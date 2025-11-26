<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/ChecklistModel.php';
require_once __DIR__ . '/../models/PedidoReposicaoModel.php';
require_once __DIR__ . '/../models/CompraModel.php';

header("Content-Type: application/json");

// ==========================
// VERIFICA AUTENTICAÇÃO
// ==========================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["erro" => "Usuário não autenticado"]);
    exit;
}

$userId = $_SESSION['user_id'];
$userLevel = $_SESSION['user_level'] ?? 'operario';

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["erro" => "Método inválido"]);
    exit;
}

$acao = $_POST['acao'] ?? '';
$idPedido = isset($_POST['id_pedido']) ? (int)$_POST['id_pedido'] : 0;


// ==========================
// CRIAR PEDIDO — Operário
// ==========================
if ($acao === 'criar') {
    $idProduto = (int)($_POST['id_produto'] ?? 0);
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $fornecedor = $_POST['fornecedor'] ?? '';

    if (!$idProduto || !$quantidade || !$fornecedor) {
        echo json_encode(["erro" => "Dados do pedido incompletos"]);
        exit;
    }

    $sucesso = PedidoReposicaoModel::criarPedido(
    $idProduto,
    $quantidade,
    $fornecedor,
    $_SESSION['user_level'],   // cargo do usuário
    $userId               // id do usuário
);


    echo json_encode(
        $sucesso
            ? ["sucesso" => true, "mensagem" => "Pedido criado e enviado para aprovação do supervisor!"]
            : ["erro" => "Falha ao criar pedido"]
    );
    exit;
}


// ==========================
// VALIDAR PEDIDO
// ==========================
if (!$idPedido) {
    echo json_encode(["erro" => "ID do pedido inválido"]);
    exit;
}

$pedido = PedidoReposicaoModel::buscarPorId($idPedido);

if (!$pedido) {
    echo json_encode(["erro" => "Pedido não encontrado"]);
    exit;
}



// ==========================
// APROVAR PEDIDO
// ==========================
if ($acao === 'aceitar') {

    // Supervisor aprova
    if ($userLevel === 'supervisor') {

        if ($pedido['nivel_aprovacao'] !== 'supervisor') {
            echo json_encode(["erro" => "Este pedido não está no nível do supervisor"]);
            exit;
        }

        PedidoReposicaoModel::atualizarAprovacao($idPedido, 'setor-de-compras', 'pendente');

        echo json_encode([
            "sucesso" => true,
            "mensagem" => "Pedido aprovado pelo supervisor e enviado ao setor de compras!"
        ]);
        exit;
    }

    // Setor de compras aprova
    if ($userLevel === 'setor-de-compras') {

        if ($pedido['nivel_aprovacao'] !== 'setor-de-compras') {
            echo json_encode(["erro" => "Este pedido não está no setor de compras"]);
            exit;
        }

        $idCompra = CompraModel::criarCompra($pedido['fornecedor'], 0, $userId);
        CompraModel::vincularPedidosACompra($idCompra, $idPedido);

        $valorTotal = $pedido['quantidade'] * $pedido['valor_compra'];
        CompraModel::atualizarValorTotal($idCompra);

        ChecklistModel::criarChecklist([
            'tipo' => 'compra',
            'conteudo' => 'Pedido autorizado pelo setor de compras e enviado ao fornecedor.',
            'idUsuarios_TBL' => $userId,
            'idPedidosReposicao_TBL' => $idPedido,
            'idCompra_TBL' => $idCompra,
            'idProduto_TBL' => $pedido['id_produto'] ?? null
        ]);

        PedidoReposicaoModel::atualizarStatus($idPedido, 'a-caminho');

        echo json_encode([
            "sucesso" => true,
            "mensagem" => "Pedido aprovado e enviado!"
        ]);
        exit;
    }
}


// ==========================
// REJEITAR PEDIDO
// ==========================
if ($acao === 'negar') {

    $nivelAtual = $pedido['nivel_aprovacao'];

    if (
        ($userLevel === 'supervisor' && $nivelAtual === 'supervisor') ||
        ($userLevel === 'setor-de-compras' && $nivelAtual === 'setor-de-compras')
    ) {

        PedidoReposicaoModel::rejeitarPedido($idPedido);

        echo json_encode(["sucesso" => true, "mensagem" => "Pedido rejeitado com sucesso!"]);
        exit;
    }

    echo json_encode(["erro" => "Você não pode rejeitar este pedido neste nível"]);
    exit;
}



// ==========================
// AÇÃO INVÁLIDA
// ==========================
echo json_encode(["erro" => "Ação inválida"]);
exit;
