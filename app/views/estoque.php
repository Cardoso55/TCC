<?php
require_once __DIR__ . '/../controllers/ProdutoController.php';
$produtos = ProdutoController::listarProdutos();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Estoque</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/estoque.css">
</head>
<body>
<div class="all">
    <?php include 'partials/sidebar.php'; ?>
    <div class="main-content">

        <h2 class="title">Gestão de Estoque</h2>

        <div class="stock-management">
            <input type="text" placeholder="Pesquisar">
            <button>Filtrar</button>
        </div>
        
        <div class="product-list">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Preço Unitário</th>
                        <th>Quantidade</th>
                        <th><button id="btnAdd">Adicionar</button></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($produtos) > 0): ?>
                        <?php foreach ($produtos as $p): ?>
                            <tr data-produto='<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                                <td><?= htmlspecialchars($p['codigo_produto']) ?></td>
                                <td><?= htmlspecialchars($p['nome']) ?></td>
                                <td><?= htmlspecialchars($p['categoria']) ?></td>
                                <td>R$ <?= number_format($p['preco_unitario'], 2, ',', '.') ?></td>
                                <td><?= $p['quantidade_atual'] ?? 0 ?></td>
                                <td>
                                    <button class="edit-btn">✏️</button>
                                    <button class="delete-btn">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6">Nenhum produto encontrado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- MODAL CADASTRAR -->
        <div id="modal-add" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Cadastrar Produto</h2>
                <form action="../controllers/ProdutoController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="cadastrar">
                    <div class="form-grid">
                        <div class="form-fields">
                            <div class="form-group">
                                <label>Nome do produto</label>
                                <input type="text" name="nome" maxlength="90" required>
                            </div>

                            <div class="form-group">
                                <label>Preço Unidade (R$)</label>
                                <input type="number" step="0.01" name="preco" required>
                            </div>

                            <div class="form-group">
                                <label>Categoria</label>
                                <input type="text" name="categoria">
                            </div>

                            <div class="form-group">
                                <label>Quantidade</label>
                                <input type="number" name="quantidade" required>
                            </div>

                            <div class="form-group">
                                <label>Descrição</label>
                                <textarea name="descricao" maxlength="200"></textarea>
                            </div>
                        </div>

                        <div class="image-upload">
                            <div class="image-preview">
                                <img src="/TCC/public/img/img-placeholder.png" alt="Prévia">
                            </div>
                            <input type="file" name="imagem" accept="image/*">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDITAR -->
        <div id="modal-edit" class="modal">
            <div class="modal-content">
                <span class="close-edit">&times;</span>
                <h2>Editar Produto</h2>
                <form action="../controllers/ProdutoController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id_produto">
                    <div class="form-grid">
                        <div class="form-fields">
                            <div class="form-group">
                                <label>Nome do produto</label>
                                <input type="text" name="nome" required>
                            </div>

                            <div class="form-group">
                                <label>Preço Unidade (R$)</label>
                                <input type="number" step="0.01" name="preco" required>
                            </div>

                            <div class="form-group">
                                <label>Categoria</label>
                                <input type="text" name="categoria">
                            </div>

                            <div class="form-group">
                                <label>Quantidade</label>
                                <input type="number" name="quantidade" required>
                            </div>

                            <div class="form-group">
                                <label>Descrição</label>
                                <textarea name="descricao" maxlength="200"></textarea>
                            </div>
                        </div>

                        <div class="image-upload">
                            <div class="image-preview">
                                <img id="preview-edit" src="/TCC/public/img/img-placeholder.png" alt="Prévia">
                            </div>
                            <input type="file" name="imagem" accept="image/*">
                            <small>Se não escolher, manterá a imagem atual</small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
const modalAdd = document.getElementById("modal-add");
const modalEdit = document.getElementById("modal-edit");
const btnAdd = document.getElementById("btnAdd");
const closeAdd = document.querySelector(".close");
const closeEdit = document.querySelector(".close-edit");

btnAdd.onclick = () => modalAdd.style.display = "block";
closeAdd.onclick = () => modalAdd.style.display = "none";
closeEdit.onclick = () => modalEdit.style.display = "none";

window.onclick = e => {
    if (e.target === modalAdd) modalAdd.style.display = "none";
    if (e.target === modalEdit) modalEdit.style.display = "none";
};

// EDITAR
document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.onclick = () => {
        const data = JSON.parse(btn.closest("tr").dataset.produto);
        const form = modalEdit.querySelector("form");

        form.id_produto.value = data.id_produto;
        form.nome.value = data.nome;
        form.preco.value = data.preco_unitario;
        form.categoria.value = data.categoria;
        form.quantidade.value = data.quantidade_atual;
        form.descricao.value = data.descricao;

        document.getElementById("preview-edit").src = data.imagem_url ? `/TCC/app/${data.imagem_url}` : "/TCC/public/img/img-placeholder.png";
        modalEdit.style.display = "block";
    };
});

// EXCLUIR
document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.onclick = () => {
        const data = JSON.parse(btn.closest("tr").dataset.produto);
        if (confirm(`Tem certeza que deseja excluir o produto "${data.nome}"?`)) {
            window.location.href = `../controllers/ProdutoController.php?acao=excluir&id=${data.id_produto}`;
        }
    };
});
</script>

</body>
</html>
