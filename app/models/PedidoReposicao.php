<?php
require_once __DIR__ . "/../database/conexao.php";

class PedidoReposicao {

    // Criar pedido
    public static function criarPedido($id_produto, $quantidade, $fornecedor, $id_usuario) {
        $conn = conectarBanco();

        $sql = "INSERT INTO pedidosreposicao_tbl 
                (id_produto, quantidade, fornecedor, status, data_pedido, idUsuarios_TBL)
                VALUES (?, ?, ?, 'pendente', NOW(), ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisi", $id_produto, $quantidade, $fornecedor, $id_usuario);

         $stmt->execute();

    // DEBUG AQUI
    echo "<pre>ERRO MYSQL: " . $stmt->error . "</pre>";

         return $stmt->affected_rows > 0;
    }

    // Listar pedidos
    public static function listarPedidos() {
        $conn = conectarBanco();

        $sql = "SELECT 
                    p.id_pedido,
                    p.quantidade AS quantidade_atual,
                    p.status,
                    p.data_pedido,
                    pd.nome
                FROM pedidosreposicao_tbl p
                INNER JOIN produtos_tbl pd 
                    ON pd.id_produto = p.id_produto
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

    public static function aceitarPedido($id_pedido) {
        $db = conectarBanco();
        $stmt = $db->prepare("UPDATE PedidosReposicao_TBL SET status = 'a-caminho' WHERE id_pedido = ?");
        $stmt->bind_param("i", $id_pedido);
        $res = $stmt->execute();
        $stmt->close();
        $db->close();
        return $res;
    }

    public static function negarPedido($id_pedido) {
        $db = conectarBanco();
        $stmt = $db->prepare("UPDATE PedidosReposicao_TBL SET status = 'negado' WHERE id_pedido = ?");
        $stmt->bind_param("i", $id_pedido);
        $res = $stmt->execute();
        $stmt->close();
        $db->close();
        return $res;
    }
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




public static function atualizarCompra($idPedido, $idCompra) {
    $db = conectarBanco();
    $stmt = $db->prepare("UPDATE pedidosreposicao_tbl SET id_compra=? WHERE id_pedido=?");
    $stmt->bind_param("ii", $idCompra, $idPedido);
    $stmt->execute();
}

}

