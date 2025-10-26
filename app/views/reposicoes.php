<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos de Reposição</title>
  <link rel="stylesheet" href="/TCC/public/css/reset.css">
  <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
  <link rel="stylesheet" href="/TCC/public/css/reposicoes.css">
</head>
<body>
  <div class="all">
    <?php include 'partials/sidebar.php'; ?>

    <main class="main-content">
      <h1 class="title">Pedidos de Reposição</h1>

      <section class="tabela-container">
        <h2 class="subtitle">Pedidos de Loja</h2>

        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Loja</th>
              <th>Data</th>
              <th>Produto</th>
              <th>Quantidade</th>
              <th>Status</th>
              <th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>001</td>
              <td>Loja Central</td>
              <td>25/10/2025</td>
              <td>Mouse Logitech</td>
              <td>20</td>
              <td><span class="status pendente">Pendente</span></td>
              <td><button class="check-btn">✔️</button></td>
            </tr>
            <tr>
              <td>002</td>
              <td>Loja Zona Sul</td>
              <td>24/10/2025</td>
              <td>Teclado Mecânico</td>
              <td>10</td>
              <td><span class="status a-caminho">A Caminho</span></td>
              <td><button class="check-btn">✔️</button></td>
            </tr>
          </tbody>
        </table>

        <button class="integrar-btn">Integrar com Estoque</button>
      </section>
    </main>
  </div>
</body>
</html>
