<?php
// arquivo: /TCC/app/views/estoque.php
if (session_status() === PHP_SESSION_NONE)
    session_start();

require_once __DIR__ . '/../controllers/ProdutoController.php';
$produtos = ProdutoController::listarProdutos();

// nível do usuário logado
$userLevel = $_SESSION['user_level'] ?? '';

// permissões
$temPermissaoCompleta = in_array($userLevel, [
    'diretor',
    'gerente',
    'supervisor',
    'operario'
]);

$setorVendas = ($userLevel === 'setor-de-vendas');
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
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
</head>

<body>
    <div class="all">
        <?php include 'partials/sidebar.php'; ?>
        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 class="title">Gestão de Estoque</h2>
            <button id="btnRevisarMinimo" class="review-min-btn button-blue">
                Revisar estoque mínimo
            </button>
            </div>
            <div class="stock-management">
                <input type="text" id="filtroCodigo" placeholder="Código">
                <input type="text" id="filtroNome" placeholder="Nome">
                <input type="text" id="filtroCategoria" placeholder="Categoria">
                <input type="text" id="filtroPreco" placeholder="Preço específico">
                <input type="text" id="filtroQuantidade" placeholder="Quantidade específica">
            </div>

            

            <h2 class="subtitle">Produtos em estoque</h2>
            <div class="product-list">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nome <span class="sort" data-campo="nome" data-ordem="null">↕</span></th>
                            <th>Tipo</th>
                            <th>Preço Unitário <span class="sort" data-campo="preco" data-ordem="asc">▲</span></th>
                            <th>Valor Compra</th>
                            <th>Quantidade <span class="sort" data-campo="quantidade" data-ordem="asc">▲</span></th>
                            <th>Quantidade Miníma</th>
                            <th>
                                <?php if ($temPermissaoCompleta): ?>
                                    <button id="btnAdd">Adicionar</button>
                                <?php elseif ($setorVendas): ?>
                                    Saída
                                <?php else: ?>
                                    Ações
                                <?php endif; ?>
                            </th>
                        </tr>
                    </thead>

                    <tbody id="tabela-estoque">
                        <?php if (!empty($produtos) && is_array($produtos)): ?>
                            <?php foreach ($produtos as $p):
                                $dataset = [
                                    'id_produto' => $p['id_produto'] ?? $p['idProdutos_TBL'] ?? null,
                                    'codigo_produto' => $p['codigo_produto'] ?? '',
                                    'nome' => $p['nome'] ?? '',
                                    'categoria' => $p['categoria'] ?? '',
                                    'preco_unitario' => $p['preco_unitario'] ?? $p['preco'] ?? 0,
                                    'valor_compra' => $p['valor_compra'] ?? 0,
                                    'quantidade_atual' => $p['estoque_atual'] ?? 0,
                                    'quantidade_minima' => $p['quantidade_minima'] ?? $p['estoque_minima'] ?? null,
                                    'quantidade_maxima' => $p['quantidade_maxima'] ?? null,
                                    'quantidade_baixo' => $p['quantidade_baixo'] ?? null,
                                    'imagem_url' => $p['imagem_url'] ?? $p['imagem'] ?? null,
                                    'descricao' => $p['descricao'] ?? ''
                                ];

                                ?>
                                <tr data-produto='<?= htmlspecialchars(json_encode($dataset), ENT_QUOTES, "UTF-8") ?>'>
                                    <td><?= htmlspecialchars($dataset['codigo_produto']) ?></td>
                                    <td><?= htmlspecialchars($dataset['nome']) ?></td>
                                    <td><?= htmlspecialchars($dataset['categoria']) ?></td>
                                    <td>R$ <?= number_format($dataset['preco_unitario'], 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($dataset['valor_compra'], 2, ',', '.') ?></td>
                                    <td><?= $dataset['quantidade_atual'] ?></td>
                                    <td><?= $dataset['quantidade_minima'] !== null ? $dataset['quantidade_minima'] : '-' ?></td>
                                    <td>
                                        <?php if ($userLevel === 'operario'): ?>
                                            <button class="reposicao-btn" type="button" title="Repor">
                                                <span class="material-symbols-outlined">inventory_2</span>
                                            </button>
                                        <?php elseif (in_array($userLevel, ['supervisor', 'gerente', 'diretor'])): ?>
                                            <button class="edit-btn" type="button" title="Editar">
                                                <span class="material-symbols-outlined">edit</span>
                                            </button>

                                            <button class="delete-btn" type="button" title="Excluir">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>

                                            <button class="reposicao-btn" type="button" title="Repor">
                                                <span class="material-symbols-outlined">inventory_2</span>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($setorVendas): ?>
                                            <button class="saida-btn" type="button" title="Saída">
                                                <span class="material-symbols-outlined">sell</span>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">Nenhum produto encontrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- MODAL SAÍDA (para setor de vendas) -->
            <?php if ($setorVendas): ?>
                <!-- MODAL SAÍDA -->
                <div id="modal-saida" class="modal" style="display:none;">
                    <div class="modal-content">
                        <span class="close" onclick="hideModal(modalSaida)">&times;</span>
                        <h2>Registrar Saída</h2>
                        <form id="form-saida">
                            <input type="hidden" id="saida-id_produto" name="id_produto">
                            <div class="modal-row">
                                <label>Produto:</label>
                                <span id="saida-nome"></span>
                            </div>
                            <div class="modal-row">
                                <label>Estoque atual:</label>
                                <span id="saida-quantidade-atual"></span>
                            </div>
                            <div class="modal-row">
                                <label>Quantidade:</label>
                                <input type="number" id="saida-quantidade" data-estoque="<?= $p['estoque_atual'] ?>"
                                    min="1">


                            </div>
                            <div class="modal-row">
                                <label>Observação:</label>
                                <textarea id="saida-observacao" name="observacao"></textarea>
                            </div>
                            <div class="modal-footer">
                                <button type="submit">Enviar Pedido</button>
                                <button type="button" onclick="hideModal(modalSaida)">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>

            <?php endif; ?>

            <!-- MODAL CADASTRAR -->
            <?php if ($temPermissaoCompleta): ?>
                <div id="modal-add" class="modal" style="display:none;">
                    <div class="modal-content">
                        <button class="close">&times;</button>
                        <h2>Cadastrar Produto</h2>

                        <!-- ACTION: sempre enviar para o router -->
                        <form id="form-add" action="/TCC/index.php?pagina=produto" method="POST" enctype="multipart/form-data">
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
                            </div>

                            <div class="modal-footer">
                                <button type="submit">Salvar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MODAL EDITAR -->
            <?php if ($temPermissaoCompleta): ?>
                <div id="modal-edit" class="modal" style="display:none;">
                    <div class="modal-content">
                        <button class="close">&times;</button>
                        <h2>Editar Produto</h2>

                        <!-- ACTION: enviar para router (mesma rota, acao=editar) -->
                        <form id="form-edit" action="/TCC/index.php?pagina=produto" method="POST"
                            enctype="multipart/form-data">
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
                                        <input type="number" step="0.01" name="valor_compra" id="edit-valor_compra"
                                            required>
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
            <?php endif; ?>

            <!-- MODAL REPOSIÇÃO (somente quem tem permissão) -->
            <?php if ($temPermissaoCompleta): ?>
                <div id="modal-reposicao" class="modal">
                    <div class="modal-content">
                        <span class="close" id="close-reposicao">&times;</span>

                        <h2 class="modal-title">Solicitar Reposição</h2>

                        <form id="form-reposicao">
                            <input type="hidden" id="rep-id" name="id_produto">

                            <div class="modal-row"><label>Produto:</label><span id="rep-nome"></span></div>
                            <div class="modal-row"><label>Estoque atual:</label><span id="rep-atual"></span></div>
                            <div class="modal-row"><label>Estoque mínimo:</label><span id="rep-min"></span></div>
                            <div class="modal-row"><label>Estoque baixo:</label><span id="rep-baixo"></span></div>
                            <div class="modal-row"><label>Estoque máximo:</label><span id="rep-max"></span></div>

                            <div class="modal-inputs">
                                <div class="input-box">
                                    <label>Quantidade desejada:</label>
                                    <input type="number" id="rep-quantidade" name="quantidade" min="1" required>
                                </div>

                                <div class="input-box">
                                    <label>Fornecedor:</label>
                                    <input type="text" id="rep-fornecedor" name="fornecedor" required>
                                </div>
                            </div>

                            <div class="modal-btns">
                                <button type="submit" class="btn confirmar">Enviar pedido</button>
                                <button type="button" class="btn cancelar" id="cancelar-reposicao">Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- ======================
     JS (inline aqui pra facilitar teste)
     ====================== -->
    <script>
        const USER_LEVEL = '<?= $_SESSION['user_level'] ?? '' ?>';
        const SETOR_VENDAS = <?= ($userLevel === 'setor-de-vendas') ? 'true' : 'false' ?>;

        const $ = s => document.querySelector(s);
        const $$ = s => Array.from(document.querySelectorAll(s));

        function showModal(el) {
            if (el) {
                el.style.display = 'block';
                el.setAttribute('aria-hidden', 'false');
            }
        }
        function hideModal(el) {
            if (el) {
                el.style.display = 'none';
                el.setAttribute('aria-hidden', 'true');
            }
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", "&#039;");
        }

        function htmlspecialchars_js(s) {
            return escapeHtml(s)
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }
        document.addEventListener('DOMContentLoaded', () => {
            // --- Modais ---
            const modalAdd = $('#modal-add');
            const modalEdit = $('#modal-edit');
            const modalReposicao = $('#modal-reposicao');
            const modalSaida = $('#modal-saida');

            // Abrir modal adicionar
            $('#btnAdd')?.addEventListener('click', () => showModal(modalAdd));
            modalAdd?.querySelector('.close')?.addEventListener('click', () => hideModal(modalAdd));

            // Fechar modais editar, reposição
            modalEdit?.querySelector('.close')?.addEventListener('click', () => hideModal(modalEdit));
            modalReposicao?.querySelector('.close')?.addEventListener('click', () => hideModal(modalReposicao));
            $('#cancelar-reposicao')?.addEventListener('click', () => hideModal(modalReposicao));

            // Fechar modal saída
            $('#close-saida')?.addEventListener('click', () => hideModal(modalSaida));
            $('#cancelar-saida')?.addEventListener('click', () => hideModal(modalSaida));

            window.addEventListener('click', e => {
                if (e.target === modalAdd) hideModal(modalAdd);
                if (e.target === modalEdit) hideModal(modalEdit);
                if (e.target === modalReposicao) hideModal(modalReposicao);
                if (e.target === modalSaida) hideModal(modalSaida);
            });

            // --- Tabela e delegação ---
            const tabelaBody = $('#tabela-estoque');

            tabelaBody?.addEventListener('click', async (e) => {
                const btn = e.target.closest('button');
                if (!btn) return;

                const tr = btn.closest('tr');
                if (!tr) return;

                let data;
                try {
                    data = JSON.parse(tr.getAttribute('data-produto') || '{}');
                } catch {
                    console.error("Erro parse data-produto");
                    data = {};
                }

                // --- EDITAR ---
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

                // --- EXCLUIR ---
                if (btn.classList.contains('delete-btn')) {
                    if (!confirm(`Tem certeza que deseja excluir "${data.nome}"?`)) return;

                    try {
                        const resp = await fetch(`/TCC/index.php?pagina=produto&acao=excluir&id_produto=${encodeURIComponent(data.id_produto)}`, {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' }
                        });

                        const raw = await resp.text();
                        const clean = raw.trim();
                        let json;
                        try { json = JSON.parse(clean); } catch (e) {
                            console.error("Resposta inválida:", raw);
                            alert("O servidor retornou uma resposta inesperada.");
                            return;
                        }

                        if (json.sucesso) { alert("Produto excluído com sucesso!"); location.reload(); return; }
                        if (json.temVinculos) {
                            alert(`O produto não pode ser excluído porque possui vínculos:\nChecklists: ${json.detalhes.checklists}\nReposições: ${json.detalhes.reposicoes}`);
                            return;
                        }
                        alert("Não foi possível excluir o produto.");

                    } catch (err) {
                        console.error(err);
                        alert("Erro de comunicação com o servidor.");
                    }

                    return;
                }

                // --- REPOSIÇÃO ---
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

                // --- SAÍDA ---
                if (btn.classList.contains('saida-btn')) {
                    $('#saida-id_produto').value = data.id_produto ?? '';
                    $('#saida-nome').textContent = data.nome ?? '';
                    $('#saida-quantidade-atual').textContent = data.quantidade_atual ?? '';
                    $('#saida-quantidade').value = ''; // limpa o campo de entrada
                    $('#saida-observacao').value = ''; // limpa observação
                    showModal(modalSaida);
                    return;
                }

            });

            // --- FORM SAÍDA ---
            $('#form-saida')?.addEventListener('submit', async e => {
                e.preventDefault();

                const idProduto = $('#saida-id_produto')?.value;
                const quantidadeInput = $('#saida-quantidade');
                const quantidade = parseInt(quantidadeInput?.value) || 0; // corrigido o ID
                const observacao = $('#saida-observacao')?.value || '';

                // pega o estoque disponível do data-attribute
                const estoqueDisponivel = parseInt(quantidadeInput?.dataset.estoque) || 0;

                if (!idProduto || quantidade <= 0) {
                    alert("Preencha a quantidade corretamente.");
                    return;
                }

                if (quantidade > estoqueDisponivel) {
                    alert(`Quantidade maior que o estoque disponível (${estoqueDisponivel}).`);
                    quantidadeInput.value = estoqueDisponivel; // opcional: ajusta para o máximo
                    return;
                }

                const formData = new FormData();
                formData.append('acao', 'criar_saida');
                formData.append('id_produto', idProduto);
                formData.append('quantidade', quantidade);
                formData.append('observacao', observacao);

                try {
                    const resp = await fetch('/TCC/index.php?pagina=produto', { method: 'POST', body: formData });
                    const txt = await resp.text();
                    alert(txt || "Solicitação enviada com sucesso.");
                    hideModal(modalSaida);
                    location.reload();
                } catch (err) {
                    console.error(err);
                    alert("Erro ao enviar solicitação.");
                }
            });


            // --- FORM REPOSIÇÃO ---
            $('#form-reposicao')?.addEventListener('submit', async e => {
                e.preventDefault();
                const idProduto = $('#rep-id').value;
                const quantidade = $('#rep-quantidade').value;

                if (!idProduto || !quantidade || quantidade <= 0) { alert("Preencha a quantidade corretamente."); return; }

                const formData = new FormData();
                formData.append('acao', 'criar');
                formData.append('id_produto', idProduto);
                formData.append('quantidade', quantidade);
                formData.append('fornecedor', $('#rep-fornecedor').value);

                try {
                    const r = await fetch('/TCC/index.php?pagina=produto', { method: 'POST', body: formData });
                    const txt = await r.text();
                    alert(txt || "Pedido criado com sucesso.");
                    hideModal(modalReposicao);
                    location.reload();
                } catch (err) {
                    console.error(err);
                    alert("Erro ao criar reposição.");
                }
            });
        });
        // --- FILTROS E ORDENAÇÃO ---
        async function buscarProdutos(isOrderChange = false, newSortField = null, newSortOrder = null) {
            const filtros = {
                codigo: $('#filtroCodigo')?.value || '',
                nome: $('#filtroNome')?.value || '',
                categoria: $('#filtroCategoria')?.value || '',
                preco: $('#filtroPreco')?.value || '',
                quantidade: $('#filtroQuantidade')?.value || '',
                acao: 'filtrar'
            };

            if (newSortField && newSortOrder && newSortOrder !== 'null') {
                filtros.ordenar_por =
                    newSortField === 'preco' ? 'preco_unitario'
                        : newSortField === 'quantidade' ? 'quantidade_atual'
                            : newSortField;
                filtros.ordem = newSortOrder;
            } else {
                const activeSort = document.querySelector('.sort[data-ordem="asc"], .sort[data-ordem="desc"]');
                if (activeSort) { filtros.ordenar_por = activeSort.dataset.campo; filtros.ordem = activeSort.dataset.ordem; }
            }

            const params = new URLSearchParams(filtros).toString();

            try {
                const res = await fetch(`/TCC/index.php?pagina=produto&${params}`);
                const data = await res.json();
                const tbody = $('#tabela-estoque');
                

                if (!Array.isArray(data) || data.length === 0) { tbody.innerHTML = '<tr><td colspan="7">Nenhum produto encontrado.</td></tr>'; return; }

                tbody.innerHTML = '';

                data.forEach(produto => {
                    const ds = {
                        id_produto: produto.id_produto ?? produto.idProdutos_TBL ?? produto.id,
                        codigo_produto: produto.codigo_produto ?? produto.codigo ?? '',
                        nome: produto.nome ?? produto.titulo ?? '',
                        categoria: produto.categoria ?? '',
                        preco_unitario: Number(produto.preco_unitario ?? produto.preco ?? 0),
                        valor_compra: produto.valor_compra ?? 0,
                        quantidade_atual: produto.quantidade_atual ?? 0,
                        quantidade_minima: produto.quantidade_minima ?? null,
                        quantidade_maxima: produto.quantidade_maxima ?? null,
                        quantidade_baixo: produto.quantidade_baixo ?? null,
                        imagem_url: produto.imagem_url ?? null,
                        descricao: produto.descricao ?? ''
                    };

                    const precoFormatado = ds.preco_unitario.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                    const dataJson = htmlspecialchars_js(JSON.stringify(ds));

                    let botoes = '';
                    if (USER_LEVEL === 'operario') {
                        botoes = `<button class="reposicao-btn" title="Repor"><span class="material-symbols-outlined">inventory_2</span></button>`;
                    } else if (['supervisor', 'gerente', 'diretor'].includes(USER_LEVEL)) {
                        botoes = `
                    <button class="edit-btn" title="Editar"><span class="material-symbols-outlined">edit</span></button>
                    <button class="delete-btn" title="Excluir"><span class="material-symbols-outlined">delete</span></button>
                    <button class="reposicao-btn" title="Repor"><span class="material-symbols-outlined">inventory_2</span></button>
                `;
                    }
                    if (SETOR_VENDAS) {
                        botoes += `<button class="saida-btn" title="Registrar saída"><span class="material-symbols-outlined">logout</span></button>`;
                    }

                    tbody.insertAdjacentHTML('beforeend', `
                <tr data-produto='${dataJson}'>
                    <td>${escapeHtml(ds.codigo_produto)}</td>
                    <td>${escapeHtml(ds.nome)}</td>
                    <td>${escapeHtml(ds.categoria)}</td>
                    <td>${precoFormatado}</td>
                    <td>${(ds.valor_compra || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
                    <td>${ds.quantidade_atual}</td>
                    <td>${ds.quantidade_minima ?? '-'}</td>
                    <td>${botoes}</td>
                </tr>
            `);
                });

            } catch (err) {
                console.error("Erro buscarProdutos", err);
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            ['filtroCodigo', 'filtroNome', 'filtroCategoria', 'filtroPreco', 'filtroQuantidade'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.addEventListener('input', () => buscarProdutos(false)); el.addEventListener('change', () => buscarProdutos(false)); }
            });

            document.querySelectorAll('.sort').forEach(s => {
                s.addEventListener('click', function () {
                    const campoAtual = this.dataset.campo;
                    const ordemAtual = this.dataset.ordem || 'null';
                    const proxima = (ordemAtual === 'null' || ordemAtual === 'desc') ? 'asc' : 'desc';
                    const simbolo = proxima === 'asc' ? '▲' : '▼';
                    document.querySelectorAll('.sort').forEach(x => { if (x !== this) { x.dataset.ordem = 'null'; x.textContent = '↕'; } });
                    this.dataset.ordem = proxima;
                    this.textContent = simbolo;
                    buscarProdutos(true, campoAtual, proxima);
                });
            });
        });
    </script>
    <script>
        document.getElementById("btnRevisarMinimo").addEventListener("click", async () => {

            if (!confirm("Tem certeza que deseja recalcular todos os mínimos?")) {
                return;
            }

            const response = await fetch("/TCC/index.php?pagina=produto&acao=recalcular_minimos", {
                method: "POST"
            });

            const data = await response.json();

            if (data.sucesso) {
                alert("Estoque mínimo recalculado com sucesso!");
                location.reload();
            } else {
                alert("Erro: " + data.mensagem);
            }
        });

    </script>



</body>

</html>