<?php

function conectarBanco() {
    $host = "localhost";
    $user = "root";
    $password = "";
    $database = "macawsystems";

    // Criando conexão
    $conn = new mysqli($host, $user, $password, $database);

    // Checando conexão
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    // Set charset UTF-8
    $conn->set_charset("utf8");

    return $conn;
}

conectarBanco();

?>
