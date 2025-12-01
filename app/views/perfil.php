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
    <style>
        .theme-toggle {
            display: flex;
            align-items: center;
            gap: .5rem;
            background: #f3f3f3;
            border: 1px solid #ccc;
            color: #333;
            border-radius: 10px;
            padding: 8px 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .theme-toggle:hover {
            background: #e9e9e9;
            border-color: #b50000;
            color: #b50000;
        }
    </style>
</head>

<body>
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="flash-message success"><?= $_SESSION['flash_success'];
        unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="flash-message error"><?= $_SESSION['flash_error'];
        unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>
    <div class="all">


        <?php include __DIR__ . '/partials/sidebar.php'; ?>

        <div class="main-content">

            <!-- PERFIL DO USUÁRIO -->
            <div class="card user-profile">
                <h2 class="title">Perfil do Usuário</h2>

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

                        
                    <div class="level-display">
                        <?php
                        $nivelFormatado = match (strtolower($nivel)) {
                            'operario' => 'Operário',
                            'administrador' => 'Administrador',
                            'gerente' => 'Gerente',
                            'diretor' => 'Diretor',
                            'setor-de-vendas' => 'Setor de Vendas',
                            'setor-de-compras' => 'Setor de Compras',
                            default => ucfirst($nivel),
                        };
                    ?>
                        <span><?= $nivelFormatado ?></span>
                        <i class="fas fa-lock"></i>
                    </div>

                    <button id="changePwdBtn" class="edit-button">
                        <i class="fas fa-pencil-alt"></i> Editar
                    </button>
                </div>
            </div>

            <!-- ACESSIBILIDADE -->
            <div class="card individual-settings">
                <h2 class="title">Configurações de Acessibilidade</h2>

                <h3 class="subtitle">Fontes</h3>

                <div class="acessibilidade-controles">
                    <button onclick="increaseFont()" class="btn-acess"><i class="fa-solid fa-plus" style="margin-right: 5px"></i>Aumentar Letra</button>
                    <button onclick="decreaseFont()" class="btn-acess"><i class="fa-solid fa-minus" style="margin-right: 5px"></i>Diminuir Letra</button>
                    <button onclick="resetFont()" class="btn-acess"><i class="fa-solid fa-arrow-rotate-left" style="margin-right: 5px"></i>Resetar</button>
                </div>

                <h3 class="subtitle">Acessibilidade Geral</h3>

                <div class="acess-buttons">
                    <button onclick="toggleModoLeitura()" class="btn-blue">🗣 Leitura por Clique</button>
                    <button onclick="toggleDarkMode()" class="btn-blue"><i class="fa-solid fa-moon"></i> Dark Mode</button>
                </div>

                <h3 class="subtitle">Daltonismo</h3>

                <div class="acess-buttons">
                    <button class="btn-outline" onclick="setDaltonismo('protanopia')">Protanopia</button>
                    <button class="btn-outline" onclick="setDaltonismo('deuteranopia')">Deuteranopia</button>
                    <button class="btn-outline" onclick="setDaltonismo('tritanopia')">Tritanopia</button>
                    <button class="btn-outline" onclick="setDaltonismo('none')">Desativar</button>
                </div>

                <h3 class="subtitle">Contraste</h3>
                <div class="acess-buttons">
                    <button onclick="toggleHighContrast()" class="btn-blue">Alto Contraste</button>
                </div>
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
                            <input type="password" id="current_password" name="current_password"
                                placeholder="Digite sua senha atual">
                            <i class="fas fa-eye toggle-password" data-target="current_password"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="new_password">Nova senha</label>
                        <div class="pwd-field">
                            <input type="password" id="new_password" name="new_password"
                                placeholder="Nova senha (opcional)">
                            <i class="fas fa-eye toggle-password" data-target="new_password"></i>
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirmar <br>nova senha</label>
                        <div class="pwd-field">
                            <input type="password" id="confirm_password" name="confirm_password"
                                placeholder="Confirmar nova senha">
                            <i class="fas fa-eye toggle-password" data-target="confirm_password"></i>
                        </div>
                    </div>

                    <!-- Erros do modal -->
                    <div id="password-error" class="modal-error"></div>

                    <!-- Requisitos da senha -->
                    <div id="password-requirements" class="password-requirements">
                        <p class="req-length"><span class="icon">❌</span> Pelo menos 7 caracteres</p>
                        <p class="req-uppercase"><span class="icon">❌</span> Uma letra maiúscula</p>
                        <p class="req-lowercase"><span class="icon">❌</span> Uma letra minúscula</p>
                        <p class="req-number"><span class="icon">❌</span> Um número</p>
                        <p class="req-symbol"><span class="icon">❌</span> Um símbolo</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="cancel-button modal-close-btn">Cancelar</button>
                    <button type="submit" class="save-button primary-button locked" disabled>Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById("editModal");
        const openBtn = document.querySelector(".edit-button");
        const closeBtn = document.querySelector(".close-button");
        const cancelBtn = document.querySelector(".modal-close-btn");
        const saveBtn = document.querySelector('.save-button.primary-button');

        openBtn.onclick = () => modal.style.display = "block";
        closeBtn.onclick = () => modal.style.display = "none";
        cancelBtn.onclick = () => modal.style.display = "none";
        window.onclick = (e) => { if (e.target == modal) modal.style.display = "none"; };

        // Função pra verificar senha forte
        function senhaForte(senha) {
            return /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{7,}$/.test(senha);
        }

        // Toggle de visibilidade da senha
        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', () => {
                const target = document.getElementById(icon.dataset.target);
                target.type = target.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('visible');
            });
        });

        // Requisitos da senha
        const newPwdInput = document.getElementById('new_password');
        const confPwdInput = document.getElementById('confirm_password');
        const requirements = {
            length: document.querySelector('.req-length'),
            uppercase: document.querySelector('.req-uppercase'),
            lowercase: document.querySelector('.req-lowercase'),
            number: document.querySelector('.req-number'),
            symbol: document.querySelector('.req-symbol')
        };

        // Atualiza ícones de requisitos
        function updateReq(reqElem, condition) {
            if (condition) {
                reqElem.classList.add('valid');
                reqElem.querySelector('.icon').textContent = '✅';
            } else {
                reqElem.classList.remove('valid');
                reqElem.querySelector('.icon').textContent = '❌';
            }
        }


        // Atualiza o estado do botão Salvar
        function atualizarBotaoSalvar() {
            const val = newPwdInput.value;
            const confVal = confPwdInput.value;

            const todosRequisitos =
                val.length >= 7 &&
                /[A-Z]/.test(val) &&
                /[a-z]/.test(val) &&
                /\d/.test(val) &&
                /[\W_]/.test(val);

            // Se campo confirmar está vazio, botão cinza
            if (val === "" || !todosRequisitos) {
                saveBtn.disabled = true;
                saveBtn.classList.add('locked');
            } else if (confVal.length === 0) {
                saveBtn.disabled = false;  // liberado para digitar confirm password
                saveBtn.classList.remove('locked');
            } else {
                saveBtn.disabled = false;
                saveBtn.classList.remove('locked');
            }
        }

        // Atualiza requisitos e botão conforme digitação
        newPwdInput.addEventListener('input', () => {
            const val = newPwdInput.value;
            updateReq(requirements.length, val.length >= 7);
            updateReq(requirements.uppercase, /[A-Z]/.test(val));
            updateReq(requirements.lowercase, /[a-z]/.test(val));
            updateReq(requirements.number, /\d/.test(val));
            updateReq(requirements.symbol, /[\W_]/.test(val));
            atualizarBotaoSalvar();
        });

        confirmPwdInput.addEventListener('input', atualizarBotaoSalvar);

        // Submit do form com mensagens no topo
        document.getElementById('editForm').addEventListener('submit', function (e) {
            const newPwd = newPwdInput.value.trim();
            const confPwd = confirmPwdInput.value.trim();
            const currentPwd = document.getElementById('current_password').value.trim();

            let errors = [];

            if (newPwd || confPwd || currentPwd) {
                if (!currentPwd) errors.push('Digite sua senha atual.');
                if (newPwd !== confPwd) errors.push('As senhas não coincidem.');
                if (!senhaForte(newPwd)) errors.push('Senha precisa ter pelo menos 7 caracteres, incluindo maiúscula, minúscula, número e símbolo.');
            }

            const flashDiv = document.getElementById('flash-message');
            if (errors.length > 0) {
                e.preventDefault();
                if (flashDiv) {
                    flashDiv.innerHTML = errors.map(err => `<p>${err}</p>`).join('');
                    flashDiv.className = 'flash-message error';
                }
            } else if (flashDiv) {
                flashDiv.innerHTML = '';
                flashDiv.className = '';
            }
        });

    </script>

</body>

</html>