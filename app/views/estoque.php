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
            <input type="text" id="filtroCodigo" placeholder="Código">
            <input type="text" id="filtroNome" placeholder="Nome">
            <input type="text" id="filtroCategoria" placeholder="Categoria">
            <input type="text" id="filtroPreco" placeholder="Preço específico">
            <input type="text" id="filtroQuantidade" placeholder="Quantidade específica">
        </div>
                
     <div class="product-list">
    <table>
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome
                    <span class="sort" data-campo="nome" data-ordem="null">↕</span>
                </th>
                <th>Tipo</th>
                <th>
                    Preço Unitário 
                    <span class="sort" data-campo="preco" data-ordem="asc">▲</span>
                </th>
                <th>
                    Valor Compra
                </th>
                <th>
                    Quantidade 
                    <span class="sort" data-campo="quantidade" data-ordem="asc">▲</span>
                </th>
                <th><button id="btnAdd">Adicionar</button></th>
            </tr>
        </thead>
        <tbody id="tabela-estoque">
            <?php if (!empty($produtos) && is_array($produtos)): ?>
                <?php foreach ($produtos as $p): ?>
                    <?php
                        $dataset = [
                            'id_produto' => $p['id_produto'] ?? $p['idProdutos_TBL'] ?? null,
                            'codigo_produto' => $p['codigo_produto'] ?? '',
                            'nome' => $p['nome'] ?? '',
                            'categoria' => $p['categoria'] ?? '',
                            'preco_unitario' => $p['preco_unitario'] ?? $p['preco'] ?? 0,
                            'valor_compra' => $p['valor_compra'] ?? 0, // <-- adiciona aqui
                            'quantidade_atual' => $p['quantidade_atual'] ?? 0,
                            'quantidade_minima' => $p['quantidade_minima'] ?? null,
                            'quantidade_maxima' => $p['quantidade_maxima'] ?? null,
                            'quantidade_baixo' => $p['quantidade_baixo'] ?? null,
                            'imagem_url' => $p['imagem_url'] ?? $p['imagem'] ?? null,
                        ];
                    ?>
                    <tr data-produto='<?= htmlspecialchars(json_encode($dataset), ENT_QUOTES, 'UTF-8') ?>'>
                        <td><?= htmlspecialchars($dataset['codigo_produto']) ?></td>
                        <td><?= htmlspecialchars($dataset['nome']) ?></td>
                        <td><?= htmlspecialchars($dataset['categoria']) ?></td>
                        <td>R$ <?= number_format($dataset['preco_unitario'], 2, ',', '.') ?></td>
                        <td>R$ <?= number_format($dataset['valor_compra'], 2, ',', '.') ?></td> <!-- coluna valor compra -->
                        <td><?= $dataset['quantidade_atual'] ?></td>
                        <td>
                            <button class="edit-btn" type="button" title="Editar">✏️</button>
                            <button class="delete-btn" type="button" title="Excluir">🗑️</button>
                            <button class="reposicao-btn" type="button" title="Repor">📦</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7">Nenhum produto encontrado.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>


        <!-- MODAL CADASTRAR -->
        <div id="modal-add" class="modal" aria-hidden="true" style="display:none;">
            <div class="modal-content">
                <button class="close" aria-label="Fechar">&times;</button>
                <h2>Cadastrar Produto</h2>
                <form id="form-add" action="../controllers/ProdutoController.php" method="POST" enctype="multipart/form-data">
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
                                <label>Valor de Compra (R$)</label>
                                <input type="number" step="0.01" name="valor_compra" required>
                            </div>


                            <div class="form-group">
                                <label>Categoria</label>
                                <input type="text" name="categoria">
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
        <div id="modal-edit" class="modal" aria-hidden="true" style="display:none;">
            <div class="modal-content">
                <button class="close-edit" aria-label="Fechar">&times;</button>
                <h2>Editar Produto</h2>
                <form id="form-edit" action="../controllers/ProdutoController.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id_produto" id="edit-id_produto">
                    <div class="form-grid">
                        <div class="form-fields">
                            <div class="form-group">
                                <label>Nome do produto</label>
                                <input type="text" name="nome" id="edit-nome" required>
                            </div>

                            <div class="form-group">
                                <label>Preço Unidade (R$)</label>
                                <input type="number" step="0.01" name="preco" id="edit-preco" required>
                            </div>

                            <div class="form-group">
                                <label>Valor de Compra (R$)</label>
                                <input type="number" step="0.01" name="valor_compra" id="edit-valor_compra" required>
                            </div>

                            <div class="form-group">
                                <label>Categoria</label>
                                <input type="text" name="categoria" id="edit-categoria">
                            </div>

                            <div class="form-group">
                                <label>Quantidade</label>
                                <input type="number" name="quantidade" id="edit-quantidade">
                            </div>

                            <div class="form-group">
                                <label>Descrição</label>
                                <textarea name="descricao" id="edit-descricao" maxlength="200"></textarea>
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

       <!-- MODAL DE REPOSIÇÃO -->
    <div id="modal-reposicao" class="modal">
        <div class="modal-content">
            <span class="close" id="close-reposicao">&times;</span>

            <h2 class="modal-title">Solicitar Reposição</h2>

            <form id="form-reposicao">
                <input type="hidden" id="rep-id" name="id_produto">

                <div class="modal-row">
                    <label>Produto:</label>
                    <span id="rep-nome" class="modal-info"></span>
                </div>

                <div class="modal-row">
                    <label>Estoque atual:</label>
                    <span id="rep-atual" class="modal-info"></span>
                </div>

                <div class="modal-row">
                    <label>Estoque mínimo:</label>
                    <span id="rep-min" class="modal-info"></span>
                </div>

                <div class="modal-row">
                    <label>Estoque baixo:</label>
                    <span id="rep-baixo" class="modal-info"></span>
                </div>

                <div class="modal-row">
                    <label>Estoque máximo:</label>
                    <span id="rep-max" class="modal-info"></span>
                </div>

                <div class="modal-inputs">
                    <div class="input-box">
                        <label for="rep-quantidade">Quantidade desejada:</label>
                        <input type="number" id="rep-quantidade" name="quantidade" min="1" required>
                    </div>

                    <div class="input-box">
                        <label for="rep-fornecedor">Fornecedor:</label>
                        <input type="text" id="rep-fornecedor" name="fornecedor">
                    </div>
                </div>

                <div class="modal-btns">
                    <button type="submit" class="btn confirmar">Enviar pedido</button>
                    <button type="button" class="btn cancelar" id="cancelar-reposicao">Cancelar</button>
                </div>
            </form>
        </div>
    </div>


    </div>
