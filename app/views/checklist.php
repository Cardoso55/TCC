<?php

require_once __DIR__ . '/../controllers/ChecklistController.php';

// Filtros opcionais
$filtros = [];
if (isset($_GET['tipo'])) $filtros['tipo'] = $_GET['tipo'];
if (isset($_GET['idProduto'])) $filtros['idProduto_TBL'] = $_GET['idProduto'];
if (isset($_GET['idCompra'])) $filtros['idCompra_TBL'] = $_GET['idCompra'];
if (isset($_GET['idPedido'])) $filtros['idPedidosReposicao_TBL'] = $_GET['idPedido'];

$checklists = ChecklistController::listar($filtros);

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Checklists</title>
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
<h1>Checklists</h1>

<?php if (!$checklists) echo "<p>Nenhum checklist encontrado.</p>"; ?>

<?php foreach ($checklists as $c): 
    $tipoClass = $c['tipo'] === 'entrada' ? 'tipo-entrada' : 'tipo-saida';
    $statusClass = 'status-' . str_replace(' ', '_', $c['status']);
?>
<div class="card <?= $tipoClass ?> <?= $statusClass ?>">
    <p><strong>Produto/ID:</strong> <?= $c['produto_nome'] ?? $c['idProduto_TBL'] ?></p>
    <p><strong>Conteúdo:</strong> <?= $c['conteudo'] ?></p>
    <p><strong>Status:</strong> <?= $c['status'] ?></p>
    <p><strong>Observação:</strong> <?= $c['observacao'] ?: 'Nenhuma' ?></p>
    <p><strong>Data criação:</strong> <?= $c['data_criacao'] ?></p>
    <p><strong>Data confirmação:</strong> <?= $c['data_confirmacao'] ?: '-' ?></p>
    <p><strong>Usuário responsável:</strong> <?= $c['usuario_nome'] ?></p>

    <?php if ($c['status'] !== 'concluído'): ?>
    <!-- Form para confirmar checklist -->
    <form action="?pagina=checklist_confirmar" method="post" style="display:inline">
        <input type="hidden" name="idChecklist" value="<?= $c['id_checklist'] ?>">
        <input type="hidden" name="idUsuario" value="<?= $_SESSION['user_id'] ?? 1 ?>">
        <?php if (!empty($c['idPedidosReposicao_TBL'])): ?>
            <input type="hidden" name="idPedido" value="<?= $c['idPedidosReposicao_TBL'] ?>">
        <?php endif; ?>
        <button class="botao botao-confirmar">Confirmar</button>
    </form>

    <!-- Form para adicionar observação -->
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
