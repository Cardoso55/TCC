<?php
require_once __DIR__ . "/../database/conexao.php";

class PedidoReposicaoModel {

    /* ============================================================
        🔥 1) CRIAR PEDIDO NORMAL (manual)
    ============================================================ */
    public static function criarPedido($id_produto, $quantidade, $fornecedor, $id_usuario) {
        $conn = conectarBanco();

        $sql = "INSERT INTO pedidosreposicao_tbl 
                (id_produto, quantidade, fornecedor, status, data_pedido, idUsuarios_TBL, gerado_por_ia)
                VALUES (?, ?, ?, 'pendente', NOW(), ?, 0)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $id_produto, $quantidade, $fornecedor, $id_usuario);

        $stmt->execute();

        // DEBUG opcional
        // echo "<pre>ERRO MYSQL: " . $stmt->error . "</pre>";

        return $stmt->affected_rows > 0;
    }

    /* ============================================================
        🔥 2) CRIAR PEDIDO GERADO PELA IA
    ============================================================ */
    public static function criarPedidoIA($id_produto, $quantidade, $id_usuario, $data_prevista_chegada = null) {
        $conn = conectarBanco();

        $sql = "INSERT INTO pedidosreposicao_tbl 
                (id_produto, quantidade, fornecedor, status, data_pedido, 
                 idUsuarios_TBL, gerado_por_ia, data_prevista_chegada)
                VALUES (?, ?, NULL, 'pendente_ia', NOW(), ?, 1, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $id_produto, $quantidade, $id_usuario, $data_prevista_chegada);

        $stmt->execute();

        return $stmt->affected_rows > 0;
    }

    /* ============================================================
        🔥 3) LISTAR TODOS OS PEDIDOS (uso geral)
    ============================================================ */
    public static function listarPedidos() {
        $conn = conectarBanco();

        $sql = "SELECT 
                    p.id_pedido,
                    p.quantidade AS quantidade_pedida,
                    e.quantidade_atual AS quantidade_estoque,
                    p.status,
                    p.data_pedido,
                    pd.nome,
                    p.gerado_por_ia
                FROM pedidosreposicao_tbl p
                INNER JOIN produtos_tbl pd 
                    ON pd.id_produto = p.id_produto
                INNER JOIN estoque_tbl e 
                    ON e.idProdutos_TBL = p.id_produto
                ORDER BY p.data_pedido DESC";

        $result = $conn->query($sql);

        $pedidos = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pedidos[] = $row;
            }
        }

        return $pedidos;
    }


    /* ============================================================
        🔥 4) LISTAR APENAS PEDIDOS GERADOS PELA IA
    ============================================================ */
    public static function listarPedidosIA() {
        $conn = conectarBanco();

        $sql = "SELECT 
                    p.*,
                    pd.nome
                FROM pedidosreposicao_tbl p
                INNER JOIN produtos_tbl pd 
                    ON pd.id_produto = p.id_produto
                WHERE p.gerado_por_ia = 1
                ORDER BY p.data_pedido DESC";

        $result = $conn->query($sql);

        $rows = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /* ============================================================
        🔥 5) ACEITAR PEDIDO (IA OU MANUAL)
    ============================================================ */
    public static function aceitarPedido($id_pedido) {
        $db = conectarBanco();
        $stmt = $db->prepare("UPDATE pedidosreposicao_tbl 
                              SET status = 'a-caminho', 
                                  data_aprovacao = NOW(),
                                  nivel_aprovacao = 1
                              WHERE id_pedido = ?");
        $stmt->bind_param("i", $id_pedido);
        $res = $stmt->execute();

        $stmt->close();
        $db->close();
        return $res;
    }

    /* ============================================================
        🔥 6) REJEITAR PEDIDO (IA OU MANUAL)
    ============================================================ */
    public static function negarPedido($id_pedido) {
        $db = conectarBanco();
        $stmt = $db->prepare("UPDATE pedidosreposicao_tbl 
                              SET status = 'negado',
                                  data_aprovacao = NOW(),
                                  nivel_aprovacao = 2
                              WHERE id_pedido = ?");
        $stmt->bind_param("i", $id_pedido);
        $res = $stmt->execute();

        $stmt->close();
        $db->close();
        return $res;
    }

    /* ============================================================
        🔥 7) BUSCAR PEDIDO PARA CRIAR COMPRA
    ============================================================ */
    public static function buscarPedidoParaCompra($idPedido) {
        $db = conectarBanco();

        $sql = "SELECT 
                    p.id_pedido,
                    p.id_produto,
                    p.quantidade,
                    p.fornecedor,
                    p.idUsuarios_TBL,
                    pr.valor_compra
                FROM pedidosreposicao_tbl p
                INNER JOIN produtos_tbl pr
                    ON pr.id_produto = p.id_produto
                WHERE p.id_pedido = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();

        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }

    /* ============================================================
        🔥 8) VINCULAR UM PEDIDO A UMA COMPRA
    ============================================================ */
    public static function atualizarCompra($idPedido, $idCompra) {
        $db = conectarBanco();
        $stmt = $db->prepare("UPDATE pedidosreposicao_tbl 
                              SET id_compra = ?
                              WHERE id_pedido = ?");
        $stmt->bind_param("ii", $idCompra, $idPedido);
        $stmt->execute();
    }

    public function getPedidosIA() {
        $conn = conectarBanco();

        $sql = "
            SELECT 
                p.*,
                pr.nome AS nome_produto
            FROM pedidosreposicao_tbl p
            LEFT JOIN produtos_tbl pr ON pr.id_produto = p.id_produto
            WHERE p.gerado_por_ia = 1
            ORDER BY p.data_pedido DESC
        ";

        $result = $conn->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function atualizarStatus($id, $status, $user_id) {
        $conn = conectarBanco();

        $stmt = $conn->prepare("
            UPDATE pedidosreposicao_tbl
            SET status = ?, nivel_aprovacao = 1, data_aprovacao = NOW(), idUsuarios_TBL = ?
            WHERE id_pedido = ?
        ");

        return $stmt->execute([$status, $user_id, $id]);
    }

}
