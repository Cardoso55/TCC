<?php
require_once __DIR__ . '/../controllers/RequisicaoController.php';
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$userLevel = $_SESSION['user_level'] ?? 'operario';
$pedidos = RequisicaoController::listar();

?>

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
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 class="title">Pedidos de Reposição</h2>
        <button id="gerar-decisoes-btn" class="gerar-btn button-blue">
          Decisões Automáticas
        </button>
      </div>
      <div class="filtros-container">

        <div class="filtro">
          <label>Pesquisar Produto:</label>
          <input type="text" id="filtroProduto" placeholder="Ex: Caneta, Papel...">
        </div>

        <div class="filtro">
          <label>Status:</label>
          <select id="filtroStatus">
            <option value="">Todos</option>
            <option value="pendente">Pendente</option>
            <option value="confirmado">Confirmado</option>
            <option value="negado">Negado</option>
            <option value="a-caminho">A Caminho</option>
          </select>
        </div>

        <div class="filtro">
          <label>Gerado por IA:</label>
          <select id="filtroIA">
            <option value="">Todos</option>
            <option value="sim">Sim</option>
            <option value="não">Não</option>
          </select>
        </div>

        <div class="filtro">
          <label>Data início:</label>
          <input type="date" id="filtroDataInicio">
        </div>

        <div class="filtro">
          <label>Data fim:</label>
          <input type="date" id="filtroDataFim">
        </div>
      </div>

      <h2 class="subtitle">Pedidos em Aberto</h2>
      <section class="tabela-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Solicitante</th>
              <th>Solicitação em</th>
              <th>Produto</th>
              <th>Quantidade Solicitada</th>
              <th>Status</th>
              <th>Gerado pela IA</th>
              <th>Ações</th>
            </tr>
          </thead>

          <tbody>
            <?php if (!empty($pedidos)): ?>
              <?php foreach ($pedidos as $p): ?>
                <tr>
                  <td><?= htmlspecialchars($p['id_pedido']) ?></td>
                  <td>Estoque</td>
                  <td><?= date("d/m/Y H:i", strtotime($p['data_pedido'])) ?></td>
                  <td><?= htmlspecialchars($p['nome']) ?></td>
                  <td><?= htmlspecialchars($p['quantidade']) ?></td>
                  <td>
                    <span class="status <?= htmlspecialchars($p['status']) ?>">
                      <?= ucfirst($p['status']) ?>
                    </span>
                  </td>
                  <td>
                    <?= $p['gerado_por_ia'] ? 'Sim' : 'Não' ?>
                  </td>

                  <td>
                    <?php if ($userLevel === 'setor-de-compras'): ?>
                      <?php if ($p['status'] === 'pendente' || $p['status'] === 'pendente_ia'): ?>
                        <button class="check-btn" data-id="<?= $p['id_pedido'] ?>" data-acao="aceitar">Confirmar</button>
                        <button class="deny-btn" data-id="<?= $p['id_pedido'] ?>" data-acao="negar">Recusar</button>
                      <?php endif; ?>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7">Nenhum pedido encontrado.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <script>
    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('.check-btn, .deny-btn');
      if (!btn) return;

      const idPedido = btn.dataset.id;
      const acao = btn.dataset.acao;

      if (!confirm(`Tem certeza que deseja ${acao === 'aceitar' ? 'aprovar' : 'negar'} este pedido?`)) return;

      const formData = new FormData();
      formData.append('acao', acao);
      formData.append('id_pedido', idPedido);

      try {
        const resp = await fetch('/TCC/app/controllers/PedidoAcaoController.php', {
          method: 'POST',
          body: formData
        });

        const data = await resp.json();
        alert(data.sucesso || data.mensagem || data.erro || "Ação concluída.");


        if (data.sucesso) {
          location.reload();
        }

      } catch (err) {
        console.error(err);
        alert('Erro ao processar ação.');
      }
    });
  </script>
  <script>
    document.getElementById('gerar-decisoes-btn').addEventListener('click', async () => {
      if (!confirm("Deseja gerar novas decisões de reposição usando a IA?")) return;

      try {
        const resp = await fetch('/TCC/python/rerun_replenishment.php');
        const texto = await resp.text();

        alert(texto);

        // recarrega a página automaticamente
        setTimeout(() => location.reload(), 800);

      } catch (error) {
        console.error(error);
        alert("Erro ao gerar decisões da IA.");
      }
    });
  </script>
  <script>
    function filtrarTabela() {
      const produto = document.getElementById("filtroProduto").value.toLowerCase().trim();
      const status = document.getElementById("filtroStatus").value.toLowerCase();
      const ia = document.getElementById("filtroIA").value.toLowerCase();
      const dataInicio = document.getElementById("filtroDataInicio").value;
      const dataFim = document.getElementById("filtroDataFim").value;

      const linhas = document.querySelectorAll("tbody tr");

      linhas.forEach(tr => {
        const tds = tr.querySelectorAll("td");
        const solicitante = tds[1].innerText.toLowerCase();
        const dataPedido = tds[2].innerText; // dd/mm/yyyy HH:MM
        const produtoNome = tds[3].innerText.toLowerCase();
        const statusTexto = tds[5].innerText.toLowerCase();
        const iaTexto = tds[6].innerText.toLowerCase();

        let mostrar = true;

        // filtro produto
        if (produto && !produtoNome.includes(produto)) mostrar = false;

        // filtro status
        if (status && statusTexto !== status) mostrar = false;

        // filtro IA
        if (ia && iaTexto !== ia) mostrar = false;

        // converte datas
        const [dia, mes, anoHora] = dataPedido.split("/");
        const [ano, hora] = anoHora.split(" ");
        const dataObj = new Date(`${ano}-${mes}-${dia}T${hora}`);

        if (dataInicio) {
          if (dataObj < new Date(dataInicio)) mostrar = false;
        }
        if (dataFim) {
          const fim = new Date(dataFim);
          fim.setHours(23, 59, 59);
          if (dataObj > fim) mostrar = false;
        }

        // animação suave
        if (mostrar) {
          tr.classList.remove("hidden");
          setTimeout(() => tr.style.display = "", 200);
        } else {
          tr.classList.add("hidden");
          setTimeout(() => tr.style.display = "none", 200);
        }
      });
    }

    // eventos automáticos
    ["input", "change"].forEach(evt => {
      document.querySelectorAll("#filtroProduto, #filtroStatus, #filtroIA, #filtroDataInicio, #filtroDataFim")
        .forEach(el => el.addEventListener(evt, filtrarTabela));
    });

    // limpar filtros
    document.getElementById("limparFiltros").addEventListener("click", () => {
      document.getElementById("filtroProduto").value = "";
      document.getElementById("filtroStatus").value = "";
      document.getElementById("filtroIA").value = "";
      document.getElementById("filtroDataInicio").value = "";
      document.getElementById("filtroDataFim").value = "";
      filtrarTabela();
    });
  </script>


</body>

</html>