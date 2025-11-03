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

    /**
     * Função que recebe um array de filtros (incluindo ordenação) e retorna os produtos.
     * Esta é a função que o AJAX deve chamar.
     */
    public static function filtrarAjax($filtros) {
        // A função buscarFiltradoComOrdenacao do Model já está preparada para lidar com todos os filtros
        return ProdutoModel::buscarFiltradoComOrdenacao($filtros);
    }

}

// --- ROTEAMENTO ---

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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
    $acao = $_GET['acao'];

    if ($acao === 'excluir') {
        ProdutoController::excluirProduto($_GET['id']);
        header('Location: ../views/estoque.php');
        exit;
    } 
    
    // 💡 ROTEAMENTO CORRIGIDO E UNIFICADO PARA FILTRO E ORDENAÇÃO
    if ($acao === 'filtrar' || $acao === 'ordenar') { // Trata ambas as ações na mesma função
        // Usa o array $_GET inteiro como filtros. O Model saberá o que fazer
        // com 'ordenar_por' e 'ordem' (ou com os campos de filtro de texto).
        $produtos = ProdutoController::filtrarAjax($_GET); 
        
        header('Content-Type: application/json');
        echo json_encode($produtos);
        exit;
    }
}

// ❌ Removido o bloco if (isset($_GET['acao']) && $_GET['acao'] === 'ordenar') 
//    porque a lógica agora é tratada no bloco unificado acima.
// ❌ Corrigido o bloco anterior de "filtrar" que estava chamando a função errada.