</div>

<script>
// helper: safe query selector
const $ = sel => document.querySelector(sel);
const $$ = sel => Array.from(document.querySelectorAll(sel));

// Modal helpers
function showModal(el) {
    if (!el) return;
    el.style.display = 'block';
    el.setAttribute('aria-hidden', 'false');
}
function hideModal(el) {
    if (!el) return;
    el.style.display = 'none';
    el.setAttribute('aria-hidden', 'true');
}

// Open/close basic modals (Add / Edit)
const modalAdd = $('#modal-add');
const modalEdit = $('#modal-edit');
const modalReposicao = $('#modal-reposicao');

const btnAdd = $('#btnAdd');
if (btnAdd && modalAdd) btnAdd.addEventListener('click', () => showModal(modalAdd));

const closeAdd = modalAdd ? modalAdd.querySelector('.close') : null;
if (closeAdd) closeAdd.addEventListener('click', () => hideModal(modalAdd));

const closeEdit = modalEdit ? modalEdit.querySelector('.close-edit') : null;
if (closeEdit) closeEdit.addEventListener('click', () => hideModal(modalEdit));

const closeReposicao = modalReposicao ? modalReposicao.querySelector('.close-reposicao') : null;
if (closeReposicao) closeReposicao.addEventListener('click', () => hideModal(modalReposicao));

const repCancel = $('#rep-cancel');
if (repCancel) repCancel.addEventListener('click', () => hideModal(modalReposicao));

