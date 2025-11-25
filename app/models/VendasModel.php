<?php
require_once __DIR__ . "/../database/conexao.php";

class VendasModel {

    public static function criarSaida($dados) {
        $conn = conectarBanco();

        $idProduto = (int)($dados['id_produto'] ?? 0);
        $quantidade = (int)($dados['quantidade'] ?? 0);
        $observacao = $conn->real_escape_string($dados['observacao'] ?? '');
        $idUsuario = (int)($_SESSION['user_id']); // usuário logado
        $origem = 'vendas';

        if (!$idProduto || !$quantidade) {
            $conn->close();
            return ['erro' => 'Produto ou quantidade inválidos'];
        }

        $query = "INSERT INTO pedidossaida_tbl
                  (id_produto, id_usuario_solicitante, quantidade, origem, observacao)
                  VALUES ($idProduto, $idUsuario, $quantidade, '$origem', '$observacao')";

        if ($conn->query($query)) {
            $id = $conn->insert_id;
            $conn->close();
            return ['sucesso' => true, 'id_pedido_saida' => $id];
        } else {
            $erro = $conn->error;
            $conn->close();
            return ['erro' => $erro];
        }
    }

    public static function listarSaidas() {
        $conn = conectarBanco();

        $query = "SELECT s.id_pedido_saida,
                         s.quantidade,
                         s.status,
                         s.data_pedido,
                         s.observacao,
                         p.nome AS produto_nome,
                         u.nome AS usuario_nome
                  FROM pedidossaida_tbl s
                  LEFT JOIN produtos_tbl p ON s.id_produto = p.id_produto
                  LEFT JOIN usuarios_tbl u ON s.id_usuario_solicitante = u.id_usuario
                  ORDER BY s.data_pedido DESC";

        $res = $conn->query($query);
        $saidas = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $conn->close();
        return $saidas;
    }

    public static function aprovarPedido($idPedido) {
        $conn = conectarBanco();
        $id = (int)$idPedido;
        $query = "UPDATE pedidossaida_tbl SET status = 'a_caminho' WHERE id_pedido_saida = $id";
        $res = $conn->query($query);
        $conn->close();
        return $res ? true : false;
    }

    public static function recusarPedido($idPedido) {
        $conn = conectarBanco();
        $id = (int)$idPedido;

        $query = "UPDATE pedidossaida_tbl SET status = 'negado' WHERE id_pedido_saida = $id";
        $res = $conn->query($query);

        $conn->close();
        return $res ? true : false;
    }

