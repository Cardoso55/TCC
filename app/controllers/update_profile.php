<?php
session_start();
require_once __DIR__ . '/../models/Usuario.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /TCC/views/auth/login.php');
    exit;
}

$usuarioModel = new Usuario();
$userId = $_SESSION['user_id'];

$nome = $_POST['nome'] ?? '';
$email = $_POST['email'] ?? '';
$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Atualiza nome e email
if (!empty($nome) && !empty($email)) {
    $usuarioModel->atualizarPerfil($userId, $nome, $email);
    $_SESSION['user_name'] = $nome; // Atualiza na sessão
}

// Se campos de senha foram preenchidos, faz verificação e atualização
if (!empty($current) || !empty($new) || !empty($confirm)) {
    $user = $usuarioModel->buscarUsuarioPorId($userId);

    if (!$user) {
        $_SESSION['flash_error'] = 'Usuário não encontrado.';
    } elseif (!password_verify($current, $user['senha_hash'])) {
        $_SESSION['flash_error'] = 'Senha atual incorreta.';
    } elseif ($new !== $confirm) {
        $_SESSION['flash_error'] = 'As senhas novas não coincidem.';
    } elseif (strlen($new) < 6) {
        $_SESSION['flash_error'] = 'A nova senha precisa ter pelo menos 6 caracteres.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $usuarioModel->atualizarSenha($userId, $hash);
        $_SESSION['flash_success'] = 'Senha atualizada com sucesso.';
    }
}

header('Location: /TCC/app/views/perfil.php');
exit;
