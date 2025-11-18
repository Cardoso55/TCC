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
    // primeiro apaga os pedidos de reposição relacionados
    ProdutoModel::excluirPedidosReposicaoDoProduto($id);
    // depois apaga o produto
    return ProdutoModel::excluir($id);
}


    // no ProdutoModel.php
public static function criarReposicao($dados) {
    $db = conectarBanco();

    $id_produto = (int)($dados['id_produto'] ?? 0);
    $quantidade = (int)($dados['quantidade'] ?? 0);
    $fornecedor = $db->real_escape_string($dados['fornecedor'] ?? '');

    if ($id_produto <= 0 || $quantidade <= 0) {
        return false;
    }

    $stmt = $db->prepare("INSERT INTO pedidosreposicao_tbl (id_produto, quantidade, fornecedor, data_pedido) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $id_produto, $quantidade, $fornecedor);
    $stmt->execute();
    $stmt->close();
    $db->close();

    return true;
}


    public static function filtrarAjax($filtros) {
        return ProdutoModel::buscarFiltradoComOrdenacao($filtros);
    }

}

// ------------------ ROTEAMENTO ------------------

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? null;

    switch($acao) {
        case 'cadastrar':
            ProdutoController::cadastrarProduto($_POST, $_FILES);
            header('Location: ../views/estoque.php');
            exit;

        case 'editar':
            ProdutoController::editarProduto($_POST, $_FILES);
            header('Location: ../views/estoque.php');
            exit;

        case 'excluir':
            ProdutoController::excluirProduto($_POST['id_produto'] ?? 0);
            header('Location: ../views/estoque.php');
            exit;

        case 'criar':
            ProdutoController::criarReposicao($_POST);
            echo "Pedido de reposição enviado com sucesso!";
            exit;

        default:
            echo "Ação inválida.";
            exit;
    }
}

// GET
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    $acao = $_GET['acao'];

    switch($acao) {
        case 'filtrar':
        case 'ordenar':
            $produtos = ProdutoController::filtrarAjax($_GET);
            header('Content-Type: application/json');
            echo json_encode($produtos);
            exit;

        case 'excluir':
            ProdutoController::excluirProduto($_GET['id'] ?? 0);
            header('Location: ../views/estoque.php');
            exit;

        default:
            echo "Ação inválida.";
            exit;
    }
}

