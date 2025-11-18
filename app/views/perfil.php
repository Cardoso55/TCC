<?php
require_once __DIR__ . '/../controllers/AuthController.php';
AuthController::checkAuth(); // garante que só entra logado

$nome = $_SESSION['user_name'];
$nivel = $_SESSION['user_level'];
$userId = $_SESSION['user_id'];

// buscar dados extras do usuário
require_once __DIR__ . '/../models/Usuario.php';
$usuarioModel = new Usuario();
$usuario = $usuarioModel->buscarUsuarioPorId($userId);
$email = $usuario['email'];
$nivel = $usuario['nivel'];
?>

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
        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <div class="main-content">
            <div class="card user-profile">
                <h1 class="title">Perfil do Usuário</h1>
                
                <div class="input-group">
                    <label for="name">Nome</label>
                    <input type="text" id="name" value="<?= htmlspecialchars($nome) ?>" readonly>
                </div>
                
                <div class="input-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($email) ?>" readonly>
                </div>

                <div class="input-group level-group">
                    <label>Nível</label>
                    <?php
                        $nivelFormatado = match (strtolower($nivel)) {
                            'operario' => 'Operário',
                            'administrador' => 'Administrador',
                            'gerente' => 'Gerente',
                            'diretor' => 'Diretor',
                            default => ucfirst($nivel),
                        };
                    ?>
                    <div class="level-display">
                        <span><?= $nivelFormatado ?></span>
                        <i class="fas fa-lock"></i> 
                    </div>
                    <button id="changePwdBtn" class="edit-button"><i class="fas fa-pencil-alt"></i>Editar</button>
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

        <!-- MODAL DE EDIÇÃO -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Informações Pessoais</h2>
                    <span class="close-button">&times;</span>
                </div>

                <form id="editForm" method="POST" action="/TCC/app/controllers/update_profile.php">
                    <div class="modal-body">
                        <div class="input-group">
                            <label for="modal-name">Nome</label>
                            <input type="text" id="modal-name" name="nome" value="<?= htmlspecialchars($nome) ?>">
                        </div>

                        <div class="input-group">
                            <label for="modal-email">E-mail</label>
                            <input type="email" id="modal-email" name="email" value="<?= htmlspecialchars($email) ?>">
                        </div>

                        <hr style="margin:15px 0; border:none; border-top:1px solid #ddd;">

                        <h3 style="font-size:1rem; margin-bottom:10px;">Alterar Senha (opcional)</h3>

                        <div class="input-group">
                            <label for="current_password">Senha atual</label>
                            <div class="pwd-field">
                                <input type="password" id="current_password" name="current_password" placeholder="Digite sua senha atual">
                                <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="new_password">Nova senha</label>
                            <div class="pwd-field">
                                <input type="password" id="new_password" name="new_password" placeholder="Nova senha (opcional)">
                                <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="confirm_password">Confirmar nova senha</label>
                            <div class="pwd-field">
                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar nova senha">
                                <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="cancel-button modal-close-btn">Cancelar</button>
                        <button type="submit" class="save-button primary-button">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script>
    const modal = document.getElementById("editModal");
    const openBtn = document.querySelector(".edit-button");
    const closeBtn = document.querySelector(".close-button");
    const cancelBtn = document.querySelector(".modal-close-btn");

    openBtn.onclick = () => modal.style.display = "block";
    closeBtn.onclick = () => modal.style.display = "none";
    cancelBtn.onclick = () => modal.style.display = "none";
    window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; };

    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', () => {
            const target = document.getElementById(icon.dataset.target);
            target.type = target.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('visible');
        });
    });

    document.getElementById('editForm').addEventListener('submit', function(e) {
        const newPwd = document.getElementById('new_password').value.trim();
        const confPwd = document.getElementById('confirm_password').value.trim();
        const currentPwd = document.getElementById('current_password').value.trim();

        if (newPwd || confPwd || currentPwd) {
            if (!currentPwd) { alert('Por favor, digite sua senha atual para alterar.'); e.preventDefault(); return; }
            if (newPwd !== confPwd) { alert('As senhas novas não coincidem!'); e.preventDefault(); return; }
            if (newPwd.length < 6) { alert('A nova senha precisa ter pelo menos 6 caracteres.'); e.preventDefault(); return; }
        }
    });
</script>
</body>
</html>
