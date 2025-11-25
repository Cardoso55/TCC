<?php
require_once __DIR__ . "/../models/PrevisoesModel.php";


$model = new PrevisoesModel();

// Pega a última previsão por produto e tipo
$previsoes = $model->getUltimasPrevisoes(); 
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Master</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/dashboard.css">
    <style>
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .cards .card {
            padding: 15px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: center;
        }
        th { background: #4c79ff; color: #fff; }
        tr:nth-child(even) { background: #f5f5f5; }
        tr:hover { background: #cce0ff; }
        .graphics { margin-bottom: 30px; }
        .graphic { background: #fff; padding: 15px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
<div class="all">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">
        <h2 class="title">Dashboard Master</h2>
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

        <div style="margin-bottom: 20px;">
        <form action="/TCC/app/controllers/ExecutarPrevisaoController.php" method="POST">
            <button type="submit" 
                style="
                    padding: 12px 20px;
                    background: linear-gradient(135deg, #4c79ff, #6b8bff);
                    border: none;
                    color: white;
                    font-size: 16px;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: 0.3s;
                "
                onmouseover="this.style.opacity='0.85'"
                onmouseout="this.style.opacity='1'">
                🔮 Gerar Previsões Agora
            </button>
        </form>


        <!-- Botão IA fora do form -->
        <button id="executar-ia-btn" class="gerar-btn" style="
                padding: 12px 20px;
                background: linear-gradient(135deg, #4c79ff, #6b8bff);
                border: none;
                color: white;
                font-size: 16px;
                border-radius: 8px;
                cursor: pointer;
                transition: 0.3s;
            "
            onmouseover="this.style.opacity='0.85'"
            onmouseout="this.style.opacity='1'">🤖 Executar IA</button>


    </div>

            <?php
            require_once __DIR__ . "/../models/IARecommendationsModel.php";
            $iaModel = new IARecommendationsModel();
            $recomendacoes = $iaModel->getRecomendacoesNaoVistas();
            ?>

            <?php if(!empty($recomendacoes)): ?>
                <h2>Recomendações Insanas da IA</h2>
                <div class="cards" id="recomendacoes-ia">
                    <?php foreach ($recomendacoes as $r): ?>
                        <div class="card" id="rec-<?= $r['id'] ?>" style="background: linear-gradient(135deg, #ff4c4c, #ff8a8a); color: white;">
                            <h4><?= $r['nome_produto'] ?> (<?= $r['codigo_produto'] ?>)</h4>
                            <p><?= $r['recomendacao'] ?></p>
                            <button class="marcar-visto-btn" data-id="<?= $r['id'] ?>" style="
                                margin-top:10px; 
                                padding:6px 12px; 
                                background:#fff; 
                                color:#ff4c4c; 
                                border:none; 
                                border-radius:6px; 
                                cursor:pointer;">
                                ✔️ Marcar como visto
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <!-- CARDS RESUMO -->
        <div class="cards">
            <?php foreach ($previsoes as $p): ?>
                <div class="card">
                    <h4><?= $p['produto'] ?></h4>
                    <p><strong>Tipo:</strong> <?= ucfirst($p['tipo_previsao']) ?></p>
                    <p><strong>Previsão:</strong> <?= number_format($p['previsao_quantidade'], 0, ',', '.') ?></p>
                    <p><strong>Data:</strong> <?= date("d/m/Y", strtotime($p['data_previsao'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- TABELA -->
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Previsão</th>
                    <th>Data Prevista</th>
                    <th>Gerada em</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($previsoes as $p): ?>
                    <tr>
                        <td><?= $p['produto'] ?></td>
                        <td><?= ucfirst($p['tipo_previsao']) ?></td>
                        <td><?= number_format($p['previsao_quantidade'], 0, ',', '.') ?></td>
                        <td><?= date("d/m/Y", strtotime($p['data_previsao'])) ?></td>
                        <td><?= date("d/m/Y H:i", strtotime($p['criado_em'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- GRÁFICO -->
        <div class="graphics">
            <div class="graphic">
                <canvas id="graficoPrevisoes"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Agrupar previsões por tipo
    const agrupado = { diario: 0, semanal: 0, mensal: 0 };
    <?php foreach ($previsoes as $p): ?>
        agrupado['<?= $p['tipo_previsao'] ?>'] += <?= $p['previsao_quantidade'] ?>;
    <?php endforeach; ?>

    const labels = Object.keys(agrupado).map(t => t.charAt(0).toUpperCase() + t.slice(1));
    const data = Object.values(agrupado);

    new Chart(document.getElementById("graficoPrevisoes"), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Previsão Total por Tipo',
                data: data,
                backgroundColor: ['#4c79ff','#6b8bff','#8faeff'],
                borderColor: ['#2f4fff','#4c79ff','#6b8bff'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Quantidade' } },
                x: { title: { display: true, text: 'Tipo de Previsão' } }
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
        const id = btn.dataset.id;
        try {
            const resp = await fetch('/TCC/app/controllers/marcar_visto.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${id}`
            });
            const text = await resp.text();
            if(text.trim() === 'ok'){
                // remove o card da tela
                document.getElementById('rec-' + id).remove();
            } else {
                alert('Erro ao marcar como visto!');
            }
        } catch(e) {
            console.error(e);
            alert('Erro na requisição.');
        }
    });
});
</script>


</body>
</html>
