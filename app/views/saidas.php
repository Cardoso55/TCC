<?php
require_once __DIR__ . '/../controllers/VendasController.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userLevel = $_SESSION['user_level'] ?? 'operario';

// cria instância do controller e pega as saídas
$saidasCtrl = new VendasController();
$saidas = $saidasCtrl->listarSaidas();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saídas de Produtos</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/saidas.css">
</head>

<body>
    <div class="all">
        <?php include 'partials/sidebar.php'; ?>

        <div class="main-content">
            <h2 class="title">Saídas</h2>
            <div class="filtros-container">
                <div class="filtro-item">
                    <label>Solicitante</label>
                    <input type="text" id="filtroSolicitante" placeholder="Buscar por solicitante...">
                </div>

                <div class="filtro-item">
                    <label>Produto</label>
                    <input type="text" id="filtroProduto" placeholder="Buscar por produto...">
                </div>

                <div class="filtro-item">
                    <label>Status</label>
                    <select id="filtroStatus">
                        <option value="">Todos</option>
                        <option value="pendente">Pendente</option>
                        <option value="aprovado">Aprovado</option>
                        <option value="a-caminho">A Caminho</option>
                        <option value="recusado">Recusado</option>
                    </select>
                </div>

                <div class="filtro-item">
                    <label>Data Inicial</label>
                    <input type="date" id="filtroDataInicio">
                </div>

                <div class="filtro-item">
                    <label>Data Final</label>
                    <input type="date" id="filtroDataFim">
                </div>
            </div>
            <h2 class="subtitle">Pedidos das filiais</h2>
            <section class="tabela-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Solicitante</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Observação</th>
                            <th>Data do Pedido</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($saidas)): ?>
                            <?php foreach ($saidas as $p): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['id_pedido_saida']) ?></td>
                                    <td><?= htmlspecialchars($p['usuario_nome'] ?? 'Desconhecido') ?></td>
                                    <td><?= htmlspecialchars($p['produto_nome'] ?? 'Desconhecido') ?></td>
                                    <td><?= htmlspecialchars($p['quantidade']) ?></td>
                                    <td><?= htmlspecialchars($p['observacao'] ?? '') ?></td>
                                    <td><?= date("d/m/Y H:i", strtotime($p['data_pedido'])) ?></td>
                                    <td>
                                        <span class="status <?= htmlspecialchars($p['status'] ?? '') ?>">
                                            <?= ucfirst(str_replace('_', '-', $p['status'] ?? '')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (in_array($userLevel, ['supervisor', 'gerente', 'diretor']) && $p['status'] === 'pendente'): ?>
                                            <button class="action-btn" data-id="<?= $p['id_pedido_saida'] ?>"
                                                data-acao="aprovar">Aprovar</button>
                                            <button class="action-btn" data-id="<?= $p['id_pedido_saida'] ?>"
                                                data-acao="recusar">Recusar</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">Nenhum pedido encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>
    <script>
        document.querySelectorAll('.action-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                const acao = this.dataset.acao;

                fetch(`?pagina=${acao}_saida`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id_pedido_saida=${id}`
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.sucesso) {
                            alert(`Pedido ${acao === 'aprovar' ? 'aprovado (a-caminho)' : 'negado'} com sucesso!`);
                            location.reload();
                        } else {
                            alert(`Erro: ${data.erro || 'Não foi possível processar.'}`);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Erro na requisição.');
                    });
            });
        });
    </script>
    <script>
        const linhas = document.querySelectorAll("tbody tr");

        function aplicarFiltros() {
            const txtSolicitante = document.getElementById("filtroSolicitante").value.toLowerCase();
            const txtProduto = document.getElementById("filtroProduto").value.toLowerCase();
            const status = document.getElementById("filtroStatus").value;
            const dataInicio = document.getElementById("filtroDataInicio").value;
            const dataFim = document.getElementById("filtroDataFim").value;

            linhas.forEach(linha => {
                const solicitante = linha.children[1].innerText.toLowerCase();
                const produto = linha.children[2].innerText.toLowerCase();
                const statusTxt = linha.children[6].innerText.toLowerCase();
                const dataPedido = linha.children[5].innerText.split(' ')[0].split('/').reverse().join('-');

                let mostrar = true;

                if (!solicitante.includes(txtSolicitante)) mostrar = false;
                if (!produto.includes(txtProduto)) mostrar = false;
                if (status !== "" && !statusTxt.includes(status)) mostrar = false;

                if (dataInicio && dataPedido < dataInicio) mostrar = false;
                if (dataFim && dataPedido > dataFim) mostrar = false;

                linha.style.display = mostrar ? "" : "none";
            });
        }

        document.getElementById("filtroSolicitante").addEventListener("input", aplicarFiltros);
        document.getElementById("filtroProduto").addEventListener("input", aplicarFiltros);
        document.getElementById("filtroStatus").addEventListener("change", aplicarFiltros);
        document.getElementById("filtroDataInicio").addEventListener("change", aplicarFiltros);
        document.getElementById("filtroDataFim").addEventListener("change", aplicarFiltros);

        document.getElementById("btnLimparFiltros").addEventListener("click", () => {
        document.getElementById("filtroSolicitante").value = "";
        document.getElementById("filtroProduto").value = "";
        document.getElementById("filtroStatus").value = "";
        document.getElementById("filtroDataInicio").value = "";
        document.getElementById("filtroDataFim").value = "";
        aplicarFiltros();
    });
</script>


</body>

</html>