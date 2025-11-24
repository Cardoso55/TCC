<?php

require_once __DIR__ . '/../models/Usuario.php';

class UsuarioController {
    private $usuario;

    public function __construct() {
        $this->usuario = new Usuario(); // Instancia o modelo Usuario
    }

    // Função para validar senha forte
    private function validarSenhaForte($senha) {
        // Mínimo 8 caracteres, pelo menos 1 maiúscula, 1 minúscula, 1 número e 1 símbolo
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/';
        return preg_match($regex, $senha);
    }

    public function criarUsuarioFromPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
            session_start();
            $nivelLogado = $_SESSION['user_level'] ?? '';

            $nome = trim($_POST['name']);
            $email = trim($_POST['email']);
            $senha = $_POST['password'];
            $nivel = trim($_POST['level']);

            // validação de senha
            if (!$this->validarSenhaForte($senha)) {
                $_SESSION['flash_error'] = "Senha fraca! Use pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e símbolos.";
                header("Location: /TCC/index.php?pagina=usuarios");
                exit;
            }

            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            // Hierarquia de criação
            $permitidos = [
                'diretor' => ['diretor','gerente','supervisor','operario','setor-de-compras','setor-de-vendas'],
                'gerente' => ['gerente','supervisor','operario','setor-de-compras','setor-de-vendas'],
                'supervisor' => ['supervisor','operario'],
                'operario' => [],
                'setor-de-compras' => [],
                'setor-de-vendas' => []
            ];

            if (!in_array($nivel, $permitidos[$nivelLogado] ?? [])) {
                $_SESSION['flash_error'] = "Você não tem permissão para criar usuários desse nível.";
                header("Location: /TCC/index.php?pagina=usuarios");
                exit;
            }

            $resultado = $this->usuario->criarUsuario($nome, $email, $senhaHash, $nivel);

            if ($resultado) {
                $_SESSION['flash_success'] = "Usuário criado com sucesso!";
            } else {
                $_SESSION['flash_error'] = "Erro ao criar usuário.";
            }

            header("Location: /TCC/index.php?pagina=usuarios");
            exit;
        }
    }

    public function listarUsuarios($nome = '', $ativo = '', $nivel = '') {
        // Retorna todos se filtros vazios
        if ($nome === '' && $ativo === '' && $nivel === '') {
            return $this->usuario->listarUsuario();
        }
        return $this->usuario->listarUsuario($nome, $ativo, $nivel);
    }

    public function buscarUsuario($id) {
        return $this->usuario->buscarUsuarioPorId($id);
    }

    public function atualizarUsuario($id, $nome, $email, $nivel, $ativo) {
        session_start();
        $nivelLogado = $_SESSION['user_level'] ?? '';

        // Hierarquia na edição
        $permitidosEdicao = [
            'diretor' => ['diretor','gerente','supervisor','operario','setor-de-compras','setor-de-vendas'],
            'gerente' => ['supervisor','operario','setor-de-compras','setor-de-vendas'],
            'supervisor' => ['supervisor','operario'],
            'operario' => [],
            'setor-de-compras' => [],
            'setor-de-vendas' => []
        ];

        if (!in_array($nivel, $permitidosEdicao[$nivelLogado] ?? [])) {
            $_SESSION['flash_error'] = "Você não tem permissão para editar usuários desse nível.";
            header("Location: /TCC/index.php?pagina=usuarios");
            exit;
        }

        return $this->usuario->atualizarUsuario($id, $nome, $email, $nivel, $ativo);
    }

    public function deletarUsuarioFromPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
            $id = intval($_POST['delete_id']);
            $this->usuario->deletarUsuario($id);
            header("Location: /TCC/index.php?pagina=usuarios&msg=success");
            exit;
        }
        return false;
    }

    public function atualizarUsuarioFromPost() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['nome'])) {
        session_start();
        $nivelLogado = $_SESSION['user_level'] ?? ''; // corrigido

        $id = intval($_POST['id']);
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $nivel = trim($_POST['nivel']);
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        // Hierarquia: só permite atualizar usuários de níveis permitidos
        $permitidos = [
            'diretor' => ['diretor','gerente','supervisor','operario','setor-de-compras','setor-de-vendas'],
            'gerente' => ['supervisor','operario','setor-de-compras','setor-de-vendas'],
            'supervisor' => ['operario'],
            'operario' => [],
            'setor-de-compras' => [],
            'setor-de-vendas' => []
        ];

        if (!in_array($nivel, $permitidos[$nivelLogado] ?? [])) {
            $_SESSION['flash_error'] = "Você não tem permissão para atualizar usuários desse nível.";
            header("Location: /TCC/index.php?pagina=usuarios");
            exit;
        }

        $this->atualizarUsuario($id, $nome, $email, $nivel, $ativo);

        $_SESSION['flash_success'] = "Usuário atualizado com sucesso!";
        header("Location: /TCC/index.php?pagina=usuarios");
        exit;
    }
    return false;
}

}
?>
