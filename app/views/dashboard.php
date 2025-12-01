<?php
require_once __DIR__ . "/../models/PrevisoesModel.php";
require_once __DIR__ . "/../models/ComparacoesModel.php";

$model = new PrevisoesModel();

// Pega a última previsão por produto e tipo
$previsoes = $model->getUltimasPrevisoes();

// Ordenar previsões em ordem específica
$ordem = ['diario' => 1, 'semanal' => 2, 'mensal' => 3];

usort($previsoes, function ($a, $b) use ($ordem) {

    // 1️⃣ Ordena primeiro pelo nome do produto
    $cmp = strcmp($a['produto'], $b['produto']);
    if ($cmp !== 0)
        return $cmp;

    // 2️⃣ Se o produto for igual, ordena pelo tipo na ordem desejada
    return $ordem[$a['tipo_previsao']] <=> $ordem[$b['tipo_previsao']];
});



$cmp = new ComparacoesModel();
$comparacoes = $cmp->compararTudo();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/dashboard.css">
</head>

<body>
    <div class="all">
        <?php include 'partials/sidebar.php'; ?>
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 class="title">Dashboard</h2>
                <div class="buttons-group">
                    <form action="/TCC/app/controllers/ExecutarPrevisaoController.php" method="POST">
                        <button type="submit" class="button-blue" onmouseover="this.style.opacity='0.85'"
                            onmouseout="this.style.opacity='1'">Gerar Previsões Agora</button>
                    </form>


                    <!-- Botão IA fora do form -->
                    <button id="executar-ia-btn" class="gerar-btn button-blue" onmouseover="this.style.opacity='0.85'"
                        onmouseout="this.style.opacity='1'">Verificar Alertas e Recomendações</button>

                </div>
            </div>
            <?php if (isset($_GET['previsao_ok'])): ?>
                <div style="
                padding: 12px;
                margin-bottom: 20px;
                background: #4CAF50;
                color: white;
                border-radius: 6px;
                text-align: center;
            ">
                    ✔️ Previsões geradas com sucesso!
                </div>
            <?php endif; ?>
            <?php
            require_once __DIR__ . "/../models/IARecommendationsModel.php";
            $iaModel = new IARecommendationsModel();
            $recomendacoes = $iaModel->getRecomendacoesNaoVistas();
            ?>

            <h2 class="subtitle">Recomendações da IA</h2>

            <?php if (empty($recomendacoes)): ?>
                <div class="card"
                    style="padding:20px; margin:20px; text-align:center; background:#f8f9ff; border:1px solid #d9dfff; border-radius:10px;">
                    <strong>Gere recomendações no botão</strong>
                </div>
            <?php else: ?>
                <div class="cards-alerts" id="recomendacoes-ia">
                    <?php foreach ($recomendacoes as $r): ?>
                        <div class="card-alert" data-id="<?= $r['id'] ?>">
                            <h4><?= $r['nome_produto'] ?></h4>
                            <p><?= $r['recomendacao'] ?></p>

                            <button class="marcar-visto-btn"><i class="fa-solid fa-check" style="margin-right: 5px;"></i>Marcar
                                como visto</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p id="msg-vazio" style="
                    display:none;
                    padding:20px;
                    background:#e8f5e9;
                    color:#2e7d32;
                    border-radius:8px;
                    text-align:center;
                    font-weight:bold;
                    margin:20px;
                ">Todas as recomendações foram visualizadas!</p>
            <?php endif; ?>


            <!-- GRÁFICO -->
            <div class="graphics">
                <div class="graphic">
                    <canvas id="graficoPrevisoes"></canvas>
                </div>
            </div>

            <!-- CARDS RESUMO AGRUPADOS -->
            <h2 class="subtitle">Previsões</h2>
            <?php if (empty($previsoes)): ?>
                <div class="card"
                    style="padding:20px; margin:20px; text-align:center; background:#f8f9ff; border:1px solid #d9dfff; border-radius:10px;">
                    <strong>Gere previsões no botão</strong>
                </div>
            <?php else: ?>
                <?php
                $grupos = [
                    "diario" => [],
                    "semanal" => [],
                    "mensal" => []
                ];

                foreach ($previsoes as $p) {
                    $grupos[$p["tipo_previsao"]][] = $p;
                }
                $tipoFormatado = [
                    'diario'  => 'Diário',
                    'semanal' => 'Semanal',
                    'mensal'  => 'Mensal'
                ];

                ?>



                <?php foreach ($grupos as $tipo => $lista): ?>
                    <?php if (!empty($lista)): ?>

                        <h3 class="subtitle" style="margin-top:20px;">
                            <?= ucfirst($tipo) === "Diario" ? "Diário" : ucfirst($tipo) ?>
                        </h3>

                        <div class="cards">
                            <?php foreach ($lista as $p): ?>
                                <div class="card">
                                    <h4><?= $p['produto'] ?></h4>
                                    <p><strong>Tipo:</strong> <?= $tipoFormatado[$p['tipo_previsao']] ?></p>
                                    <p><strong>Previsão:</strong> <?= number_format($p['previsao_quantidade'], 0, ',', '.') ?></p>
                                    <p><strong>Data:</strong> <?= date("d/m/Y", strtotime($p['data_previsao'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>


            <!-- TABELA -->
            <table>
                <?php if (!empty($previsoes)): ?>


                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Previsão</th>
                            <th>Data Prevista</th>
                            <th>Gerada em</th>
                        </tr>
                    </thead>
                <?php endif; ?>

                <tbody>
                    <?php if (!empty($previsoes)): ?>
                        <?php $tipoFormatado = [
                            'diario' => 'Diário',
                            'semanal' => 'Semanal',
                            'mensal' => 'Mensal'
                        ];
                        foreach ($previsoes as $p): ?>
                            <tr>
                                <td><?= $p['produto'] ?></td>
                                <td><?= $tipoFormatado[$p['tipo_previsao']] ?? $p['tipo_previsao'] ?></td>
                                <td><?= number_format($p['previsao_quantidade'], 0, ',', '.') ?></td>
                                <td><?= date("d/m/Y", strtotime($p['data_previsao'])) ?></td>
                                <td><?= date("d/m/Y H:i", strtotime($p['criado_em'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
    <script>
        const comparacoes = {
            amanha: {
                previsto: <?= $comparacoes['amanha']['previsto'] ?>,
                real: <?= $comparacoes['amanha']['real'] ?>,
            },
            semana: {
                previsto: <?= $comparacoes['semana']['previsto'] ?>,
                real: <?= $comparacoes['semana']['real'] ?>,
            },
            mes: {
                previsto: <?= $comparacoes['mes']['previsto'] ?>,
                real: <?= $comparacoes['mes']['real'] ?>,
            }
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Armazena o total em DINHEIRO
        const totalValor = { diario: 0, semanal: 0, mensal: 0 };

        <?php foreach ($previsoes as $p):
            $tipo = $p['tipo_previsao'];
            $qtd = floatval($p['previsao_quantidade']);
            $preco = floatval($p['preco_unitario'] ?? $p['preco'] ?? 0);
            ?>
            totalValor["<?= $tipo ?>"] += <?= $qtd ?> * <?= $preco ?>;
        <?php endforeach; ?>


        const previstoData = [
            comparacoes.amanha.previsto,
            comparacoes.semana.previsto,
            comparacoes.mes.previsto
        ];

        const realData = [
            comparacoes.amanha.real,
            comparacoes.semana.real,
            comparacoes.mes.real
        ];


        // Formatação moeda BR
        const formatBRL = v => v.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });

        new Chart(document.getElementById("graficoPrevisoes"), {
            type: 'bar',
            data: {
                datasets: [
                    {
                        label: 'Real (R$)',
                        data: realData,
                        backgroundColor: '#4CAF50',
                        xAxisID: 'xReal'
                    },
                    {
                        label: 'Previsto (R$)',
                        data: previstoData,
                        backgroundColor: '#6b8bff',
                        xAxisID: 'xPrevisto'
                    }
                ]
            },
            options: {
                scales: {
                    xPrevisto: {
                        type: 'category',
                        labels: ["Amanhã", "Próximos 7 dias", "Próximos 30 dias"],
                        offset: true
                    },
                    xReal: {
                        type: 'category',
                        labels: ["Ontem", "Decorrer da Semana", "Decorrer do Mês"],
                        offset: true,
                        display: false
                    },
                    y: {
                        beginAtZero: true
                    }
                }

            }
        });

    </script>

    <script>
        document.getElementById('executar-ia-btn').addEventListener('click', async (e) => {
            e.preventDefault(); // evita submit do form

            if (!confirm("Deseja executar a IA agora?")) return;

            try {
                const resp = await fetch('/TCC/app/controllers/executar_ia_dashboard.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: "executar=1" // pode ser qualquer flag pra indicar que deve rodar
                });
                const texto = await resp.text();

                alert(texto);

                // Atualiza alertas e dados se quiser
                setTimeout(() => location.reload(), 800);
            } catch (err) {
                console.error(err);
                alert("Erro ao executar a IA.");
            }
        });

    </script>
    <script>
        document.querySelectorAll('.marcar-visto-btn').forEach(btn => {
            btn.addEventListener('click', async () => {

                const id = btn.closest('.card-alert').dataset.id;

                try {
                    const resp = await fetch('/TCC/app/controllers/marcar_visto.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `id=${id}`
                    });

                    const text = await resp.text();

                    if (text.trim() === 'ok') {

                        // remove o card
                        document.querySelector(`.card-alert[data-id="${id}"]`).remove();

                        // ================================
                        // 📌 VERIFICAR SE ACABOU TUDO
                        // ================================
                        const totalRestante = document.querySelectorAll('.card-alert').length;

                        if (totalRestante === 0) {
                            document.getElementById('msg-vazio').style.display = 'block';
                        }

                    } else {
                        alert('Erro ao marcar como visto!');
                    }

                } catch (e) {
                    console.error(e);
                    alert('Erro na requisição.');
                }
            });
        });



    </script>


</body>

</html>