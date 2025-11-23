<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../models/PedidoReposicao.php';
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

    $sucesso = PedidoReposicao::criarPedido($idProduto, $quantidade, $fornecedor, $userId);

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

$pedido = PedidoReposicao::buscarPorId($idPedido);

if (!$pedido) {
    echo json_encode(["erro" => "Pedido não encontrado"]);
    exit;
}



// ==========================
// APROVAR PEDIDO
// ==========================
if ($acao === 'aceitar') {

    // -------------------------
    // Supervisor aprova
    // -------------------------
    if ($userLevel === 'supervisor') {

        // Confere se o pedido tá no nível do supervisor
        if ($pedido['nivel_aprovacao'] !== 'supervisor') {
            echo json_encode(["erro" => "Este pedido não está no nível do supervisor"]);
            exit;
        }

        // Atualiza nível para setor de compras e mantém status pendente
        PedidoReposicao::atualizarAprovacao($idPedido, 'setor-de-compras', 'pendente');

        echo json_encode(["sucesso" => "Pedido aprovado pelo supervisor e enviado ao setor de compras!"]);
        exit;
    }

    // -------------------------
    // Setor de compras aprova
    // -------------------------
    if ($userLevel === 'setor-de-compras') {

        // Confere se o pedido tá no nível correto
        if ($pedido['nivel_aprovacao'] !== 'setor-de-compras') {
            echo json_encode(["erro" => "Este pedido não está no setor de compras"]);
            exit;
        }

        // 1. Criar compra
        $idCompra = CompraModel::criarCompra(
            $pedido['fornecedor'], // fornecedor do pedido
            0,                     // valor_total inicial
            $userId
        );

        // 2. Vincular pedido à compra
        CompraModel::vincularPedidosACompra($idCompra, $idPedido);

        // 3. Criar checklist vinculado à compra
        ChecklistModel::criarChecklist([
            'tipo' => 'compra',
            'conteudo' => 'Pedido autorizado pelo setor de compras e enviado ao fornecedor.',
            'idUsuarios_TBL' => $userId,
            'idPedidosReposicao_TBL' => $idPedido,
            'idCompra_TBL' => $idCompra,
            'idProduto_TBL' => $pedido['id_produto'] ?? null
        ]);

        // 4. Atualizar status do pedido (não mexe no nível)
        PedidoReposicao::atualizarAprovacao($idPedido, 'setor-de-compras', 'a-caminho');

        echo json_encode(["sucesso" => "Pedido aprovado e enviado!"]);
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
    echo json_encode(["erro" => "Nível inválido para aprovar este pedido"]);
    exit;
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

        PedidoReposicao::rejeitarPedido($idPedido);

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
