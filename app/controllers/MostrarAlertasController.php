<?php
require_once __DIR__ . "/../database/conexao.php";

$conn = conectarBanco();

$sql = "SELECT id_alerta, tipo, mensagem, nivel_prioridade, status, data_criacao
        FROM alertas_tbl
        ORDER BY data_criacao DESC
        LIMIT 20";

$result = $conn->prepare($sql);
$result->execute();
$result = $result->get_result();

$alertas = [];

while ($row = $result->fetch_assoc()) {

    $titulo = match($row['tipo']) {
        'estoque_baixo' => 'Estoque Baixo',
        'produto_parado' => 'Produto Parado',
        'validade' => 'Validade Próxima',
        default => 'Alerta'
    };

    $icone = match($row['tipo']) {
        'estoque_baixo' => '⚠️',
        'produto_parado' => '⏳',
        'validade' => '⛔',
        default => '🔔'
    };

    $alertas[] = [
        "id" => $row['id_alerta'],
        "tipo" => $row['tipo'],
        "titulo" => $titulo,
        "icone" => $icone,
        "mensagem" => $row['mensagem'],
        "status" => $row['status'],
        "data" => $row['data_criacao']
    ];
}

header("Content-Type: application/json");
echo json_encode($alertas);
