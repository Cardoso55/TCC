<?php
require_once __DIR__ ."/../database/conexao.php";
require_once __DIR__ . '/../controllers/RequisicaoController.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
global $conn;
$conn = conectarBanco();

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
  <style>
    .gerar-btn {
      background: linear-gradient(135deg, #4c79ff, #6b8bff);
      color: white;
      padding: 12px 22px;
      border: none;
      border-radius: 12px;
      cursor: pointer;
      font-size: 16px;
      margin-bottom: 20px;
      transition: 0.2s ease-in-out;
    }

    .gerar-btn:hover {
      opacity: 0.85;
      transform: scale(1.03);
    }
  </style>
</head>
<body>



  <div class="all">

    <?php include 'partials/sidebar.php'; ?>

    <main class="main-content">

      <h1 class="title">Pedidos de Reposição</h1>
      <button id="gerar-decisoes-btn" class="gerar-btn">
        🔮 Gerar Decisões da IA
      </button>


      <section class="tabela-container">
        <h2 class="subtitle">Pedidos em Aberto</h2>

        <table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Solicitante</th>
      <th>Solicitação em</th>
      <th>Produto</th>
      <th>Quantidade a Repor</th>
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
        <td><?= htmlspecialchars($p['quantidade_pedida'] ?? 0) ?></td>
        <td>
          <span class="status <?= htmlspecialchars($p['status'] ?? 'nao-definido') ?>">
              <?= ucfirst($p['status'] ?? 'N/A') ?>
          </span>
        </td>

        <!-- nova coluna "Gerado pela IA" -->
        <td>
          <?= $p['gerado_por_ia'] == 1 ? 'Sim' : 'Não' ?>
        </td>

       <td>
        <?php if ($p['status'] === 'pendente' || $p['status'] === 'pendente_ia'): ?>
            <button class="check-btn" data-id="<?= $p['id_pedido'] ?>" data-acao="aceitar">Confirmar</button>
            <button class="deny-btn" data-id="<?= $p['id_pedido'] ?>" data-acao="negar">Recusar</button>
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

    </main>
  </div>
    <script>
document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.check-btn, .deny-btn');
    if (!btn) return;

    const tr = btn.closest('tr');
    if (!tr) return;

    const idPedido = btn.dataset.id;
    const acao = btn.dataset.acao;

    const confirma = confirm(`Tem certeza que deseja ${acao === 'aceitar' ? 'aceitar' : 'negar'} este pedido?`);
    if (!confirma) return;

    const formData = new FormData();
    formData.append('acao', acao);
    formData.append('id_pedido', idPedido);

    try {
        const resp = await fetch('/TCC/app/controllers/PedidoAcaoController.php', {
            method: 'POST',
            body: formData
        });

        const msg = await resp.text();
        alert(msg);

        // atualiza status e desabilita botões
        const statusSpan = tr.querySelector('.status');
        if (statusSpan) {
            statusSpan.textContent = acao === 'aceitar' ? 'A caminho' : 'Negado';
            statusSpan.className = 'status ' + (acao === 'aceitar' ? 'a-caminho' : 'negado');
        }

        // oculta os botões
        tr.querySelectorAll('.check-btn, .deny-btn').forEach(b => b.style.display = 'none');

    } catch (err) {
        console.error(err);
        alert('Ocorreu um erro ao processar a ação.');
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

</body>
</html>
