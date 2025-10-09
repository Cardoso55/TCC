<?php
// =======================================
// Página: login.php
// Função: Autentica o usuário no sistema.
// Exibe o formulário de login (usuário e senha).
// Redireciona para o dashboard conforme o nível hierárquico.
// =======================================
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Macaw Systems</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/login.css">
</head>
<body>

    <div class="login-wrapper">
        <!-- LADO ESQUERDO - LOGO / CINZA CLARO -->
        <div class="login-left">
            <div class="logo-area">
                <img src="/TCC/public/images/logos/VERMELHOPRETO.png" alt="Logo Macaw Systems" class="logo">
    
            </div>
        </div>

        <!-- LADO DIREITO - FORMULÁRIO -->
        <div class="login-right">
            <div class="form-area">
                <div class="icon-user">
                    <img src="/TCC/public/images/icons/pessoa.png" alt="Pessoa Login">
                </div>
                <h2>Bem-vindo de volta!</h2>
                <p>Entre com suas credenciais para continuar</p>

                <form action="#" method="POST">
                    <div class="input-group">
                        <label for="email">Usuário</label>
                        <input type="text" id="email" name="email" placeholder="Digite seu ID" required>
                    </div>

                    <div class="input-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required>
                    </div>

                    <button type="submit" class="btn-login">Entrar</button>

                    <p class="forgot"><a href="alterar_senha.php">Esqueceu a senha?</a></p>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
