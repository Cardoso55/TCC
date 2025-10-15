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
            <div class="user-list">
                <h2>Lista de Usuários</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Nível</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Gabriel</td>
                            <td>email@gmail.com</td>
                            <td>Funcionário</td>
                            <td>Ativo</td>
                            <td><a href="">Editar</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Modal -->
            <div class="modal-overlay" id="userModal">
                <div class="modal-content">
                    <h2>Cadastro de Usuário</h2>
                    <form>
                        <label>Nome</label>
                        <input type="text" placeholder="Digite o nome" required>

                        <label>E-mail</label>
                        <input type="email" placeholder="Digite o e-mail" required>

                        <label>Senha</label>
                        <input type="password" placeholder="Digite a senha" required>

                        <label>Nível</label>
                        <select required>
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
</script>

</body>
</html>