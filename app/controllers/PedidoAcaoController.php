<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/PedidoReposicaoModel.php';
require_once __DIR__ . '/../models/CompraModel.php';

header("Content-Type: application/json");

// ✅ Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["erro" => "Usuário não autenticado"]);
    exit;
}

// Só processa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $acao = $_POST['acao'] ?? '';
    $idPedido = isset($_POST['id_pedido']) ? (int)$_POST['id_pedido'] : 0;

    if (!$idPedido) {
        echo json_encode(["erro" => "ID do pedido inválido"]);
        exit;
    }

   if ($acao === 'aceitar') {
    PedidoReposicaoModel::aceitarPedido($idPedido);

    $pedido = PedidoReposicaoModel::buscarPedidoParaCompra($idPedido);

    if (!$pedido) {
        echo json_encode(["erro" => "Pedido não encontrado ou já processado"]);
        exit;
    }

    $idUsuario = $pedido['idUsuarios_TBL'];
    if (!$idUsuario) {
        echo json_encode(["erro" => "Pedido sem usuário associado. Compra não pode ser criada."]);
        exit;
    }

    $quantidade = $pedido['quantidade'];
    $valorUnitario = $pedido['valor_compra'];
    $valorTotal = $quantidade * $valorUnitario;
    $fornecedor = $pedido['fornecedor'];

    // Cria compra e vincula pedido
    $idCompra = CompraModel::criarCompra($fornecedor, $valorTotal, $idUsuario);
    PedidoReposicaoModel::atualizarCompra($idPedido, $idCompra);

    // 🚀 Aqui gera os checklists automaticamente
    require_once __DIR__ . '/../controllers/ChecklistController.php';
    ChecklistController::gerarParaCompra(
    $idCompra,
    $idUsuario,
    $pedido['id_produto'],
    $quantidade,
    $pedido['id_pedido'] // ✅ o ID do pedido que está faltando
);


    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Compra criada, pedido confirmado e checklists gerados!",
        "id_compra" => $idCompra
    ]);
    exit;
}


    // NEGAR PEDIDO
    if ($acao === 'negar') {
        PedidoReposicaoModel::negarPedido($idPedido);
        echo json_encode(["sucesso" => true, "mensagem" => "Pedido negado"]);
        exit;
    }

    echo json_encode(["erro" => "Ação inválida"]);
}
