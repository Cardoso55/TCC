<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório de Estoque</title>
  <link rel="stylesheet" href="/TCC/public/css/reset.css">
  <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
  <link rel="stylesheet" href="/TCC/public/css/relatorios.css">
</head>
<body>
    <div class="all">
        <?php
            include 'partials/sidebar.php'; 
        ?>
        <div class="main-content">
            <h2 class="title">Relatório de Estoque</h2>
            <section class="report-container">
                

                <div class="tabs">
                <button class="tab on">Entradas</button>
                <button class="tab">Saídas</button>
                <button class="tab">Resumo</button>
                </div>

                <div class="report-box">
                <div class="resume-top">
                    <div class="info">
                    <h3>Entradas</h3>
                    <p><strong>R$ 0.00</strong></p>
                    </div>
                    <div class="info">
                    <h3>Saídas</h3>
                    <p><strong>R$ 0.00</strong></p>
                    </div>
                    <div class="info">
                    <h3>Saldo Atual</h3>
                    <p><strong>R$ 0.00</strong></p>
                    </div>
                    <div class="info">
                    <h3>Produtos no Estoque</h3>
                    <p><strong>0</strong></p>
                    </div>
                </div>

                <div class="graphic-placeholder">
                    <p>📊 Espaço reservado para o gráfico de movimentação de estoque</p>
                </div>

                <div class="table-content">
                    <h3>Últimas Movimentações</h3>
                    <table>
                    <thead>
                        <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Responsável</th>
                        <th>Origem/Destino</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                        <td>00/00/2025</td>
                        <td>Entrada</td>
                        <td>Produto A</td>
                        <td>50</td>
                        <td>João</td>
                        <td>Fornecedor XPTO</td>
                        </tr>
                        <tr>
                        <td>00/00/2025</td>
                        <td>Saída</td>
                        <td>Produto B</td>
                        <td>20</td>
                        <td>Maria</td>
                        <td>Loja Central</td>
                        </tr>
                        <tr>
                        <td>00/00/2025</td>
                        <td>Entrada</td>
                        <td>Produto C</td>
                        <td>100</td>
                        <td>Carlos</td>
                        <td>Fornecedor ABC</td>
                        </tr>
                    </tbody>
                    </table>
                </div>

                <div class="export-btn">
                    <button class="btn-pdf">📄 Exportar PDF</button>
                    <button class="btn-excel">📊 Exportar Excel</button>
                </div>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
