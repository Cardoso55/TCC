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
      <h1 class="title">Pedidos de Reposição</h1>
      <section class="tabela-container">
        <h2 class="subtitle">Pedidos em Aberto</h2>

        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Solicitante</th>
              <th>Solicitação em</th>
              <th>Produto</th>
              <th>Quantidade Atual</th>
              <th>Status</th>
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
                <td><?= htmlspecialchars($p['quantidade_atual']) ?></td>

                <td>
                  <span class="status <?= htmlspecialchars($p['status']) ?>">
                      <?= ucfirst($p['status']) ?>
                  </span>
                </td>

                <td>
                  <?php if ($p['status'] === 'pendente'
                        && isset($p['nivel_aprovacao'])
                        && $p['nivel_aprovacao'] === $userLevel): ?>
                      
                      <button class="check-btn" data-id="<?= $p['id_pedido'] ?>" data-acao="aceitar">Aprovar</button>
                      <button class="deny-btn" data-id="<?= $p['id_pedido'] ?>" data-acao="negar">Recusar</button>

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

</body>
</html>
