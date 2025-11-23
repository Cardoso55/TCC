<?php

require_once __DIR__ . '/../database/conexao.php';

class ReposicaoModel
{
    // Buscar pedido por ID
    public static function buscarPorId($id_pedido)
    {
        $db = conectarBanco();

        $id_pedido = (int) $id_pedido;

        $sql = "
            SELECT pr.id_pedido,
                pr.id_produto,
                pr.quantidade,
                pr.fornecedor,
                pr.status,
                pr.idUsuarios_TBL,
                prod.nome,
                prod.valor_compra,
                e.quantidade_atual,
                e.quantidade_minima,
                e.quantidade_maxima,
                e.quantidade_baixo,
                u.id_usuario,
                u.nome AS usuario_nome
            FROM pedidosreposicao_tbl pr
            JOIN produtos_tbl prod ON prod.id_produto = pr.id_produto
            JOIN estoque_tbl e ON e.idProdutos_TBL = pr.id_produto
            LEFT JOIN usuarios_tbl u ON u.id_usuario = pr.idUsuarios_TBL
            WHERE pr.id_pedido = ?
        ";

        $stmt = $db->prepare($sql);

        if (!$stmt) {
            die("ERRO AO PREPARAR SQL: " . $db->error);
        }

        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }


    // Atualizar status do pedido
    public static function atualizarStatus($id_pedido, $status)
    {
        $db = conectarBanco();

        $sql = "UPDATE pedidosreposicao_tbl SET status = ? WHERE id_pedido = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $status, $id_pedido);

        return $stmt->execute();
    }

    // Criar pedido novo (para garantir consistência)
    public static function criarPedido($id_produto, $quantidade, $fornecedor, $id_usuario)
    {
        $db = conectarBanco();

        $sql = "
            INSERT INTO pedidosreposicao_tbl 
                (id_produto, quantidade, fornecedor, status, data_pedido, idUsuarios_TBL)
            VALUES (?, ?, ?, 'pendente', NOW(), ?)
        ";

        $stmt = $db->prepare($sql);
        // fornecedor é string -> s
        $stmt->bind_param("iisi", $id_produto, $quantidade, $fornecedor, $id_usuario);

        return $stmt->execute();
    }
}