// Close modals when click outside
window.addEventListener('click', (e) => {
    if (e.target === modalAdd) hideModal(modalAdd);
    if (e.target === modalEdit) hideModal(modalEdit);
    if (e.target === modalReposicao) hideModal(modalReposicao);
});

// Delegation: handle clicks inside the table body
const tabelaBody = $('#tabela-estoque');
if (tabelaBody) {
    tabelaBody.addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;

        const tr = btn.closest('tr');
        if (!tr) return;

        let data;
        try {
            data = JSON.parse(tr.getAttribute('data-produto') || '{}');
        } catch (err) {
            console.error('Erro ao parsear data-produto:', err);
            data = {};
        }

        if (btn.classList.contains('edit-btn')) {
            $('#edit-id_produto').value = data.id_produto ?? '';
            $('#edit-nome').value = data.nome ?? '';
            $('#edit-preco').value = data.preco_unitario ?? '';
            $('#edit-categoria').value = data.categoria ?? '';
            $('#edit-quantidade').value = data.quantidade_atual ?? '';
            $('#edit-descricao').value = data.descricao ?? '';
            $('#preview-edit').src = data.imagem_url ? `/TCC/app/${data.imagem_url}` : "/TCC/public/img/img-placeholder.png";
            $('#edit-valor_compra').value = data.valor_compra ?? '';
            showModal(modalEdit);
            return;
        }

        if (btn.classList.contains('delete-btn')) {
            if (confirm(`Tem certeza que deseja excluir o produto "${data.nome}"?`)) {

                // 🌟 CORRIGIDO — Caminho absoluto
                window.location.href = `/TCC/app/controllers/ProdutoController.php?acao=excluir&id=${encodeURIComponent(data.id_produto)}`;
            }
            return;
        }

        if (btn.classList.contains('reposicao-btn')) {
            $('#rep-id').value = data.id_produto ?? '';
            $('#rep-nome').textContent = data.nome ?? '';
            $('#rep-atual').textContent = data.quantidade_atual ?? '-';
            $('#rep-min').textContent = data.quantidade_minima ?? '-';
            $('#rep-baixo').textContent = data.quantidade_baixo ?? '-';
            $('#rep-max').textContent = data.quantidade_maxima ?? '-';
            $('#rep-quantidade').value = '';
            $('#rep-fornecedor').value = '';
            showModal(modalReposicao);
            return;
        }
    });
}

