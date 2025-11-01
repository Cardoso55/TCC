<?php

require_once '../models/Usuario.php' ;

class UsuarioController {
    private $usuario;

    public function __construct() {
        $this->usuario = new Usuario(); // Instancia o modelo Usuario que vem de models/Usuario.php
    }

    public function criarUsuarioFromPost() {
        if(isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['level'])) {
            $nome = $_POST['name'];
            $email = $_POST['email'];
            $senha = password_hash($_POST['password'], PASSWORD_DEFAULT); // hash da senha
            $nivel = $_POST['level'];

            return $this->usuario->criar($nome, $email, $senha, $nivel);
        }
        return false;
    }

    public function listarUsuarios() {
        return $this->usuario->listar();
    }

    public function buscarUsuario($id) {
        return $this->usuario->buscarPorId($id);
    }

    public function atualizarUsuario($id, $nome, $email, $cargo, $ativo) {
        return $this->usuario->atualizar($id, $nome, $email, $cargo, $ativo);
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