<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Usuário</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/perfil.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="all">
        <?php
            include 'partials/sidebar.php'; 
        ?>
        <div class="main-content">
            <div class="card user-profile">
                <h1 class="title">Perfil do Usuário</h1>
                
                <div class="input-group">
                    <label for="name">Nome</label>
                    <input type="text" id="name" value="Lucas Guimarães" readonly>
                </div>
                
                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" value="email@gmail.com" readonly>
                </div>
                
                <div class="input-group level-group">
                    <label for="password">Senha</label>
                    <div class="level-display">
                        <span>Coxinha123</span>
                        <i class="fas fa-eye"></i> 
                    </div>
                </div>
                
                <div class="input-group level-group">
                    <label>Nível</label>
                    <div class="level-display">
                        <span>Administrador</span>
                        <i class="fas fa-lock"></i> 
                    </div>
                    <button class="edit-button"><i class="fas fa-pencil-alt"></i> Editar</button>
                </div>
            </div>

            <div class="card individual-settings">
                <h2 class="subtitle">Configurações Individuais</h2>

                <div class="setting-row">
                    <label>Idioma</label>
                    <div class="options-group language-options">
                        <button class="selected">Português</button>
                        <button>English</button>
                        </div>
                </div>

                <div class="setting-row theme-setting">
                    <label>Tema</label>
                    <div class="options-group theme-options">
                        <button class="selected dark-theme-button">Modo Claro</button>
                        <button class="dark-theme-button">Modo Escuro</button>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="cancel-button">Cancelar</button>
                    <button class="save-button">Salvar</button>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Informações Pessoais</h2>
                    <span class="close-button">&times;</span> 
                </div>
                
                <div class="modal-body">
                    <div class="input-group">
                        <label for="modal-name">Nome</label>
                        <input type="text" id="modal-name" value="Lucas Guimarães">
                    </div>
                    
                    <div class="input-group">
                        <label for="modal-email">E-mail</label>
                        <input type="email" id="modal-email" value="email@gmail.com">
                    </div>
                    
                    <div class="input-group">
                        <label for="modal-password">Nova Senha</label>
                        <input type="password" id="modal-password" placeholder="Deixe em branco para não alterar">
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button class="cancel-button modal-close-btn">Cancelar</button>
                    <button class="save-button primary-button">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pega o modal
        var modal = document.getElementById("editModal");

        // Pega o botão que abre o modal
        var btn = document.querySelector(".edit-button");

        // Pega o elemento <span> que fecha o modal
        var span = document.querySelector(".close-button");

        // Pega o botão Cancelar do modal
        var cancelBtn = document.querySelector(".modal-close-btn");

        // Quando o usuário clica no botão, abre o modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // Quando o usuário clica em (x), fecha o modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // Quando o usuário clica em Cancelar, fecha o modal
        cancelBtn.onclick = function() {
            modal.style.display = "none";
        }

        // Quando o usuário clica em qualquer lugar fora do modal, fecha o modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
