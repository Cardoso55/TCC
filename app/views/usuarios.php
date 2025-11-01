<?php
require_once '../controllers/UsuarioController.php';
$controller = new UsuarioController();

// Processar criação (já existente)
$resultado = $controller->criarUsuarioFromPost();

// Processar atualização (novo)
$updateResult = $controller->atualizarUsuarioFromPost();

// Buscar usuários pra listar
$usuarios = $controller->listarUsuarios();

$controller->deletarUsuarioFromPost();


// Mensagem (opcional)
$msg = null;
if ($resultado) $msg = "Usuário cadastrado com sucesso!";
if ($updateResult) $msg = "Usuário atualizado com sucesso!";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/usuarios.css">
</head>
<body>
    <div class="all">
        <?php
            include 'partials/sidebar.php'; 
        ?>
        <div class="main-content">

            <h2 class="title">Gestão de Usuário</h2>

            <div class="user-management">
                <input type="text" placeholder="Nome">
                <input type="email" placeholder="Email">
                <select>
                    <option value="" disabled selected>Nível</option>
                    <option value="diretor">Diretor</option>
                    <option value="gerente">Gerente</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="operario">Operário</option>
                </select>
                <button>Filtrar</button>
            </div>
            
            <div class="middle-line">
                <h2 class="subtitle">Usuários</h2>
                <button class="add-user" onclick="openModal()">Adicionar Usuário</button>
            </div>
            <!-- Lista de usuários -->
            <div class="user-list">
                <h2>Lista de Usuários</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Cargo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php $userJson = htmlspecialchars(json_encode($usuario, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nome']) ?></td>
                                <td><?= htmlspecialchars($usuario['email']) ?></td>
                                <?php
                                    $roles = [
                                    'diretor' => 'Diretor',
                                    'gerente' => 'Gerente',
                                    'supervisor' => 'Supervisor',
                                    'operario' => 'Operário'
                                    ];
                                ?>
                                <td><?= $roles[$usuario['nivel']] ?? ucfirst($usuario['nivel']) ?></td>
                                <td><?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                                <td>
                                    <button class="edit-btn" data-user='<?= $userJson ?>'>Editar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Modal de Edição (inicialmente oculto) -->
            <div id="editModal" class="modal-overlay" style="display:none;">
            <div class="modal-content">
                <button class="modal-close" id="modalCloseBtn">&times;</button>
                    <h3>Editar Usuário</h3>
                    <form id="editForm" method="POST" action="usuarios.php">
                        <input type="hidden" name="id" id="edit-id">

                        <label for="edit-nome">Nome</label>
                        <input type="text" name="nome" id="edit-nome" required>

                        <label for="edit-email">Email</label>
                        <input type="email" name="email" id="edit-email" required>

                        <label for="edit-nivel">Nível</label>
                        <select name="nivel" id="edit-nivel" required>
                            <option value="diretor">Diretor</option>
                            <option value="gerente">Gerente</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="operario">Operário</option>
                        </select>

                        <label>
                            <input type="checkbox" name="ativo" id="edit-ativo">
                            Ativo
                        </label>

                        <div class="modal-buttons">
                            <button type="submit" class="save">Salvar</button>
                            <button type="button" class="delete" id="deleteUserBtn">Excluir</button>
                            <button type="button" class="cancel" id="modalCancelBtn">Cancelar</button>
                        </div>
                </form>

            </div>
            </div>


            <!-- Modal de Cadastro -->
            <div class="modal-overlay" id="userModal">
                <div class="modal-content">
                    <h2>Cadastro de Usuário</h2>
                    <form method="POST">
                        <label>Nome</label>
                        <input type="text" placeholder="Digite o nome" required id="name" name="name">

                        <label>E-mail</label>
                        <input type="email" placeholder="Digite o e-mail" required id="email" name="email">

                        <label>Senha</label>
                        <input type="password" placeholder="Digite a senha" required id="password" name="password">

                        <label>Nível</label>
                        <select required id="level" name="level">
                            <option value="" disabled selected>Selecione</option>
                            <option value="diretor">Diretor</option>
                            <option value="gerente">Gerente</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="operario">Operário</option>
                        </select>

                        <div class="buttons">
                            <button type="submit" class="save">Salvar</button>
                            <button type="button" class="cancel" onclick="closeModal()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        
    </div>

<script>
function openModal() {
  document.getElementById('userModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('userModal').style.display = 'none';
}
  // Abrir modal e preencher campos a partir do data-user (JSON)
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      try {
        const user = JSON.parse(btn.dataset.user);

        // Ajuste aqui caso o seu campo id seja `id_usuario` no array
        const id = user.id_usuario ?? user.id ?? user.id_user ?? null;

        document.getElementById('edit-id').value = id;
        document.getElementById('edit-nome').value = user.nome ?? '';
        document.getElementById('edit-email').value = user.email ?? '';
        document.getElementById('edit-nivel').value = user.nivel ?? '';
        document.getElementById('edit-ativo').checked = parseInt(user.ativo ?? 0) === 1;

        document.getElementById('editModal').style.display = 'flex';
      } catch (e) {
        console.error('Erro ao abrir modal de edição:', e);
        alert('Erro ao abrir formulário de edição. Veja console para detalhes.');
      }
    });
  });

  // Fechar modal
  document.getElementById('modalCloseBtn').addEventListener('click', () => {
    document.getElementById('editModal').style.display = 'none';
  });
  document.getElementById('modalCancelBtn').addEventListener('click', () => {
    document.getElementById('editModal').style.display = 'none';
  });

  // Fechar clicando fora do conteúdo
  document.getElementById('editModal').addEventListener('click', (e) => {
    if (e.target === document.getElementById('editModal')) {
      document.getElementById('editModal').style.display = 'none';
    }
  });

  document.getElementById('deleteUserBtn').addEventListener('click', () => {
    const id = document.getElementById('edit-id').value;
    if (!id) return alert('Erro: ID não encontrado.');

    if (confirm('Tem certeza que deseja excluir este usuário?')) {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'usuarios.php';

      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'delete_id';
      idInput.value = id;

      form.appendChild(idInput);
      document.body.appendChild(form);
      form.submit();
    }
  });
</script>

</body>
</html>