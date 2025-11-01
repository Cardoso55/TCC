<?php
require_once __DIR__ . '/../models/ProdutoModel.php';

class ProdutoController {
    public static function listarProdutos() {
        return ProdutoModel::buscarComEstoque();
    }

    public static function cadastrarProduto($dados, $arquivo) {
        return ProdutoModel::salvar($dados, $arquivo);
    }

    public static function editarProduto($dados, $arquivo) {
        return ProdutoModel::editar($dados, $arquivo);
    }

    public static function excluirProduto($id) {
        return ProdutoModel::excluir($id);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? null;

    if ($acao === 'cadastrar') {
        ProdutoController::cadastrarProduto($_POST, $_FILES);
    } elseif ($acao === 'editar') {
        ProdutoController::editarProduto($_POST, $_FILES);
    }

    header('Location: ../views/estoque.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao']) && $_GET['acao'] === 'excluir') {
    ProdutoController::excluirProduto($_GET['id']);
    header('Location: ../views/estoque.php');
    exit;
}
