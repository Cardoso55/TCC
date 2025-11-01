<?php
require_once __DIR__ . '/../database/conexao.php';

class EstoqueModel {
    public static function salvar($estoque) {
        $db = Database::conectar();
        $stmt = $db->prepare("INSERT INTO estoque_tbl (idProduto, quantidade) VALUES (?, ?)");
        return $stmt->execute([$estoque['idProduto'], $estoque['quantidade']]);
    }
}
