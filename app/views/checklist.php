<?php
require_once __DIR__ . '/../controllers/ChecklistController.php';

$tipoBruto = $_GET['tipo'] ?? 'saída';
$ehEntrada = in_array(strtolower(trim($tipoBruto)), ['entrada', 'compra']);
$tipo = $ehEntrada ? 'compra' : 'saída';

$filtros = [];
if (isset($_GET['idProduto'])) $filtros['idProduto_TBL'] = $_GET['idProduto'];
if (isset($_GET['idCompra'])) $filtros['idCompra_TBL'] = $_GET['idCompra'];
if (isset($_GET['idPedido'])) {
    $filtrosKey = $ehEntrada ? 'idPedidosReposicao_TBL' : 'idPedidosSaida_TBL';
    $filtros[$filtrosKey] = $_GET['idPedido'];
}

// Listagem
$checklists = $ehEntrada 
    ? ChecklistController::listarEntrada($filtros)
    : ChecklistController::listarSaida($filtros);

$userLevel = $_SESSION['user_level'] ?? 'setor-de-vendas';
$saidaPermitidos = ['setor-de-vendas'];
$compraPermitidos = ['operário','supervisor','gerente','diretor'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Checklists</title>
<link rel="stylesheet" href="/TCC/public/css/reset.css">
<link rel="stylesheet" href="/TCC/public/css/checklist.css">
<link rel="stylesheet" href="/TCC/public/css/sidebar.css">
</head>
<body>

<?php if (isset($_GET['sucesso'])): ?>
<div style="background:#2ecc71; padding:10px; color:white; border-radius:5px; margin-bottom:15px;">
Checklist confirmado com sucesso!
</div>
<?php endif; ?>

<div class="wrapper">
<?php require_once __DIR__ . '/partials/sidebar.php'; ?>
<div class="conteudo">
<h1>Checklists – <?= ucfirst($tipo) ?></h1>

<?php if (!$checklists) echo "<p>Nenhum checklist encontrado.</p>"; ?>

<?php foreach ($checklists as $c):
    $isEntrada = in_array(strtolower(trim($c['tipo'])), ['entrada','compra']);
    $statusDB = strtolower(trim($c['status'] ?? ''));
    $isConcluido = in_array($statusDB, ['concluido','concluído','confirmado','feito','finalizado']);

    $tipoClass = $isEntrada ? 'tipo-entrada' : 'tipo-saida';
    $statusClass = 'status-' . str_replace(' ', '_', $statusDB);
?>
<div class="card <?= $tipoClass ?> <?= $statusClass ?>">

    <p><strong>Produto/ID:</strong> <?= $c['produto_nome'] ?? $c['idProduto_TBL'] ?></p>
    <p><strong>Conteúdo:</strong> <?= $c['conteudo'] ?></p>
    <p><strong>Status:</strong> <?= $c['status'] ?></p>
    <p><strong>Observação:</strong> <?= $c['observacao'] ?: 'Nenhuma' ?></p>
    <p><strong>Data criação:</strong> <?= $c['data_criacao'] ?></p>
    <p><strong>Data confirmação:</strong> <?= $c['data_confirmacao'] ?: '-' ?></p>
    <p><strong>Usuário responsável:</strong> <?= $c['usuario_nome'] ?></p>

    <?php 
    $podeConfirmar = !$isConcluido && (
        (!$isEntrada && in_array($userLevel, $saidaPermitidos)) ||
        ($isEntrada && in_array($userLevel, $compraPermitidos))
    );
    ?>

    <?php if ($podeConfirmar): ?>
    <form action="?pagina=checklist_confirmar" method="post" style="display:inline">

        <input type="hidden" name="idChecklist" value="<?= $c['id_checklist'] ?>">
        <input type="hidden" name="idUsuario" value="<?= $_SESSION['user_id'] ?>">

        <?php 
            // envia automaticamente o ID correto do pedido
            if (!empty($c['idPedidosSaida_TBL'])) {
                $pedido = $c['idPedidosSaida_TBL'];
            } elseif (!empty($c['idPedidosReposicao_TBL'])) {
                $pedido = $c['idPedidosReposicao_TBL'];
            } elseif (!empty($c['idCompra_TBL'])) {
                $pedido = $c['idCompra_TBL'];
            } else {
                $pedido = null;
            }
        ?>

        <?php if ($pedido): ?>
            <input type="hidden" name="idPedido" value="<?= $pedido ?>">
        <?php endif; ?>

        <button class="botao botao-confirmar">Confirmar</button>
    </form>

    <form action="?pagina=checklist_observacao" method="post" style="display:inline">
        <input type="hidden" name="idChecklist" value="<?= $c['id_checklist'] ?>">
        <input type="text" name="observacao" placeholder="Adicionar observação">
        <button class="botao botao-observacao">Salvar</button>
    </form>
<?php endif; ?>


</div>
<?php endforeach; ?>

</div>
</div>
</body>
</html>
