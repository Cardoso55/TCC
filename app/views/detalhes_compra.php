<?php
if (!isset($compra)) {
    die("Erro: dados da compra não carregados.");
}
?>

<link rel="stylesheet" href="/TCC/public/css/detalhes_compra.css">
<link rel="stylesheet" href="/TCC/public/css/reset.css">
<link rel="stylesheet" href="/TCC/public/css/sidebar.css">

<div class="all">

    <!-- Sidebar -->
    <?php require __DIR__ . '/partials/sidebar.php'; ?>

    <!-- Conteúdo principal -->
    <div class="main-content">

        <!-- Info geral da compra -->
        <div class="title">Compra #<?= $compra['id_compra'] ?></div>
        <div class="subtitle">Fornecedor: <?= $compra['fornecedor'] ?></div>

        <div class="info-box">
            <p><strong>Data da compra:</strong> <?= date("d/m/Y H:i", strtotime($compra['data_compra'])) ?></p>
            <p><strong>Valor total:</strong> R$ <?= number_format($compra['valor_total'], 2, ',', '.') ?></p>
        </div>

        <!-- Itens da compra em cards -->
        <h2 class="subtitle" style="margin-top: 30px;">Itens Confirmados</h2>

        <?php if (empty($pedidos)): ?>
            <div class="empty-box">
                Nenhum item confirmado ainda.
            </div>
        <?php else: ?>
            <div class="cards-container">
                <?php foreach ($pedidos as $p): ?>
                    <div class="card-item <?= ($p['status'] === 'confirmado') ? 'status-confirmado' : 'status-acaminho' ?>">
                        <h3><?= $p['nome'] ?></h3>
                        <p><strong>ID Pedido:</strong> <?= $p['id_pedido'] ?></p>
                        <p><strong>Quantidade:</strong> <?= $p['quantidade'] ?></p>
                        <p><strong>Valor Unitário:</strong> R$ <?= number_format($p['preco_unitario'], 2, ',', '.') ?></p>
                        <p><strong>Data do Pedido:</strong> <?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?></p>
                        <p><strong>Recebido por:</strong> <?= $p['nome_usuario'] ?></p>
                        <p class="total">Total do Item: R$ <?= number_format($p['total_item'], 2, ',', '.') ?></p>
                        <span class="status-label"><?= ($p['status'] === 'confirmado') ? 'Confirmado' : 'A caminho' ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <a href="/TCC/index.php?pagina=compras" class="btn-back">Voltar</a>

    </div>
</div>
