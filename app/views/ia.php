<?php
require_once __DIR__ . '/../database/conexao.php';

// Caminho do JSON de previsões gerado pela IA
$jsonPath = __DIR__ . "/../../python/previsoes_vendas.json";

// Se o JSON não existir, inicializa array vazio
$previsoes = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];
if (!is_array($previsoes))
  $previsoes = [];

$conn = conectarBanco();

function getNomeProduto($codigo, $conn)
{
  $stmt = $conn->prepare("SELECT nome FROM produtos_tbl WHERE codigo_produto = ?");
  $stmt->bind_param("s", $codigo);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    return $row['nome'];
  }
  return $codigo; // retorna o código se não achar
}

// Monta array de nomes dos produtos
$nomesProdutos = [];
foreach ($previsoes as $codigo => $info) {
  $nomesProdutos[$codigo] = isset($info['nome']) && !empty($info['nome'])
    ? $info['nome']
    : getNomeProduto($codigo, $conn);
}

// Calcula faturamento previsto usando preço unitário do JSON ou do banco
$faturamentoPrevisto = [];
foreach ($previsoes as $codigo => $info) {
  $preco = isset($info['preco_unitario']) ? $info['preco_unitario'] : 0;
  if ($preco == 0) {
    $stmt = $conn->prepare("SELECT preco_unitario FROM produtos_tbl WHERE codigo_produto = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $preco = $row ? $row['preco_unitario'] : 0;
  }

  // Multiplica pelo campo 'previsao' que vem do JSON gerado pela IA
  $faturamentoPrevisto[$codigo] = $preco * (isset($info['previsao']) ? $info['previsao'] : 0);
}

// Menos vendidos: pega 3 menores faturamentos
$menosVendidos = $faturamentoPrevisto;
asort($menosVendidos);
$menosVendidos = array_slice($menosVendidos, 0, 3, true);
?>


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
            <button class="filter-btn">Anual</button>
            <button class="filter-btn active">Mensal</button>
            <button class="filter-btn">Semanal</button>
          </div>
        </div>
      </section>

      <!-- Cards de previsão -->
      <section class="cards-container">
        <!-- Previsões de sazonalidade -->
        <?php
        $json_path = __DIR__ . '/../../python/previsoes_sazonais.json';
        $produtos = [];
        if (file_exists($json_path)) {
          $produtos = json_decode(file_get_contents($json_path), true);
        }
        ?>

        <div class="card previsao-sazonalidade">
          
          <?php
            $meses = [
                1 => "Janeiro",
                2 => "Fevereiro",
                3 => "Março",
                4 => "Abril",
                5 => "Maio",
                6 => "Junho",
                7 => "Julho",
                8 => "Agosto",
                9 => "Setembro",
                10 => "Outubro",
                11 => "Novembro",
                12 => "Dezembro"
            ];

            // exemplo de uso
            $mes_atual = (int) date("m");
            $mes_atual = $meses[$mes_atual];
            ?>
          <h3>Recomendações para <?php echo htmlspecialchars($mes_atual); ?></h3>
          <p></p>
          <ul>
            <?php foreach ($produtos as $produto): ?>
              <li><?php echo htmlspecialchars($produto['nome']); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>


        <!-- Tendência de vendas -->
        <div class="card tendencia-vendas">
          <h3>Tendência a ser Vendido</h3>
          <ul>
            <?php foreach ($previsoes as $codigo => $info): ?>
              <li style="display: flex; justify-content: space-between; align-items: center;">
                <div> <?= htmlspecialchars($nomesProdutos[$codigo]) ?> </div>
                <div style="font-weight: bold;"><?= round($info["previsao"]) ?> unidades</div>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <?php
          $arquivo = __DIR__ . '/../../python/mais_procurados.json';
          $mais_procurados = [];

          if(file_exists($arquivo)) {
              $mais_procurados = json_decode(file_get_contents($arquivo), true);
          }
        ?>

        <!-- Produtos mais procurados -->
        
        <div class="card produtos-mais-procurados">
            <h3>Produtos Mais Procurados</h3>
            <ul>
                <?php foreach($mais_procurados as $produto): ?>
                   <li style="display: flex; justify-content: space-between; align-items: center;">
                      <div><?= htmlspecialchars($produto['nome']) ?></div>
                      <div style="font-weight: bold;"><?= $produto['variacao'] ?>%</div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Faturamento estimado (gráfico pizza) -->
        <div class="card faturamento">
          <h3>Faturamento Previsto</h3>
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

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const nomesProdutos = <?= json_encode($nomesProdutos) ?>;
      const faturamentoPrevisto = <?= json_encode($faturamentoPrevisto) ?>;
      const menosVendidos = <?= json_encode($menosVendidos) ?>;

      // Gráfico Faturamento Total
      // Faturamento (torres)
      const canvasFaturamento = document.getElementById('graficoFaturamento');
      if (canvasFaturamento && Object.keys(faturamentoPrevisto).length > 0) {
        new Chart(canvasFaturamento, {
          type: 'bar', // mudou de pie para bar
          data: {
            labels: Object.keys(faturamentoPrevisto).map(c => nomesProdutos[c]),
            datasets: [{
              label: 'Faturamento Previsto',
              data: Object.values(faturamentoPrevisto),
              backgroundColor: ['#ef233c', '#4361ee', '#f77f00', '#7209b7', '#06d6a0']
            }]
          },
          options: {
            plugins: { legend: { display: false } }, // esconde legenda se quiser
            scales: { y: { beginAtZero: true } }
          }
        });
      }


      // Gráfico Menos Vendidos
      new Chart(document.getElementById('graficoMenosVendidos'), {
        type: 'bar',
        data: {
          labels: Object.keys(menosVendidos).map(codigo => nomesProdutos[codigo]),
          datasets: [{
            label: 'Faturamento Previsto',
            data: Object.values(menosVendidos),
            backgroundColor: '#1d3557'
          }]
        },
        options: { scales: { y: { beginAtZero: true } } }
      });
    });
  </script>

</body>

</html>