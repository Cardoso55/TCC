<?php
require_once __DIR__ . '/../database/conexao.php';
$conn = conectarBanco();

if (!isset($_POST['id'])) {
    exit('ID não enviado');
}

$id = $_POST['id'];

$stmt = $conn->prepare("UPDATE alertas_tbl SET status = 'visto' WHERE id_alerta = ?");
$stmt->execute([$id]);

echo "ok";
