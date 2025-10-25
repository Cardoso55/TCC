<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitações</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/solicitacoes.css">
</head>
<body>
    <div class="all">
        <?php
            include 'partials/sidebar.php'; 
        ?>
        <div class="main-content">
            <h2 class="title">Solicitações e Aprovações</h2>
            <div class="count">
                <div class="count-card pending">
                    <div class="number">20</div>
                    <h2 class="card-title">Pendentes</h2>
                </div>
                <div class="count-card approved">
                    <div class="number">50</div>
                    <h2 class="card-title">Aprovadas</h2>
                </div>
                <div class="count-card denied">
                    <div class="number">6</div>
                    <h2 class="card-title">Negadas</h2>
                </div>
            </div>

            <h2 class="subtitle">Histórico de solicitações</h2>

            <div class="request-history">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Solicitante</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>2024-06-01</td>
                            <td>João Silva</td>
                            <td>Alterar quantidade de detergente</td>
                            <td><span class="status pendente">Pendente</span></td>
                            <td><button class="view-btn">Ver Detalhes</button></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>2024-06-02</td>
                            <td>Maria Oliveira</td>
                            <td>Adicionar novo produto ao estoque</td>
                            <td><span class="status aprovado">Aprovada</span></td>
                            <td><button class="view-btn">Ver Detalhes</button></td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>2024-06-03</td>
                            <td>Carlos Pereira</td>
                            <td>Remover produto vencido</td>
                            <td><span class="status negado">Negada</span></td>
                            <td><button class="view-btn">Ver Detalhes</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
</html>
