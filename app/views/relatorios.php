<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();

require_once __DIR__ . '/../controllers/RelatorioController.php';
require_once __DIR__ . '/../models/ProdutoModel.php';
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/VendasModel.php';

// Dados
$compras = RelatorioController::getCompras();
$vendas = RelatorioController::getVendas();
$financeiro = RelatorioController::getFinanceiro();

$topProdutos = $vendas['por_produto'];
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Relatórios</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/relatorios.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="all">
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <div class="main-content">
            <h2 class="title">Relatórios do Sistema</h2>

            <form method="GET" action="">
                <div>
                    <input type="hidden" name="pagina" value="relatorios">

                    <label>Data inicial:</label>
                    <input type="date" name="inicio">

                    <label>Data final:</label>
                    <input type="date" name="fim">

                    <button type="submit">Filtrar</button>
                </div>
                <div class="quick-links">
                    <a href="index.php?pagina=relatorios&range=7">Últimos 7 dias</a>
                    <a href="index.php?pagina=relatorios&range=30">Últimos 30 dias</a>
                    <a href="index.php?pagina=relatorios&range=365">Últimos 365 dias</a>
                </div>
            </form>
            

            <div class="tabs">
                <button class="tab-btn active" data-tab="compras">Entradas</button>
                <button class="tab-btn" data-tab="vendas">Saídas</button>
                <button class="tab-btn" data-tab="financeiro">Financeiro</button>
            </div>

            <!-- ============================ COMPRAS ============================= -->
            <div class="tab-content active" id="compras">
                <div class="infos-relatorios">  
                    <h2 class="subtitle">Resumo das Compras</h2>

                    <p><strong>Total gasto:</strong>
                        R$ <?= number_format($compras['total_gasto'], 2, ',', '.') ?></p>

                    <p><strong>Quantidade total comprada:</strong>
                        <?= $compras['quantidade_total'] ?></p>

                    <p><strong>Preço médio geral:</strong>
                        R$ <?= number_format($compras['preco_medio_geral'], 2, ',', '.') ?></p>
                </div>
                <div class="grafico">
                    <h3>Gráfico – Total gasto por produto</h3>
                    <canvas id="graficoCompras" style="max-width: 600px;"></canvas>
                </div>
                <h3>Detalhamento por produto</h3>

                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Preço médio</th>
                            <th>Total gasto</th>
                            <th>Última compra</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($compras['produtos'] as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nome']) ?></td>
                                <td><?= $p['quantidade_total'] ?></td>
                                <td>R$ <?= number_format($p['preco_medio'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($p['total_gasto'], 2, ',', '.') ?></td>
                                <td><?= $p['ultima_compra'] ?: '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <a href="index.php?pagina=pdf_compras" class="btn-download">Baixar PDF Compras</a>
            </div>

            <!-- ============================ VENDAS =============================== -->
            <div class="tab-content" id="vendas">
                <div class="infos-relatorios">
                    <h2 class="subtitle">Resumo das Vendas</h2>

                    <p><strong>Receita total:</strong>
                        R$ <?= number_format($vendas['receita_total'], 2, ',', '.') ?></p>

                    <p><strong>Quantidade total vendida:</strong>
                        <?= $vendas['quantidade_total'] ?></p>

                    <p><strong>Ticket médio:</strong>
                        R$ <?= number_format($vendas['ticket_medio'], 2, ',', '.') ?></p>
                </div>
                <div class="grafico">                
                    <h3>Gráfico – Receita por produto</h3>
                    <canvas id="graficoVendas" style="max-width: 600px;"></canvas>
                </div>
                <h3>Produtos mais vendidos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Preço Unitário</th>
                            <th>Receita</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($topProdutos as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['nome']) ?></td>
                                <td><?= number_format($item['total_vendido'], 0, ',', '.') ?></td>
                                <td>R$ <?= number_format($item['preco_unitario_atual'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($item['receita'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
                <a href="index.php?pagina=pdf_vendas" class="btn-download">Baixar PDF Vendas</a>
            </div>

            <!-- ============================ FINANCEIRO =============================== -->
            <div class="tab-content" id="financeiro">

                <div class="infos-relatorios">                
                    <h2 class="subtitle">Resumo Geral</h2>
                    <p><strong>Receita total:</strong>
                        R$ <?= number_format($financeiro['receita_total'], 2, ',', '.') ?></p>

                    <p><strong>Custo total:</strong>
                        R$ <?= number_format($financeiro['custo_total'], 2, ',', '.') ?></p>

                    <p><strong>Lucro real total:</strong>
                        R$ <?= number_format($financeiro['lucro_real_total'], 2, ',', '.') ?></p>

                    <p><strong>Valor do estoque parado:</strong>
                        R$ <?= number_format($financeiro['valor_estoque_parado'], 2, ',', '.') ?></p>

                    <small>* O valor do estoque parado representa dinheiro investido que ainda não virou lucro.</small>
                </div>
                <h2>Relatório Financeiro</h2>
                <div class="grafico">               
                    <h3>Gráfico – Receita x Custo x Lucro</h3>
                    <canvas id="graficoFinanceiro" style="max-width: 700px;"></canvas>
                </div>            
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Receita</th>
                            <th>Custo</th>
                            <th>Lucro</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($financeiro['produtos'] as $f): ?>
                            <tr>
                                <td><?= htmlspecialchars($f['nome']) ?></td>
                                <td>R$ <?= number_format($f['receita'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($f['custo'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($f['lucro'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="index.php?pagina=pdf_financeiro" class="btn-download">Baixar PDF Financeiro</a>
                    
            </div>

        </div>
    </div>


    <script>
        const tabs = document.querySelectorAll('.tab-btn');
        const contents = document.querySelectorAll('.tab-content');

        tabs.forEach(btn => {
            btn.addEventListener('click', () => {
                tabs.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                contents.forEach(c => c.classList.remove('active'));
                document.getElementById(btn.dataset.tab).classList.add('active');
            });
        });
    </script>

    <!-- ===================== GRÁFICOS DINÂMICOS ===================== -->

    <script>
        // COMPRAS
        const comprasData = <?= json_encode($compras['produtos']) ?>;
        new Chart(document.getElementById("graficoCompras"), {
            type: "bar",
            data: {
                labels: comprasData.map(p => p.nome),
                datasets: [{
                    label: "Total gasto (R$)",
                    data: comprasData.map(p => Number(p.total_gasto)),
                    backgroundColor: "rgba(255, 99, 132, 0.6)"
                }]
            }
        });

        // VENDAS
        const vendasData = <?= json_encode($vendas['por_produto']) ?>;
        new Chart(document.getElementById("graficoVendas"), {
            type: "bar",
            data: {
                labels: vendasData.map(v => v.nome),
                datasets: [{
                    label: "Receita (R$)",
                    data: vendasData.map(v => Number(v.receita)),
                    backgroundColor: "rgba(54, 162, 235, 0.6)"
                }]
            }
        });

        // FINANCEIRO
        const finData = <?= json_encode($financeiro['produtos']) ?>;
        new Chart(document.getElementById("graficoFinanceiro"), {
            type: "bar",
            data: {
                labels: finData.map(f => f.nome),
                datasets: [
                    {
                        label: "Receita",
                        data: finData.map(f => Number(f.receita)),
                        backgroundColor: "rgba(75,192,192,0.6)"
                    },
                    {
                        label: "Custo",
                        data: finData.map(f => Number(f.custo)),
                        backgroundColor: "rgba(255,206,86,0.6)"
                    },
                    {
                        label: "Lucro",
                        data: finData.map(f => Number(f.lucro)),
                        backgroundColor: "rgba(153,102,255,0.6)"
                    }
                ]
            }
        });
    </script>

</body>

</html>