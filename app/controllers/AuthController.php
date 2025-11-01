<?php

require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
        session_start(); // inicia sessão
    }

    // Login
    public function loginFromPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['senha'])) {
            $email = $_POST['email'];
            $senha = $_POST['senha'];

            $usuario = $this->usuarioModel->login($email, $senha);

            if ($usuario) {
                $_SESSION['user_id'] = $usuario['id_usuario'];
                $_SESSION['user_name'] = $usuario['nome'];
                $_SESSION['user_level'] = $usuario['nivel'];

                // Atualiza último login
                $this->usuarioModel->atualizarUltimoLogin($usuario['id_usuario']);

                header("Location: ../dashboard.php"); // redireciona pro painel
                exit;
            } else {
                return "Email ou senha incorretos!";
            }
        }
    }

    // Logout
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header("Location: ../views/auth/login.php");
        exit;
    }

    // Verifica se usuário está logado
    public static function checkAuth() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: ../views/login.php");
            exit;
        }
    }
}
?>
