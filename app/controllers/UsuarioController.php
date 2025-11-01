<?php

require_once '../models/Usuario.php' ;

class UsuarioController {
    private $usuario;

    public function __construct() {
        $this->usuario = new Usuario(); // Instancia o modelo Usuario que vem de models/Usuario.php
    }

    public function criarUsuarioFromPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'])) {
            $nome = $_POST['name'];
            $email = $_POST['email'];
            $senha = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $nivel = $_POST['level'];

            $usuarioModel = new Usuario();
            $resultado = $usuarioModel->criarUsuario($nome, $email, $senha, $nivel);

            if ($resultado) {
                // ✅ Redireciona para evitar reenvio do POST
                header("Location: usuarios.php?msg=success");
                exit;
            } else {
                header("Location: usuarios.php?msg=error");
                exit;
            }
        }
    }

    public function listarUsuarios() {
        return $this->usuario->listarUsuario();
    }

    public function buscarUsuario($id) {
        return $this->usuario->buscarUsuarioPorId($id);
    }

    public function atualizarUsuario($id, $nome, $email, $cargo, $ativo) {
        return $this->usuario->atualizarUsuario($id, $nome, $email, $cargo, $ativo);
    }

    public function deletarUsuarioFromPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
            $id = intval($_POST['delete_id']);
            $this->usuario->deletarUsuario($id);
            header("Location: usuarios.php");
            exit;
        }
        return false;
    }

    
    public function atualizarUsuarioFromPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['nome'])) {
            // sanitize básico
            $id = intval($_POST['id']);
            $nome = trim($_POST['nome']);
            $email = trim($_POST['email']);
            $nivel = trim($_POST['nivel']);
            // checkbox 'ativo' só aparece se marcado
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            $ok = $this->atualizarUsuario($id, $nome, $email, $nivel, $ativo);

            // redireciona para evitar reenvio do formulário
            header("Location: usuarios.php");
            exit;
        }
        return false;
    }


}

?>