<?php
require_once __DIR__ . '/../database/Conexao.php';

class PedidoSaidaModel
{
    // Buscar um pedido pelo ID
    public static function getPedidoById($idPedido)
    {
        $conn = conectarBanco();

        $sql = "SELECT * FROM pedidossaida_tbl WHERE id_pedido_saida = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc(); // mysqli
    }

    // Listar todos os pedidos
    public static function listarPedidos()
    {
        $conn = conectarBanco();

        $sql = "SELECT * FROM pedidossaida_tbl ORDER BY id_pedido_saida DESC";
        $result = $conn->query($sql);

        $dados = [];
        while ($row = $result->fetch_assoc()) {
            $dados[] = $row;
        }

        return $dados; // array normal
    }
}
