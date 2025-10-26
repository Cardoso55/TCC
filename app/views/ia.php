<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Previsões</title>
  <link rel="stylesheet" href="/TCC/public/css/reset.css">
  <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
  <link rel="stylesheet" href="/TCC/public/css/ia.css">
</head>
<body>
  <div class="all">
    <?php include 'partials/sidebar.php'; ?>

    <main class="main-content">
      <h1 class="title">Previsões</h1>

      <!-- Barra de filtro de período -->
      <section class="barra-filtro">
        <div class="barra-container">
          <label for="periodo">Período:</label>
          <div class="filter-buttons">
            <button class="filter-btn active">Anual</button>
            <button class="filter-btn">Mensal</button>
            <button class="filter-btn">Semanal</button>
          </div>
        </div>
      </section>

      <!-- Cards de previsão -->
      <section class="cards-container">
        <!-- Previsões de sazonalidade -->
        <div class="card previsao-sazonalidade">
          <h3>Previsões de Sazonalidade</h3>
          <p>Feriado mais próximo: <strong>01/11/2025</strong></p>
          <ul>
            <li>Produto A</li>
            <li>Produto B</li>
            <li>Produto C</li>
          </ul>
        </div>

        <!-- Tendência de vendas -->
        <div class="card tendencia-vendas">
          <h3>Tendência a ser Vendido</h3>
          <ul>
            <li>Produto A</li>
            <li>Produto B</li>
            <li>Produto C</li>
          </ul>
        </div>

        <!-- Produtos mais procurados -->
        <div class="card produtos-procurados">
          <h3>Produtos Mais Procurados</h3>
          <ul>
            <li>Produto A - ↑10%</li>
            <li>Produto B - ↑7%</li>
            <li>Produto C - ↑5%</li>
          </ul>
        </div>

        <!-- Faturamento estimado (gráfico pizza) -->
        <div class="card faturamento">
          <h3>Faturamento Estimado</h3>
          <canvas id="graficoFaturamento"></canvas>
        </div>

        <!-- Menos vendidos (gráfico cartesiano) -->
        <div class="card menos-vendidos">
          <h3>Produtos Menos Vendidos</h3>
          <canvas id="graficoMenosVendidos"></canvas>
        </div>
      </section>
    </main>
  </div>

  <!-- Scripts para gráficos (Chart.js) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    // Gráfico de Faturamento (Pizza)
    const ctxFaturamento = document.getElementById('graficoFaturamento').getContext('2d');
    new Chart(ctxFaturamento, {
      type: 'pie',
      data: {
        labels: ['Produto A', 'Produto B', 'Produto C'],
        datasets: [{
          data: [30, 45, 25],
          backgroundColor: ['#e63946', '#457b9d', '#f4a261'],
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });

    // Gráfico de Menos Vendidos (Barra)
    const ctxMenosVendidos = document.getElementById('graficoMenosVendidos').getContext('2d');
    new Chart(ctxMenosVendidos, {
      type: 'bar',
      data: {
        labels: ['Produto X', 'Produto Y', 'Produto Z'],
        datasets: [{
          label: 'Quantidade vendida',
          data: [5, 8, 3],
          backgroundColor: '#1d3557'
        }]
      },
      options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>
</body>
</html>
