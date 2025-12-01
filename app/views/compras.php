<?php
require_once __DIR__ . '/../controllers/ComprasController.php';

$controller = new ComprasController();
$compras = $controller->getCompras();
?>


<link rel="stylesheet" href="/TCC/public/css/reset.css">
<link rel="stylesheet" href="/TCC/public/css/compras.css">
<link rel="stylesheet" href="/TCC/public/css/sidebar.css">

<div class="all">

    <?php require_once __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-content">

        <h2 class="title">Entradas</h2>

        <div class="filtros-container">

            <!-- Filtro por Fornecedor -->
            <div>
                <label>Fornecedor:</label>
                <input type="text" id="filtroFornecedor" placeholder="Nome do fornecedor...">
            </div>

            <!-- Filtro por Usuário -->
            <div>
                <label>Usuário:</label>
                <input type="text" id="filtroUsuario" placeholder="Nome do usuário...">
            </div>

            <!-- Filtro por Data -->
            <div>
                <label>Data (de):</label>
                <input type="date" id="dataDe">
            </div>

            <div>
                <label>Data (até):</label>
                <input type="date" id="dataAte">
            </div>

            <!-- Filtro por Valor -->
            <div>
                <label>Valor mínimo:</label>
                <input type="number" id="valorMin" step="0.01" placeholder="Ex: 10.00">
            </div>

            <div>
                <label>Valor máximo:</label>
                <input type="number" id="valorMax" step="0.01" placeholder="Ex: 500.00">
            </div>

        </div>


        <h2 class="subtitle">Lista de entradas no estoque</h2>
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
                                    <a class="btn-view" href="index.php?pagina=detalhes_compra&id=<?= $compra['id_compra'] ?>">
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
        const termoFornecedor = document.getElementById('filtroFornecedor').value.toLowerCase().trim();
        const termoUsuario = document.getElementById('filtroUsuario').value.toLowerCase().trim();

        const dataDe = document.getElementById('dataDe').value;
        const dataAte = document.getElementById('dataAte').value;

        const valorMin = parseFloat(document.getElementById('valorMin').value) || 0;
        const valorMax = parseFloat(document.getElementById('valorMax').value) || Infinity;

        const linhas = document.querySelectorAll('#tabelaCompras tr');

        linhas.forEach(tr => {
            const fornecedor = tr.children[1].textContent.toLowerCase();
            const usuario = tr.children[4].textContent.toLowerCase();
            const valor = parseFloat(tr.children[2].textContent.replace("R$", "").replace(".", "").replace(",", "."));
            const data = tr.children[3].textContent.split(" ")[0]; // Pega só dd/mm/yyyy

            const partes = data.split('/');
            const dataISO = `${partes[2]}-${partes[1]}-${partes[0]}`; // converte p/ yyyy-mm-dd

            let matchFornecedor = fornecedor.includes(termoFornecedor);
            let matchUsuario = usuario.includes(termoUsuario);

            let matchValor = valor >= valorMin && valor <= valorMax;

            let matchData = true;
            if (dataDe && dataISO < dataDe) matchData = false;
            if (dataAte && dataISO > dataAte) matchData = false;

            const matchTotal = matchFornecedor && matchUsuario && matchData && matchValor;

            if (matchTotal) {
                tr.classList.remove('hidden');
                setTimeout(() => tr.style.display = "", 200);
            } else {
                tr.classList.add('hidden');
                setTimeout(() => tr.style.display = "none", 200);
            }
        });
    }


    document.getElementById('filtroFornecedor').addEventListener('input', filtrarCompras);
    document.getElementById('filtroUsuario').addEventListener('input', filtrarCompras);
    document.getElementById('dataDe').addEventListener('change', filtrarCompras);
    document.getElementById('dataAte').addEventListener('change', filtrarCompras);
    document.getElementById('valorMin').addEventListener('input', filtrarCompras);
    document.getElementById('valorMax').addEventListener('input', filtrarCompras);




</script>