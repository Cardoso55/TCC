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

    public static function marcarComoConcluido($idPedido)
    {
    $conn = conectarBanco();

    $sql = "UPDATE pedidossaida_tbl 
            SET status = 'aprovado', data_atualizacao = NOW() 
            WHERE id_pedido_saida = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idPedido);
    $stmt->execute();

    return $stmt->affected_rows > 0;
    }   
    
    public static function atualizarStatus($idPedido, $status)
{
    $conn = conectarBanco();

    $sql = "UPDATE pedidossaida_tbl SET status = ? WHERE id_pedido_saida = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $idPedido);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}
public static function registrarVenda($idProduto, $quantidade, $idUsuario)
{
    $conn = conectarBanco();

    $sql = "INSERT INTO vendas_tbl (id_produto, id_usuario, quantidade, preco_unitario)
            VALUES (?, ?, ?, ?)";

    $preco = ProdutoModel::buscarPreco($idProduto);

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiid", $idProduto, $quantidade, $idUsuario, $preco);

    return $stmt->execute();
}




}
