<?php
require_once __DIR__ . "/../database/conexao.php";

class MovimentacoesModel {

    public static function registrarMovimentacao($idUsuario, $idEstoque, $idProduto, $quantidade, $tipo, $origem, $observacao) {
    $conn = conectarBanco();

    $stmt = $conn->prepare("
        INSERT INTO movimentacoes_tbl 
            (idUsuarios_TBL, idEstoque_TBL, idProdutos_TBL, quantidade, tipo, origem, observacao, data_movimentacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iiiisss", $idUsuario, $idEstoque, $idProduto, $quantidade, $tipo, $origem, $observacao);
    $stmt->execute();

    $stmt->close();
    $conn->close();
    return true;
}
}
