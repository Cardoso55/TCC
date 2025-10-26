<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Auditoria do Sistema</title>
  <link rel="stylesheet" href="/TCC/public/css/reset.css">
  <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
  <link rel="stylesheet" href="/TCC/public/css/logs.css">
</head>
<body>
  <div class="all">
    <?php include 'partials/sidebar.php'; ?>

    <main class="main-content">
      <h1 class="title">Auditoria do Sistema</h1>

      <section class="filtros">
        <h2 class="subtitle">Filtros</h2>

        <form class="filter-form">
          <div class="form-group">
            <label for="usuario">Usuário:</label>
            <select id="usuario" name="usuario">
              <option value="">Todos</option>
              <option value="Lucas">Lucas</option>
              <option value="Cardoso">Cardoso</option>
              <option value="Guilherme">Guilherme</option>
            </select>
          </div>

          <div class="form-group">
            <label for="data">Data:</label>
            <input type="date" id="data" name="data">
          </div>

          <button type="submit" class="btn-filtrar">Filtrar</button>
          <button type="reset" class="btn-limpar">Limpar</button>
        </form>
      </section>

      <section class="tabela-container">
        <h2 class="subtitle">Registros de Ações</h2>

        <table>
          <thead>
            <tr>
              <th>Usuário</th>
              <th>Data/Hora</th>
              <th>Nível</th>
              <th>Ação</th>
              <th>Detalhes</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Lucas</td>
              <td>25/10/2025 21:32</td>
              <td>Gerente</td>
              <td>Adicionou novo produto</td>
              <td><button class="detalhes-btn">Ver</button></td>
            </tr>
            <tr>
              <td>Cardoso</td>
              <td>25/10/2025 19:18</td>
              <td>Operário</td>
              <td>Atualizou checklist de saída</td>
              <td><button class="detalhes-btn">Ver</button></td>
            </tr>
            <tr>
              <td>Guilherme</td>
              <td>25/10/2025 17:41</td>
              <td>Diretor</td>
              <td>Autorizou requisição de compra</td>
              <td><button class="detalhes-btn">Ver</button></td>
            </tr>
          </tbody>
        </table>
      </section>

      <section class="tabela-container">
        <h2 class="subtitle">Histórico de Erros e Falhas de Acesso</h2>

        <table>
          <thead>
            <tr>
              <th>Usuário</th>
              <th>Data/Hora</th>
              <th>Nível</th>
              <th>Erro</th>
              <th>Detalhes</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Cardoso</td>
              <td>24/10/2025 22:01</td>
              <td>Operário</td>
              <td>Senha incorreta</td>
              <td><button class="detalhes-btn">Ver</button></td>
            </tr>
            <tr>
              <td>Lucas</td>
              <td>23/10/2025 09:12</td>
              <td>Gerente</td>
              <td>Erro de autenticação</td>
              <td><button class="detalhes-btn">Ver</button></td>
            </tr>
          </tbody>
        </table>
      </section>
    </main>
  </div>
</body>
</html>
