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


   public static function registrarVenda($idProduto, $idUsuario, $quantidade)
{
    $conn = conectarBanco();

    // Pega o preço
    $preco = self::buscarPreco($idProduto);

    $sql = "INSERT INTO vendas_tbl (id_produto, id_usuario, quantidade, preco_unitario)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiid", $idProduto, $idUsuario, $quantidade, $preco);

    return $stmt->execute();
}
    /* ============================================================
        FUNÇÕES NOVAS PARA RELATÓRIOS
       ============================================================ */

    // Receita total = soma(preco_unitario * quantidade)
    public static function getReceitaTotal($dias = null) {
        $conn = conectarBanco();

        $filtro = "";
        if ($dias) {
            $filtro = "WHERE data_venda >= DATE_SUB(NOW(), INTERVAL $dias DAY)";
        }

        $sql = "
            SELECT SUM(preco_unitario * quantidade) AS receita_total
            FROM vendas_tbl
            $filtro
        ";

        $res = $conn->query($sql);
        $row = $res->fetch_assoc();
        $conn->close();

        return (float)($row['receita_total'] ?? 0);
    }

    // Quantidade total vendida
    public static function getQuantidadeTotal($dias = null) {
        $conn = conectarBanco();

        $filtro = "";
        if ($dias) {
            $filtro = "WHERE data_venda >= DATE_SUB(NOW(), INTERVAL $dias DAY)";
        }

        $sql = "
            SELECT SUM(quantidade) AS total_quantidade
            FROM vendas_tbl
            $filtro
        ";

        $res = $conn->query($sql);
        $row = $res->fetch_assoc();
        $conn->close();

        return (int)($row['total_quantidade'] ?? 0);
    }

    // Produtos mais vendidos
 public static function getProdutosMaisVendidos($dias = null) {
    $conn = conectarBanco();

    $filtro = "";
    if ($dias) {
        // força inteiro pra evitar injeção acidental via $_GET
        $dias = (int) $dias;
        $filtro = "WHERE v.data_venda >= DATE_SUB(NOW(), INTERVAL $dias DAY)";
    }

    $sql = "
        SELECT 
            p.id_produto,
            p.nome,
            COALESCE(p.preco_unitario, 0) AS preco_unitario_atual,
            SUM(v.quantidade) AS total_vendido,
            SUM(v.valor_total) AS receita
        FROM vendas_tbl v
        LEFT JOIN produtos_tbl p ON v.id_produto = p.id_produto
        $filtro
        GROUP BY p.id_produto, p.nome, p.preco_unitario
        ORDER BY total_vendido DESC
        LIMIT 10
    ";

    $res = $conn->query($sql);
    if ($res === false) {
        // em vez de quebrar, retorna array vazio — facilita debug sem 500 fatal
        error_log("getProdutosMaisVendidos SQL error: " . $conn->error);
        $conn->close();
        return [];
    }

    $dados = $res->fetch_all(MYSQLI_ASSOC);
    $conn->close();

    return $dados;
}




    // Vendas por canal (mesmo que tudo seja 'sistema', fica pronto)
    public static function getVendasPorCanal($dias = null) {
        $conn = conectarBanco();

        $filtro = "";
        if ($dias) {
            $filtro = "WHERE data_venda >= DATE_SUB(NOW(), INTERVAL $dias DAY)";
        }

        $sql = "
            SELECT canal_venda, COUNT(*) AS total
            FROM vendas_tbl
            $filtro
            GROUP BY canal_venda
        ";

        $res = $conn->query($sql);
        $dados = $res->fetch_all(MYSQLI_ASSOC);
        $conn->close();

        return $dados;
    }

      public static function getReceitasPorProduto($dias = null) {
        $conn = conectarBanco();

        $where = "";
        if ($dias !== null) {
            $dias = (int)$dias;
            if ($dias > 0) {
                $where = "WHERE v.data_venda >= DATE_SUB(NOW(), INTERVAL {$dias} DAY)";
            }
        }

        $sql = "
            SELECT
                p.id_produto,
                p.nome,
                COALESCE(p.preco_unitario, 0) AS preco_unitario_atual,
                SUM(v.quantidade) AS total_vendido,
                SUM(v.valor_total) AS receita
            FROM vendas_tbl v
            LEFT JOIN produtos_tbl p ON v.id_produto = p.id_produto
            {$where}
            GROUP BY p.id_produto, p.nome, p.preco_unitario
            ORDER BY receita DESC
        ";

        $res = $conn->query($sql);
        if ($res === false) {
            error_log("getReceitasPorProduto SQL error: " . $conn->error);
            $conn->close();
            return [
                'receita_total' => 0,
                'quantidade_total' => 0,
                'por_produto' => []
            ];
        }

        $dados = $res->fetch_all(MYSQLI_ASSOC);

        // totais
        $receitaTotal = 0.0;
        $quantidadeTotal = 0;
        foreach ($dados as $r) {
            $receitaTotal += (float)$r['receita'];
            $quantidadeTotal += (int)$r['total_vendido'];
        }

        $conn->close();

        return [
            'receita_total' => (float)$receitaTotal,
            'quantidade_total' => (int)$quantidadeTotal,
            'por_produto' => $dados
        ];
    }
  public static function getResumoFinanceiro($dias = null) {
        $conn = conectarBanco();

        $where = "";
        if ($dias !== null) {
            $dias = (int)$dias;
            if ($dias > 0) {
                $where = "WHERE v.data_venda >= DATE_SUB(NOW(), INTERVAL {$dias} DAY)";
            }
        }

        // primeiro agregamos vendas por produto (receita e quantidade)
        $sqlVendas = "
            SELECT
                p.id_produto,
                p.nome,
                SUM(v.quantidade) AS quantidade_vendida,
                SUM(v.valor_total) AS receita
            FROM vendas_tbl v
            LEFT JOIN produtos_tbl p ON v.id_produto = p.id_produto
            {$where}
            GROUP BY p.id_produto, p.nome
        ";

        $res = $conn->query($sqlVendas);
        if ($res === false) {
            error_log("getResumoFinanceiro SQL error (vendas agg): " . $conn->error);
            $conn->close();
            return [
                'produtos' => [],
                'receita_total' => 0,
                'custo_total' => 0,
                'lucro_real_total' => 0,
                'valor_estoque_parado' => 0
            ];
        }

        $vendasAgg = $res->fetch_all(MYSQLI_ASSOC);

        // montar lookup de vendas
        $receitaTotal = 0.0;
        $custoTotal = 0.0;
        $lucroTotal = 0.0;
        $produtosResultado = [];

        foreach ($vendasAgg as $row) {
            $id = $row['id_produto'];
            $nome = $row['nome'];
            $qtdVendida = (int)$row['quantidade_vendida'];
            $receita = (float)$row['receita'];

            // buscar custo unitário atual do produto e estoque atual (do mesmo produtos_tbl/estoque_tbl)
            $sqlProd = "
                SELECT p.valor_compra, e.quantidade_atual AS estoque_atual
                FROM produtos_tbl p
                LEFT JOIN estoque_tbl e ON e.idProdutos_TBL = p.id_produto
                WHERE p.id_produto = " . ((int)$id) . "
                LIMIT 1
            ";
            $r2 = $conn->query($sqlProd);
            $prodInfo = $r2 ? $r2->fetch_assoc() : null;

            $valorCompraUnit = $prodInfo && isset($prodInfo['valor_compra']) ? (float)$prodInfo['valor_compra'] : 0.0;
            $estoqueAtual = $prodInfo && isset($prodInfo['estoque_atual']) ? (int)$prodInfo['estoque_atual'] : 0;

            $custo = $qtdVendida * $valorCompraUnit;
            $lucro = $receita - $custo;

            $receitaTotal += $receita;
            $custoTotal += $custo;
            $lucroTotal += $lucro;

            $valorEstoqueProduto = $estoqueAtual * $valorCompraUnit;

            $produtosResultado[] = [
                'id_produto' => $id,
                'nome' => $nome,
                'quantidade_vendida' => $qtdVendida,
                'receita' => (float)$receita,
                'custo' => (float)$custo,
                'lucro' => (float)$lucro,
                'estoque_atual' => $estoqueAtual,
                'valor_estoque' => (float)$valorEstoqueProduto
            ];
        }

        // somar valor do estoque parado também para produtos que NÃO venderam no período
        $sqlEstoqueParado = "
            SELECT p.id_produto, p.nome, COALESCE(p.valor_compra,0) AS valor_compra, COALESCE(e.quantidade_atual,0) AS estoque_atual
            FROM produtos_tbl p
            LEFT JOIN estoque_tbl e ON e.idProdutos_TBL = p.id_produto
            WHERE p.ativo = 1
        ";
        $res3 = $conn->query($sqlEstoqueParado);
        $valorEstoqueParado = 0.0;
        if ($res3) {
            while ($r = $res3->fetch_assoc()) {
                $valorEstoqueParado += ((float)$r['valor_compra']) * ((int)$r['estoque_atual']);
            }
        }

        $conn->close();

        return [
            'produtos' => $produtosResultado,
            'receita_total' => (float)$receitaTotal,
            'custo_total' => (float)$custoTotal,
            'lucro_real_total' => (float)$lucroTotal,
            'valor_estoque_parado' => (float)$valorEstoqueParado
        ];
    }
} // <-- FIM DA CLASSE




