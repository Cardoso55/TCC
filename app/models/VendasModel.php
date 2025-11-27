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

        $queryPedido = "SELECT id_produto, quantidade FROM pedidossaida_tbl WHERE id_pedido_saida = $idPedido";
        $res = $conn->query($queryPedido);

        if ($res && $res->num_rows > 0) {
            $pedido = $res->fetch_assoc();
            $idProduto = (int)$pedido['id_produto'];
            $quantidade = (int)$pedido['quantidade'];
            $conteudo = "Verificar recebimento de $quantidade unidades do produto $idProduto";

            $queryInsert = "INSERT INTO checklist_tbl
                            (tipo, conteudo, status, idUsuarios_TBL, idPedidosSaida_TBL, idProduto_TBL)
                            VALUES ('saída', '$conteudo', 'pendente', " . (int)$_SESSION['user_id'] . ", $idPedido, $idProduto)";
            if ($conn->query($queryInsert)) {
                $idChecklist = $conn->insert_id;
                $conn->close();
                return $idChecklist; // <-- retorna o ID
            }
        }

        $conn->close();
        return null;
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
        $pedido = $this->buscarPedidoSaida($idPedidoSaida);
        if (!$pedido) throw new Exception("Pedido de saída não encontrado (ID: $idPedidoSaida).");

        $idProduto = (int)$pedido['id_produto'];
        $quantidade = (int)$pedido['quantidade'];
        if ($quantidade <= 0) throw new Exception("Quantidade inválida no pedido.");

        $sqlPreco = "SELECT preco FROM produtos_tbl WHERE id_produto = $idProduto";
        $resPreco = $conn->query($sqlPreco);
        if (!$resPreco || $resPreco->num_rows === 0) throw new Exception("Produto não encontrado para preço (ID: $idProduto).");
        $precoUnitario = (float)$resPreco->fetch_assoc()['preco'];

        $sqlEstoque = "SELECT quantidade_atual FROM estoque_tbl WHERE idProdutos_TBL = $idProduto FOR UPDATE";
        $resEst = $conn->query($sqlEstoque);
        if (!$resEst || $resEst->num_rows === 0) throw new Exception("Registro de estoque não encontrado para o produto $idProduto.");
        $estoqueAtual = (int)$resEst->fetch_assoc()['quantidade_atual'];

        if ($estoqueAtual < $quantidade) {
            throw new Exception("Estoque insuficiente. Atual: $estoqueAtual, necessário: $quantidade.");
        }

        error_log("FINALIZAR SAIDA: idChecklist=$idChecklist, idPedidoSaida=$idPedidoSaida, idUsuario=$idUsuario");

        $sqlUpdChecklist = "
            UPDATE checklist_tbl
            SET status = 'concluído', data_confirmacao = NOW(), idUsuarios_TBL = $idUsuario
            WHERE id_checklist = $idChecklist
        ";
        error_log("SQL Checklist: $sqlUpdChecklist");

        if (!$conn->query($sqlUpdChecklist)) throw new Exception("Erro ao atualizar checklist: " . $conn->error);

        $sqlUpdPedido = "
            UPDATE pedidossaida_tbl
            SET status = 'concluído', data_atualizacao = NOW()
            WHERE id_pedido_saida = $idPedidoSaida
        ";
        error_log("SQL Pedido: $sqlUpdPedido");

        if (!$conn->query($sqlUpdPedido)) throw new Exception("Erro ao atualizar pedido de saída: " . $conn->error);


        // 3) Registrar venda (sem valor_total)
        $sqlInsVenda = "
            INSERT INTO vendas_tbl
            (id_produto, id_usuario, quantidade, preco_unitario, canal_venda, data_venda)
            VALUES
            ($idProduto, $idUsuario, $quantidade, $precoUnitario, 'sistema', NOW())
        ";
        if (!$conn->query($sqlInsVenda)) throw new Exception("Erro ao inserir venda: " . $conn->error);

        // 4) Registrar movimentação
        $sqlInsMov = "
            INSERT INTO movimentacoes_tbl
            (idProdutos_TBL, quantidade, tipo, data_movimentacao)
            VALUES ($idProduto, $quantidade, 'saida', NOW())
        ";
        if (!$conn->query($sqlInsMov)) throw new Exception("Erro ao inserir movimentação: " . $conn->error);


        $sqlBaixa = "
            UPDATE estoque_tbl
            SET quantidade_atual = quantidade_atual - $quantidade
            WHERE idProdutos_TBL = $idProduto
        ";
        if (!$conn->query($sqlBaixa)) throw new Exception("Erro ao baixar estoque: " . $conn->error);

        $conn->commit();
        $conn->close();
        return ['sucesso' => true];

    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        return ['erro' => $e->getMessage()];
    }
}


    private function registrarVenda($idProduto, $idUsuario, $quantidade, $precoUnitario, $conn) {
    $sqlInsVenda = "
        INSERT INTO vendas_tbl
        (id_produto, id_usuario, quantidade, preco_unitario, canal_venda, data_venda)
        VALUES
        ($idProduto, $idUsuario, $quantidade, $precoUnitario, 'sistema', NOW())
    ";
    if (!$conn->query($sqlInsVenda)) {
        throw new Exception("Erro ao registrar venda: " . $conn->error);
    }
}


}
