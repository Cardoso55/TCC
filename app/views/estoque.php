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
                            Quantidade 
                            <span class="sort" data-campo="quantidade" data-ordem="asc">▲</span>
                        </th>
                        <th><button id="btnAdd">Adicionar</button></th>
                    </tr>
                </thead>
                <tbody id="tabela-estoque">
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
    // FILTRO E ORDENAÇÃO
    document.addEventListener('DOMContentLoaded', () => {
        const filtros = ['filtroCodigo', 'filtroNome', 'filtroCategoria', 'filtroPreco', 'filtroQuantidade'];

        filtros.forEach(id => {
            document.getElementById(id).addEventListener('input', filtrarProdutos);
        });

        // 3. Adiciona o listener 'click' para as setas de ordenação
        document.querySelectorAll('.sort').forEach(seta => {
            seta.addEventListener('click', function() {
                const campoAtual = this.dataset.campo; 
                let ordemAtual = this.dataset.ordem || 'null'; // Inicia como 'null' se não tiver ordem

                let proximaOrdem;
                let proximoSimbolo;

                // Lógica de Três Estados: null -> asc -> desc -> null
                if (ordemAtual === 'null' || ordemAtual === 'desc') {
                    proximaOrdem = 'asc'; // Próximo estado: Crescente
                    proximoSimbolo = '▲';
                } else if (ordemAtual === 'asc') {
                    proximaOrdem = 'desc'; // Próximo estado: Decrescente
                    proximoSimbolo = '▼';
                }

                // 🚨 Limpa o estado de TODAS as outras setas para 'null'
                document.querySelectorAll('.sort').forEach(s => {
                    if (s !== this) {
                        s.dataset.ordem = 'null'; 
                        s.textContent = '↕'; // Símbolo neutro para as desativadas
                    }
                });

                // 4. Atualiza o estado da seta clicada
                this.dataset.ordem = proximaOrdem;
                this.textContent = proximoSimbolo;

                // Se a ordem for 'null' (terceiro clique), não passamos campo/ordem
                let campoParaBuscar = (proximaOrdem === 'null') ? null : campoAtual;
                let ordemParaBuscar = (proximaOrdem === 'null') ? null : proximaOrdem;
                
                // CHAMA A FUNÇÃO DE BUSCA:
                // isOrderChange=true. Passa o campo e ordem, ou null, se for neutro.
                buscarProdutos(true, campoParaBuscar, ordemParaBuscar); 
            });
        });
        
    });

    function filtrarProdutos() {
        const filtros = {
            codigo: document.getElementById('filtroCodigo').value,
            nome: document.getElementById('filtroNome').value,
            categoria: document.getElementById('filtroCategoria').value,
            preco: document.getElementById('filtroPreco').value,
            quantidade: document.getElementById('filtroQuantidade').value,
        };

        const params = new URLSearchParams(filtros).toString();

        fetch(`../controllers/ProdutoController.php?acao=filtrar&${params}`)
            .then(res => res.json())
            .then(data => atualizarTabela(data))
            .catch(err => console.error('Erro no filtro:', err));
    }

    function ordenarProdutos(campo, ordem) {
        const params = new URLSearchParams({
            acao: 'ordenar',
            campo: campo,
            ordem: ordem
        });

        fetch(`../controllers/ProdutoController.php?${params}`)
            .then(res => res.json())
            .then(data => atualizarTabela(data))
            .catch(err => console.error('Erro na ordenação:', err));
    }

    function atualizarTabela(produtos) {
        // Confirma que o ID da tabela é 'tabela-estoque'
        const tbody = document.getElementById('tabela-estoque'); 
        
        if (!tbody) {
            // Se este erro aparecer no console, o ID do <tbody> está errado.
            console.error("Elemento 'tabela-estoque' não encontrado. Verifique o ID do <tbody>.");
            return; 
        }

        // Limpa a tabela antes de adicionar novos dados
        tbody.innerHTML = ''; 

        produtos.forEach(produto => {
            // 1. Formatação de Preço (garantindo o nome da chave: preco_unitario)
            let precoFormatado = (produto.preco_unitario || 0).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });

            // 2. Prepara os dados JSON para o botão de Edição
            const dataProdutoJson = JSON.stringify(produto)
                                    .replace(/'/g, "&apos;")
                                    .replace(/"/g, '&quot;');

            // 3. Monta a linha da tabela com as chaves CORRETAS
            tbody.innerHTML += `
                <tr data-produto='${dataProdutoJson}'> 
                    <td>${produto.codigo_produto}</td> <td>${produto.nome}</td>
                    <td>${produto.categoria}</td> <td>${precoFormatado}</td>
                    <td>${produto.quantidade_atual || 0}</td>
                    <td>
                        <button class="edit-btn">✏️</button>
                        <button class="delete-btn">🗑️</button>
                    </td>
                </tr>
            `;
        });
        
        // **Importante:** Se você tem ouvintes para 'edit-btn' e 'delete-btn', 
        // chame a função de reativação deles aqui. Exemplo: reativarBotoesEdicao();
    }

    // A FUNÇÃO COMPLETA (Substitua a sua versão por esta para garantir)
    function buscarProdutos(isOrderChange = false, newSortField = null, newSortOrder = null) {
        
        const filtros = {
            acao: 'filtrar',
            
            // Mapeamento dos inputs
            codigo: document.getElementById('filtroCodigo').value,
            nome: document.getElementById('filtroNome').value,
            categoria: document.getElementById('filtroCategoria').value,
            preco: document.getElementById('filtroPreco').value, 
            quantidade: document.getElementById('filtroQuantidade').value,
        };
        
        // 1. LÓGICA PARA DETERMINAR A ORDENAÇÃO
        let campoOrdenacao = null;
        let ordem = null;

        if (newSortField && newSortOrder && newSortOrder !== 'null') {
            // Usa os valores passados pelo clique da seta
            campoOrdenacao = newSortField;
            ordem = newSortOrder;
        } else if (!isOrderChange) {
            // Se é um filtro de digitação, tenta manter a ordenação atual, se houver
            const activeSort = document.querySelector('.sort[data-ordem="asc"], .sort[data-ordem="desc"]');
            if (activeSort && activeSort.dataset.ordem !== 'null') {
                campoOrdenacao = activeSort.dataset.campo;
                ordem = activeSort.dataset.ordem;
            }
        }
        // Se newSortOrder é 'null' ou não há seta ativa, campoOrdenacao e ordem permanecem null, o que resulta em "sem ordenação" no PHP.

        // 2. ADICIONA PARÂMETROS DE ORDENAÇÃO AO OBJETO DE FILTROS
        if (campoOrdenacao) {
            // Mapear data-campo do HTML ('preco', 'quantidade') para o nome da coluna do Model ('preco_unitario', 'quantidade_atual')
            const dbColumn = campoOrdenacao === 'preco' 
                ? 'preco_unitario' 
                : (campoOrdenacao === 'quantidade' 
                    ? 'quantidade_atual' 
                    : campoOrdenacao);

            filtros['ordenar_por'] = dbColumn;
            filtros['ordem'] = ordem;
        }

        // 3. EXECUÇÃO DA REQUISIÇÃO AJAX
        const params = new URLSearchParams(filtros).toString();

        fetch(`../controllers/ProdutoController.php?${params}`)
            .then(res => res.json())
            .then(data => atualizarTabela(data))
            .catch(err => console.error('Erro na busca (Filtro/Ordenação):', err));
    }
</script>

</body>
</html>
