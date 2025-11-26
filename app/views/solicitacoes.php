<?php

require_once __DIR__ . '/../controllers/SolicitacoesController.php';

$nivelUsuario = $_SESSION['user_level'] ?? 'supervisor';

$controller = new SolicitacoesController();
$pedidos = $controller->listarSolicitacoes($nivelUsuario);

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Solicitações</title>
    <link rel="stylesheet" href="/TCC/public/css/solicitacoes.css">
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
</head>
<body>
<div class="all">

    <?php include 'partials/sidebar.php'; ?>

    <main class="main-content">

        <h1 class="title">Solicitações para aprovação <?= ucfirst($nivelUsuario) ?></h1>

        <section class="request-history">
            <table>
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Status</th>
                        <th>Data Pedido</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pedidos)): ?>
                        <?php foreach ($pedidos as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['id_pedido']) ?></td>
                                <td><?= htmlspecialchars($p['nome'] ?? 'Produto não encontrado') ?></td>
                                <td><?= htmlspecialchars($p['quantidade']) ?></td>
                                <td>
                                    <span class="status <?= htmlspecialchars($p['status'] ?? 'aguardando_aprovacao') ?>">
                                        <?= ucfirst(str_replace('_', ' ', $p['status'] ?? 'Aguardando Aprovação')) ?>
                                    </span>
                                </td>
                                <td><?= date("d/m/Y H:i", strtotime($p['data_pedido'])) ?></td>
                                <td>
                                    <?php
                                    // Mostra os botões apenas se estiver aguardando aprovação e for o nível do usuário
                                    if (($p['status'] ?? '') === 'aguardando_aprovacao' && ($p['nivel_aprovacao'] ?? '') === $nivelUsuario):
                                        $aprovarUrl = '?pagina=solicitacao_aprovar';
                                        $rejeitarUrl = '?pagina=solicitacao_rejeitar';
                                    ?>
                                        <button class="check-btn" data-id="<?= $p['id_pedido'] ?>" data-url="<?= $aprovarUrl ?>" data-acao="aceitar">Aprovar</button>
                                        <button class="deny-btn" data-id="<?= $p['id_pedido'] ?>" data-url="<?= $rejeitarUrl ?>" data-acao="negar">Rejeitar</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Nenhuma solicitação para aprovação.</td>
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
    const url = btn.dataset.url;

    if (!confirm(`Tem certeza que deseja ${acao === 'aceitar' ? 'aprovar' : 'negar'} este pedido?`)) return;

    const formData = new FormData();
    formData.append('acao', acao);
    formData.append('id_pedido', idPedido);

    try {
        const resp = await fetch('/TCC/app/controllers/PedidoAcaoController.php', {
            method: 'POST',
            body: formData
        });
        let data;
        try {
            data = await resp.json();
        } catch (e) {
            const text = await resp.text();
            console.log("Retorno inválido:", text);
            alert("Resposta inválida do servidor!");
            return;
        }

        alert(data.mensagem || data.erro);

        if (data.sucesso) {
            // atualiza status visual
            const statusSpan = tr.querySelector('.status');
            if (statusSpan) {
                statusSpan.textContent = acao === 'aceitar' ? 'A caminho' : 'Negado';
                statusSpan.className = 'status ' + (acao === 'aceitar' ? 'a-caminho' : 'negado');
            }
            // esconde botões
            tr.querySelectorAll('.check-btn, .deny-btn').forEach(b => b.style.display = 'none');
        }

    } catch (err) {
        console.error(err);
        alert('Ocorreu um erro ao processar a ação.');
    }
});
</script>

</body>
</html>
