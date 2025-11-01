<?php
require_once __DIR__ . '/../database/conexao.php';

class Usuario {
    private $conn;

    public function __construct() {
        $this->conn = conectarBanco(); // função que vem de conexao.php
    }

    //  Cadastra um novo usuário
    public function criarUsuario($nome, $email, $senha, $nivel) {
        $sql = "INSERT INTO usuarios_tbl (nome, email, senha_hash, nivel, data_criacao, ativo) VALUES (?, ?, ?, ?, NOW(), 1)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssss", $nome, $email, $senha, $nivel);
        return $stmt->execute();
    }

    // Busca todos os usuários
    public function listarUsuario() {
        $sql = "SELECT * FROM usuarios_tbl";
        $resultado = $this->conn->query($sql);
        return $resultado->fetch_all(MYSQLI_ASSOC);
    }

    // Busca um usuário por ID
    public function buscarUsuarioPorId($id) {
        $sql = "SELECT * FROM usuarios_tbl WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Atualiza um usuário
    public function atualizarUsuario($id, $nome, $email, $nivel, $ativo) {
        $sql = "UPDATE usuarios_tbl SET nome=?, email=?, nivel=?, ativo=? WHERE id_usuario=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssii", $nome, $email, $nivel, $ativo, $id);
        return $stmt->execute();
    }

    // Deleta um usuário
    public function deletarUsuario($id) {
        $sql = "DELETE FROM usuarios_tbl WHERE id_usuario=?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Valida login
    // login
    public function login($email, $senha) {
        $sql = "SELECT * FROM usuarios_tbl WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($senha, $result['senha_hash'])) {
            return $result;
        }
        return false;
    }

    // atualizar último login
    public function atualizarUltimoLogin($id) {
        $sql = "UPDATE usuarios_tbl SET ultimo_login = NOW() WHERE id_usuario = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }


}
?>
