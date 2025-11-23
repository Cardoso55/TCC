<?php
// ------------------------- SESSÃO -------------------------
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$user_id = $_SESSION["user_id"] ?? null;
if (!$user_id) {
    die("Usuário não autenticado.");
}

// ------------------------- CARREGA OS PEDIDOS DA IA -------------------------
require_once __DIR__ . '/../models/PedidoReposicaoModel.php';
$model = new PedidoReposicaoModel();

// Só pedidos gerados automaticamente pela IA
$pedidosIA = $model->getPedidosIA();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Inteligentes - IA</title>

    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/ia.css">

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>

<body>

<div class="all">

    <?php include 'partials/sidebar.php'; ?>

    <main class="main-content">

        <h1 class="title">Pedidos automáticos da IA</h1>

        <?php if (empty($pedidosIA)): ?>
            <p class="nenhum">Nenhuma decisão gerada pela IA ainda.</p>

        <?php else: ?>

            <table class="tabela-pedidos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        <th>Quantidade</th>
                        <th>Data Prevista</th>
                        <th>Status</th>
                        <th>Nível Aprovação</th>
                        <th>Ações</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($pedidosIA as $p): ?>
                    <tr id="row-<?= $p['id_pedido'] ?>">
                        <td><?= $p['id_pedido'] ?></td>
                        <td><?= htmlspecialchars($p['nome_produto']) ?></td>
                        <td><?= $p['quantidade'] ?></td>
                        <td><?= $p['data_prevista_chegada'] ?></td>
                        <td class="status"><?= $p['status'] ?></td>
                        <td><?= $p['nivel_aprovacao'] ?></td>

                        <td>
                            <button onclick="atualizarStatus(<?= $p['id_pedido'] ?>, 'aprovar')">Aprovar</button>
                            <button onclick="atualizarStatus(<?= $p['id_pedido'] ?>, 'negar')">Rejeitar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

        <?php endif; ?>

    </main>

</div>

<script>
function atualizarStatus(id, action) {

    const formData = new FormData();
    formData.append("id", id);
    formData.append("action", action);

    axios.post("/TCC/index.php?pagina=ia_atualizarStatus", formData)
        .then(res => {
            if (res.data.success) {
                document.querySelector(`#row-${id} .status`).textContent = action;
            } else {
                alert(res.data.message || "Erro ao atualizar status.");
            }
        })
        .catch(err => {
          console.error("ERRO COMPLETO:", err);                     // erro bruto
          if (err.response) {
              console.error("STATUS:", err.response.status);        // código HTTP
              console.error("DATA:", err.response.data);            // resposta do backend
              alert("Erro na requisição: " + JSON.stringify(err.response.data));
          } else {
              console.error("ERR MESSAGE:", err.message);
              alert("Erro na requisição (sem resposta do servidor).");
          }
      });

}
</script>

</body>
</html>