    public static function criarChecklistInicialSaida($idPedido) {
    $conn = conectarBanco();
    $idPedido = (int)$idPedido;

    // Primeiro pega os dados do pedido
    $queryPedido = "SELECT id_produto, quantidade FROM pedidossaida_tbl WHERE id_pedido_saida = $idPedido";
    $res = $conn->query($queryPedido);

    if ($res && $res->num_rows > 0) {
        $pedido = $res->fetch_assoc();
        $idProduto = (int)$pedido['id_produto'];
        $quantidade = (int)$pedido['quantidade'];

        // Conteúdo do checklist
        $conteudo = "Verificar recebimento de $quantidade unidades do produto $idProduto";

        // Insere no checklist
        $queryInsert = "INSERT INTO checklist_tbl
                        (tipo, conteudo, status, idUsuarios_TBL, idPedidosSaida_TBL, idProduto_TBL)
                        VALUES ('saída', '$conteudo', 'pendente', " . (int)$_SESSION['user_id'] . ", $idPedido, $idProduto)";
        $conn->query($queryInsert);
    }

    $conn->close();

   
  
}
  public function buscarPedidoSaida($idPedidoSaida) {
    $conn = conectarBanco();
    $id = (int)$idPedidoSaida;
    $sql = "SELECT * FROM pedidossaida_tbl WHERE id_pedido_saida = $id";
    $res = $conn->query($sql);
    $pedido = $res && $res->num_rows ? $res->fetch_assoc() : null;
    $conn->close();
    return $pedido;
    }
  /**
 * FINALIZAR CHECKLIST DE SAÍDA
 * - atualiza checklist
 * - atualiza pedido de saída
 * - gera venda (vendas_tbl)
 * - registra movimentação (movimentacoes_tbl)
 * - baixa no estoque (estoque_tbl)
 *
 * Tudo dentro de TRANSACTION. Retorna ['sucesso'=>true] ou ['erro'=>msg].
 */
public function finalizarChecklistSaida($idChecklist, $idPedidoSaida, $idUsuario) {
    $conn = conectarBanco();
    $idChecklist = (int)$idChecklist;
    $idPedidoSaida = (int)$idPedidoSaida;
    $idUsuario = (int)$idUsuario;

    if (!$idChecklist || !$idPedidoSaida || !$idUsuario) {
        return ['erro' => 'Parâmetros inválidos para finalizar saída.'];
    }

    $conn->begin_transaction();

    try {
        // 1) Recarrega o pedido (verifica existência)
        $pedido = $this->buscarPedidoSaida($idPedidoSaida);
        if (!$pedido) throw new Exception("Pedido de saída não encontrado (ID: $idPedidoSaida).");

        $idProduto = (int)$pedido['id_produto'];
        $quantidade = (int)$pedido['quantidade'];

        if ($quantidade <= 0) throw new Exception("Quantidade inválida no pedido.");

        // 2) Pega preço atual do produto
        $sqlPreco = "SELECT preco FROM produtos_tbl WHERE id_produto = $idProduto";
        $resPreco = $conn->query($sqlPreco);
        if (!$resPreco || $resPreco->num_rows === 0) throw new Exception("Produto não encontrado para preço (ID: $idProduto).");
        $rowPreco = $resPreco->fetch_assoc();
        $precoUnitario = (float)$rowPreco['preco'];
        $valorTotal = $precoUnitario * $quantidade;

        // 3) Verifica estoque atual
        $sqlEstoque = "SELECT quantidade_atual FROM estoque_tbl WHERE id_produto = $idProduto FOR UPDATE";
        $resEst = $conn->query($sqlEstoque);
        if (!$resEst || $resEst->num_rows === 0) throw new Exception("Registro de estoque não encontrado para o produto $idProduto.");
        $rowEst = $resEst->fetch_assoc();
        $estoqueAtual = (int)$rowEst['quantidade_atual'];

        if ($estoqueAtual < $quantidade) {
            throw new Exception("Estoque insuficiente. Atual: $estoqueAtual, necessário: $quantidade.");
        }

        // 4) Atualizar checklist (marca concluído)
        $sqlUpdChecklist = "
            UPDATE checklist_tbl
            SET status = 'concluído', data_confirmacao = NOW(), idUsuarios_TBL = $idUsuario
            WHERE id_checklist = $idChecklist
        ";
        if (!$conn->query($sqlUpdChecklist)) throw new Exception("Erro ao atualizar checklist: " . $conn->error);

        // 5) Atualizar pedido de saída
        $sqlUpdPedido = "
            UPDATE pedidossaida_tbl
            SET status = 'concluído', data_atualizacao = NOW()
            WHERE id_pedido_saida = $idPedidoSaida
        ";
        if (!$conn->query($sqlUpdPedido)) throw new Exception("Erro ao atualizar pedido de saída: " . $conn->error);

        // 6) Inserir venda na vendas_tbl
        // A coluna preco_unitario deve existir na sua tabela; ajuste o nome se for diferente.
        $sqlInsVenda = "
            INSERT INTO vendas_tbl
            (id_produto, id_usuario, quantidade, preco_unitario, valor_total, canal_venda, data_venda)
            VALUES
            ($idProduto, $idUsuario, $quantidade, $precoUnitario, $valorTotal, 'sistema', NOW())
        ";
        if (!$conn->query($sqlInsVenda)) throw new Exception("Erro ao inserir venda: " . $conn->error);

        // 7) Inserir movimentação
        $sqlInsMov = "
            INSERT INTO movimentacoes_tbl
            (id_produto, tipo, quantidade, id_usuario, data)
            VALUES
            ($idProduto, 'saida', $quantidade, $idUsuario, NOW())
        ";
        if (!$conn->query($sqlInsMov)) throw new Exception("Erro ao inserir movimentação: " . $conn->error);

        // 8) Baixa no estoque
        $sqlBaixa = "
            UPDATE estoque_tbl
            SET quantidade_atual = quantidade_atual - $quantidade
            WHERE id_produto = $idProduto
        ";
        if (!$conn->query($sqlBaixa)) throw new Exception("Erro ao baixar estoque: " . $conn->error);

        // Tudo certo
        $conn->commit();
        $conn->close();
        return ['sucesso' => true];

    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        return ['erro' => $e->getMessage()];
    }


}
public static function concluirChecklist($idChecklist) {
    $conn = conectarBanco();
    $id = (int)$idChecklist;

    $sql = "UPDATE checklist_tbl
            SET status = 'concluido',
                data_confirmacao = NOW()
            WHERE id_checklist = $id";

    $res = $conn->query($sql);
    $conn->close();
    return $res;
}
public static function concluirPedidoSaida($idPedidoSaida) {
    $conn = conectarBanco();
    $id = (int)$idPedidoSaida;

    $sql = "UPDATE pedidossaida_tbl 
        SET status = 'aprovado'
        WHERE id_pedido_saida = $id";

    $res = $conn->query($sql);
    $conn->close();
    return $res;
}

public static function getInfoPedidoSaida($idPedidoSaida) {
    $conn = conectarBanco();
    $id = (int)$idPedidoSaida;

    $sql = "SELECT 
                p.id_produto,
                p.preco_unitario,
                s.quantidade
            FROM pedidossaida_tbl s
            JOIN produtos_tbl p ON s.id_produto = p.id_produto
            WHERE s.id_pedido_saida = $id";

    $res = $conn->query($sql);
    $dados = $res ? $res->fetch_assoc() : null;

    $conn->close();
    return $dados;
}
public static function baixarEstoque($idProduto, $quantidade) {
    $conn = conectarBanco();

    $sql = "UPDATE estoque_tbl
            SET quantidade_atual = quantidade_atual - $quantidade
            WHERE idProdutos_TBL = $idProduto";

    $res = $conn->query($sql);
    $conn->close();
    return $res;
}
public static function registrarMovimentacao($idProduto, $quantidade) {
    $conn = conectarBanco();

    $sql = "INSERT INTO movimentacoes_tbl
            (idProdutos_TBL, quantidade, tipo, data_movimentacao)
            VALUES ($idProduto, $quantidade, 'saida', NOW())";

    $res = $conn->query($sql);
    $conn->close();
    return $res;
}

}






