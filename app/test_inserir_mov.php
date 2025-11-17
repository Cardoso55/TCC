<?php
require_once "./database/conexao.php";

$conn = conectarBanco();

$idUsuario = 4; // pode ser qualquer usuário

// Buscar produtos existentes no estoque
$produtos = $conn->query("SELECT id_produto FROM produtos_tbl LIMIT 5");

while ($p = $produtos->fetch_assoc()) {
    $idProduto = $p['id_produto'];

    // Cria movimentação antiga (40 dias atrás — deve gerar alerta)
    $conn->query("
        INSERT INTO movimentacoes_tbl 
        (quantidade, tipo, origem, observacao, data_movimentacao, idUsuarios_TBL, idEstoque_TBL, idProdutos_TBL)
        VALUES 
        (5, 'saida', 'teste', 'mov antiga', DATE_SUB(NOW(), INTERVAL 40 DAY), $idUsuario, NULL, $idProduto)
    ");

    // Cria movimentação recente (5 dias atrás — NÃO deve alertar)
    $conn->query("
        INSERT INTO movimentacoes_tbl 
        (quantidade, tipo, origem, observacao, data_movimentacao, idUsuarios_TBL, idEstoque_TBL, idProdutos_TBL)
        VALUES 
        (3, 'saida', 'teste', 'mov recente', DATE_SUB(NOW(), INTERVAL 5 DAY), $idUsuario, NULL, $idProduto)
    ");
}

echo "Movimentações falsas inseridas com sucesso!";
