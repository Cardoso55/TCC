<?php
header("Content-Type: text/plain; charset=utf-8");
session_start();

// verifica login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Erro: USER_ID não definido. Faça login novamente.";
    exit;
}

$USER_ID = $_SESSION['user_id'];

// monta comando enviando USER_ID pro python
$command = "set USER_ID={$USER_ID} && python export_replenishment_json.py 2>&1";

// executa
$output = shell_exec($command);

// resposta
echo "IA executada por USER_ID={$USER_ID}!\n\n";
echo $output;