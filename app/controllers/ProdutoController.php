<?php

require_once __DIR__ . '/../models/ProdutoModel.php';

class ProdutoController
{
    // LISTAGEM ============================================================
    public static function listarProdutos()
    {
        return ProdutoModel::buscarComEstoque();
    }

    // CADASTRO =============================================================
    public static function cadastrarProduto($dados, $arquivo)
    {
        return ProdutoModel::salvar($dados, $arquivo);
    }

    // EDIÇÃO ==============================================================
    public static function editarProduto($dados, $arquivo)
    {
        return ProdutoModel::editar($dados, $arquivo);
    }

    // EXCLUSÃO COM VERIFICAÇÃO ============================================
    public static function excluirProduto($id_produto)
    {
        // 1. Verifica se tem vínculos
        $vinculos = ProdutoModel::verificarVinculos($id_produto);

        if ($vinculos['temVinculos']) {
            // Retorna para o JS avisando que é preciso confirmar
            return [
                'sucesso' => false,
                'temVinculos' => true,
                'detalhes' => $vinculos['detalhes']
            ];
        }

        // 2. Soft delete direto
        ProdutoModel::excluir($id_produto);

        return ['sucesso' => true];
    }

    // CONFIRMAÇÃO DE EXCLUSÃO (CASCATA) ==================================
    public static function confirmarExclusao($id_produto)
    {
        ProdutoModel::deletarProdutoCascata($id_produto);
        return ['sucesso' => true];
    }

    // CRIAR REPOSIÇÃO =====================================================
    public static function criarReposicao($dados)
    {
        return ProdutoModel::criarReposicao($dados);
    }

    // FILTRO AJAX =========================================================
    public static function filtrarAjax($filtros)
    {
        return ProdutoModel::buscarFiltradoComOrdenacao($filtros);
    }

       public static function criarPedidoSaida(array $dados) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $idProduto = intval($dados['id_produto'] ?? 0);
        $quantidade = intval($dados['quantidade'] ?? 0);
        $observacao = trim($dados['observacao'] ?? '');

        if (!$idProduto || !$quantidade) {
            throw new Exception("Produto e quantidade são obrigatórios.");
        }

        $idUsuario = $_SESSION['user_id'] ?? null;
        if (!$idUsuario) {
            throw new Exception("Usuário não autenticado.");
        }

        $pdo = Conexao::getInstance(); // supondo que você tem um método singleton
        $sql = "INSERT INTO pedidossaida_tbl 
                (id_produto, id_usuario_solicitante, quantidade, status, origem, observacao, data_pedido, data_atualizacao)
                VALUES (:id_produto, :id_usuario, :quantidade, 'pendente', 'interno', :observacao, NOW(), NOW())";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id_produto', $idProduto, PDO::PARAM_INT);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':quantidade', $quantidade, PDO::PARAM_INT);
        $stmt->bindValue(':observacao', $observacao, PDO::PARAM_STR);

        if (!$stmt->execute()) {
            throw new Exception("Falha ao criar pedido de saída.");
        }

        return true;
    }
}

