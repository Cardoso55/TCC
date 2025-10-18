<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/estoque.css">
</head>
<body>
    <div class="all">
        <?php
            include 'partials/sidebar.php'; 
        ?>
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
                            <th><button>Adicionar</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>01</td>
                            <td>Água</td>
                            <td>Consumível</td>
                            <td>R$ 3,00</td>
                            <td>50</td>
                            <td>
                                <i class="edit-icon"></i>
                                <i class="delete-icon"></i>
                                <i class="alert-icon"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- MODAL CADASTRO DE PRODUTO -->
            <div id="modal-produto" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Cadastrar Produto</h2>

                    <div class="form-grid">
                        <div class="form-fields">
                            <div class="form-group">
                                <label>Nome do produto</label>
                                <input type="text" maxlength="90" />
                            </div>

                            <div class="form-group">
                                <label>Data de Validade</label>
                                <input type="date" />
                            </div>

                            <div class="form-group">
                                <label>Preço Unidade (R$)</label>
                                <input type="number" step="0.01" />
                            </div>

                            <div class="form-group">
                                <label>Categoria</label>
                                <input type="text" />
                            </div>

                            <div class="form-group">
                                <label>Quantidade</label>
                                <input type="number" />
                            </div>

                            <div class="form-group">
                                <label>Descrição</label>
                                <textarea id="descricao" maxlength="200"></textarea>
                                <span id="char-count">0 / 200</span>
                            </div>
                        </div>

                        <div class="image-upload">
                            <div class="image-preview">
                                <img src="img-placeholder.png" alt="Prévia" />
                            </div>

                            <div class="thumbnail-row">
                                <div class="thumbnail"><img src="img1.png" alt=""></div>
                                <div class="thumbnail"><img src="img2.png" alt=""></div>
                                <div class="thumbnail"><img src="img3.png" alt=""></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button>Salvar</button>
                    </div>
                    </div>
            </div>


        </div>
        
    </div>

<script>
    const modal = document.getElementById("modal-produto");
    const btnAdicionar = document.querySelector("th button, .btn-add");
    const spanClose = document.querySelector(".close");

    // Abrir o modal
    btnAdicionar.addEventListener("click", (e) => {
        e.preventDefault();
        modal.style.display = "block";
    });

    // Fechar o modal
    spanClose.addEventListener("click", () => {
        modal.style.display = "none";
    });

    // Fechar clicando fora do modal
    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.style.display = "none";
        }
    });  

    const textarea = document.getElementById('descricao');
    const counter = document.getElementById('char-count');

    textarea.addEventListener('input', () => {
        const currentLength = textarea.value.length;
        const maxLength = textarea.getAttribute('maxlength');
        counter.textContent = `${currentLength} / ${maxLength}`;
    });
</script>

</body>
</html>