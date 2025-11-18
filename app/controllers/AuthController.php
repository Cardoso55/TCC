<?php
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new Usuario();
        // REMOVA session_start() daqui
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

                header("Location: /TCC/index.php?pagina=dashboard");
                exit;
            } else {
                return "Email ou senha incorretos!";
            }
        }
    }

    // Logout
    public function logout() {
        // aqui também não precisa iniciar sessão de novo se já tiver sido iniciada
        session_unset();
        session_destroy();
        header("Location: /TCC/index.php?pagina=login");
        exit;
    }

    // Verifica se usuário está logado
    public static function checkAuth() {
        // aqui só inicia se a sessão ainda não estiver ativa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: /TCC/index.php?pagina=login");

            exit;
        }
    }
}
?>
