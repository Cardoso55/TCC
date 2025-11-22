<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../models/PedidoReposicaoModel.php";

// Se for POST → API JSON
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header("Content-Type: application/json");

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(["erro" => "Usuário não autenticado"]);
        exit;
    }

    $id_usuario = $_SESSION['user_id'];
    $id_produto = $_POST["id_produto"] ?? null;
    $quantidade = $_POST["quantidade"] ?? null;
    $fornecedor = $_POST["fornecedor"] ?? "";

    if (!$id_produto || !$quantidade) {
        echo json_encode(["erro" => "Dados incompletos"]);
        exit;
    }

    $resultado = PedidoReposicaoModel::criarPedido($id_produto, $quantidade, $fornecedor, $id_usuario);

    echo json_encode(["sucesso" => $resultado]);
    exit;
}



// Método para listar pedidos
class RequisicaoController {

    public static function listar() {
        return PedidoReposicaoModel::listarPedidos();
    }
}