// FORM: reposição
const formReposicao = $('#form-reposicao');
if (formReposicao) {
    formReposicao.addEventListener('submit', async (e) => {
        e.preventDefault();
        const idProduto = $('#rep-id').value;
        const quantidade = $('#rep-quantidade').value;
        const fornecedor = $('#rep-fornecedor').value;

        if (!idProduto || !quantidade || quantidade <= 0) {
            alert('Preencha uma quantidade válida.');
            return;
        }

        const formData = new FormData();
        formData.append('acao', 'criar');
        formData.append('id_produto', idProduto);
        formData.append('quantidade', quantidade);
        formData.append('fornecedor', fornecedor);

        try {

            // 🌟 CORRIGIDO — Caminho absoluto
           const resp = await fetch(`index.php?pagina=requisicao`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const text = await resp.text();
            alert(text);
            hideModal(modalReposicao);
            location.reload();

        } catch (err) {
            console.error('Erro ao criar pedido de reposição:', err);
            alert('Erro ao criar pedido. Veja console.');
        }
    });
}

// ==============================
// BUSCA / FILTRO / ORDENAÇÃO
// ==============================

async function buscarProdutos(isOrderChange = false, newSortField = null, newSortOrder = null) {
    const filtros = {
        codigo: $('#filtroCodigo')?.value || '',
        nome: $('#filtroNome')?.value || '',
        categoria: $('#filtroCategoria')?.value || '',
        preco: $('#filtroPreco')?.value || '',
        quantidade: $('#filtroQuantidade')?.value || '',
        acao: 'filtrar'
    };

    let campoOrdenacao = null;
    let ordem = null;

    if (newSortField && newSortOrder && newSortOrder !== 'null') {
        campoOrdenacao = newSortField;
        ordem = newSortOrder;
    } else if (!isOrderChange) {
        const activeSort = document.querySelector('.sort[data-ordem="asc"], .sort[data-ordem="desc"]');
        if (activeSort) {
            campoOrdenacao = activeSort.dataset.campo;
            ordem = activeSort.dataset.ordem;
        }
    }

    if (campoOrdenacao) {
        const dbColumn = campoOrdenacao === 'preco' ? 'preco_unitario' :
                         campoOrdenacao === 'quantidade' ? 'quantidade_atual' :
                         campoOrdenacao;
        filtros.ordenar_por = dbColumn;
        filtros.ordem = ordem;
    }

    const params = new URLSearchParams(filtros).toString();

    try {

        // 🌟 CORRIGIDO — Caminho absoluto
        const res = await fetch(`/TCC/app/controllers/ProdutoController.php?${params}`);
        const data = await res.json();
        atualizarTabela(data);

    } catch (err) {
        console.error('Erro na busca (Filtro/Ordenação):', err);
    }
}

function atualizarTabela(produtos) {
    const tbody = $('#tabela-estoque');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!Array.isArray(produtos) || produtos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6">Nenhum produto encontrado.</td></tr>';
        return;
    }

    produtos.forEach(produto => {
        const dataset = {
            id_produto: produto.id_produto ?? produto.idProdutos_TBL ?? produto.id,
            codigo_produto: produto.codigo_produto ?? produto.codigo ?? '',
            nome: produto.nome ?? produto.titulo ?? '',
            categoria: produto.categoria ?? '',
            preco_unitario: produto.preco_unitario ?? produto.preco ?? 0,
            valor_compra: produto.valor_compra ?? 0,
            quantidade_atual: produto.quantidade_atual ?? 0,
            quantidade_minima: produto.quantidade_minima ?? null,
            quantidade_maxima: produto.quantidade_maxima ?? null,
            quantidade_baixo: produto.quantidade_baixo ?? null,
            imagem_url: produto.imagem_url ?? null,
            descricao: produto.descricao ?? ''
        };

        const dataJson = htmlspecialchars_js(JSON.stringify(dataset));
        const precoFormatado = (dataset.preco_unitario || 0).toLocaleString('pt-BR', { 
            style: 'currency', currency: 'BRL' 
        });

        tbody.insertAdjacentHTML('beforeend', `
            <tr data-produto='${dataJson}'>
                <td>${escapeHtml(dataset.codigo_produto)}</td>
                <td>${escapeHtml(dataset.nome)}</td>
                <td>${escapeHtml(dataset.categoria)}</td>
                <td>${precoFormatado}</td>
                 <td>${(dataset.valor_compra || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
                <td>${dataset.quantidade_atual}</td>
                <td>
                    <button class="edit-btn" type="button" title="Editar">✏️</button>
                    <button class="delete-btn" type="button" title="Excluir">🗑️</button>
                    <button class="reposicao-btn" type="button" title="Repor">📦</button>
                </td>
            </tr>
        `);
    });
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function htmlspecialchars_js(s) {
    return escapeHtml(s)
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
    ['filtroCodigo','filtroNome','filtroCategoria','filtroPreco','filtroQuantidade'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', () => buscarProdutos(false));
            el.addEventListener('change', () => buscarProdutos(false));
        }
    });

    document.querySelectorAll('.sort').forEach(s => {
        s.addEventListener('click', function() {
            const campoAtual = this.dataset.campo;
            let ordemAtual = this.dataset.ordem || 'null';

            let proximaOrdem, proximoSimbolo;

            if (ordemAtual === 'null' || ordemAtual === 'desc') {
                proximaOrdem = 'asc'; 
                proximoSimbolo = '▲';
            } else {
                proximaOrdem = 'desc'; 
                proximoSimbolo = '▼';
            }

            document.querySelectorAll('.sort').forEach(x => {
                if (x !== this) { 
                    x.dataset.ordem = 'null'; 
                    x.textContent = '↕'; 
                }
            });

            this.dataset.ordem = proximaOrdem;
            this.textContent = proximoSimbolo;

            buscarProdutos(true, campoAtual, proximaOrdem);
        });
    });
});

</script>



</body>
</html>
