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
  <link rel="stylesheet" href="/TCC/public/css/reposicoes.css">
</head>
<body>
<div class="all">
    <?php include 'partials/sidebar.php'; ?>

    <main class="main-content">
      <h1 class="title">Saídas de Produtos</h1>
      <section class="tabela-container">
        <h2 class="subtitle">Pedidos em Aberto</h2>

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
                    <?= ucfirst(str_replace('_','-',$p['status'] ?? '')) ?>
                </span>
            </td>
            <td>
                <?php if (in_array($userLevel, ['supervisor','gerente','diretor']) && $p['status'] === 'pendente'): ?>
                    <button class="action-btn" data-id="<?= $p['id_pedido_saida'] ?>" data-acao="aprovar">Aprovar</button>
                    <button class="action-btn" data-id="<?= $p['id_pedido_saida'] ?>" data-acao="recusar">Recusar</button>
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
document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', function() {
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

</body>
</html>
