<?php
require_once __DIR__ . "/../database/conexao.php";

class PedidoReposicao {

    // Criar pedido (operário)
    public static function criarPedido($id_produto, $quantidade, $fornecedor, $id_usuario) {
    if (!$id_produto || !$quantidade || empty($fornecedor) || !$id_usuario) {
        return false; // ou lançar exceção
    }

    $conn = conectarBanco();

    $sql = "INSERT INTO pedidosreposicao_tbl 
            (id_produto, quantidade, fornecedor, status, data_pedido, idUsuarios_TBL, nivel_aprovacao)
            VALUES (?, ?, ?, 'aguardando_aprovacao', NOW(), ?, 'supervisor')";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Erro no prepare: " . $conn->error);
    }

    $stmt->bind_param("iisi", $id_produto, $quantidade, $fornecedor, $id_usuario);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

    // Listar pedidos para página de reposições (aprovados)
 public static function listarPedidos() {
    $conn = conectarBanco();

    $sql = "SELECT 
                p.id_pedido,
                p.id_produto,
                p.quantidade AS quantidade_atual,
                p.status,
                p.data_pedido,
                p.nivel_aprovacao,
                pr.nome
            FROM pedidosreposicao_tbl p
            INNER JOIN produtos_tbl pr 
                ON pr.id_produto = p.id_produto
            ORDER BY p.data_pedido DESC";

    $result = $conn->query($sql);

    $pedidos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = $row;
        }
    }

    $conn->close();
    return $pedidos;
}



    // Buscar pedidos para determinado nível (solicitacoes.php)
    public static function buscarPorNivel($nivel) {
        $conn = conectarBanco();

        $sql = "SELECT p.id_pedido, p.id_produto, p.quantidade, p.status, p.data_pedido,
                       p.nivel_aprovacao, pr.nome
                FROM pedidosreposicao_tbl p
                INNER JOIN produtos_tbl pr ON pr.id_produto = p.id_produto
                WHERE p.nivel_aprovacao = ? AND p.status = 'aguardando_aprovacao'
                ORDER BY p.data_pedido DESC";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nivel);
        $stmt->execute();
        $res = $stmt->get_result();

        $pedidos = [];
        while ($row = $res->fetch_assoc()) {
            $pedidos[] = $row;
        }

        $stmt->close();
        $conn->close();
        return $pedidos;
    }

    // Buscar pedido por ID
    public static function buscarPorId($id) {
        $conn = conectarBanco();
        $stmt = $conn->prepare("SELECT * FROM pedidosreposicao_tbl WHERE id_pedido = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $pedido = $res->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $pedido;
    }

    // Atualizar aprovação (subir nível ou finalizar)
    public static function atualizarAprovacao($idPedido, $novoNivel, $novoStatus = null) {
    $conn = conectarBanco();

    $sql = "UPDATE pedidosreposicao_tbl 
            SET nivel_aprovacao = ?, 
                data_aprovacao = NOW()";

    if ($novoStatus !== null) {
        $sql .= ", status = ?";
    }

    $sql .= " WHERE id_pedido = ?";

    $stmt = $conn->prepare($sql);

    if ($novoStatus !== null) {
        $stmt->bind_param("ssi", $novoNivel, $novoStatus, $idPedido);
    } else {
        $stmt->bind_param("si", $novoNivel, $idPedido);
    }

    $res = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $res;
}


    // Rejeitar pedido
    public static function rejeitarPedido($idPedido) {
        $conn = conectarBanco();
        $stmt = $conn->prepare("UPDATE pedidosreposicao_tbl 
                                SET status = 'negado', nivel_aprovacao = NULL, data_aprovacao = NOW() 
                                WHERE id_pedido = ?");
        $stmt->bind_param("i", $idPedido);
        $res = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $res;
    }

    // Buscar pedido para criar compra
    public static function buscarPedidoParaCompra($idPedido) {
        $conn = conectarBanco();
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
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();
        $res = $stmt->get_result();
        $pedido = $res->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $pedido;
    }

    // Atualizar ID da compra
    public static function atualizarCompra($idPedido, $idCompra) {
        $conn = conectarBanco();
        $stmt = $conn->prepare("UPDATE pedidosreposicao_tbl SET id_compra=? WHERE id_pedido=?");
        $stmt->bind_param("ii", $idCompra, $idPedido);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}
