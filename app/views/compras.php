<?php
require_once __DIR__ . '/../controllers/ComprasController.php';

$controller = new ComprasController();
$compras = $controller->getCompras();
?>

<link rel="stylesheet" href="/TCC/public/css/compras.css">
<link rel="stylesheet" href="/TCC/public/css/reset.css">
<link rel="stylesheet" href="/TCC/public/css/sidebar.css">

<div class="all">

    <?php require_once __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-content">

        <div class="title">Compras</div>
        <div class="subtitle">Lista de compras realizadas</div>

        <div class="stock-management">
            <input type="text" id="searchCompra" placeholder="Pesquisar por fornecedor ou usuário...">
            <button onclick="filtrarCompras()">Buscar</button>
        </div>

        <div class="product-list">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fornecedor</th>
                        <th>Valor Total</th>
                        <th>Data</th>
                        <th>Registrado por</th>
                        <th>Ação</th>
                    </tr>
                </thead>

                <tbody id="tabelaCompras">
                    <?php if (!empty($compras)): ?>
                        <?php foreach ($compras as $compra): ?>
                            <tr>
                                <td><?= $compra['id_compra'] ?></td>
                                <td><?= $compra['fornecedor'] ?></td>
                                <td>R$ <?= number_format($compra['valor_total'], 2, ',', '.') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($compra['data_compra'])) ?></td>
                                <td><?= $compra['nome_usuario'] ?></td>

                                <td>
                                    <a class="btn-view" 
                                       href="index.php?pagina=detalhes_compra&id=<?= $compra['id_compra'] ?>">
                                       Ver
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding:20px; font-weight:600;">
                                Nenhuma compra encontrada.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</div>

<script>
function filtrarCompras() {
    let termo = document.getElementById('searchCompra').value.toLowerCase();
    let linhas = document.querySelectorAll('#tabelaCompras tr');

    linhas.forEach(tr => {
        let texto = tr.innerText.toLowerCase();
        tr.style.display = texto.includes(termo) ? '' : 'none';
    });
}
</script>
