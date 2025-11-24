<?php

require_once __DIR__ . '/../models/PedidoReposicaoModel.php';
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

    // EXCLUSÃO ============================================================
    public static function excluirProduto($id_produto)
    {
        $vinculos = ProdutoModel::verificarVinculos($id_produto);

        // Checa se a chave existe e se há vínculos
        if (!empty($vinculos['temVinculos'])) {
            return [
                'sucesso' => false,
                'temVinculos' => true,
                'detalhes' => $vinculos['detalhes'] ?? []
            ];
        }

        // Se não houver vínculos, exclui normalmente
        ProdutoModel::excluir($id_produto);

        return ['sucesso' => true];
    }

    public static function confirmarExclusao($id_produto)
    {
        ProdutoModel::deletarProdutoCascata($id_produto);
        return ['sucesso' => true];
    }

    // CRIAR REPOSIÇÃO =====================================================
    public static function criarReposicao($dados)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $idUsuario = $_SESSION['user_id'] ?? null;
        $cargo = $_SESSION['user_level'] ?? null;

        if (!$idUsuario) {
            throw new Exception("Usuário não autenticado.");
        }

        // Dados necessários
        $idProduto = intval($dados['id_produto'] ?? 0);
        $quantidade = intval($dados['quantidade'] ?? 0);
        $descricao  = trim($dados['descricao'] ?? '');
        $fornecedor = trim($dados['fornecedor'] ?? '');

        return PedidoReposicaoModel::criarPedido(
            $idProduto,
            $quantidade,
            $fornecedor,
            $cargo,
            $idUsuario
        );

    }

    // SAÍDA ================================================================
    public static function criarPedidoSaida(array $dados)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $idProduto   = intval($dados['id_produto'] ?? 0);
        $quantidade  = intval($dados['quantidade'] ?? 0);
        $observacao  = trim($dados['observacao'] ?? '');

        if (!$idProduto || !$quantidade) {
            throw new Exception("Produto e quantidade são obrigatórios.");
        }

        $idUsuario = $_SESSION['user_id'] ?? null;
        if (!$idUsuario) {
            throw new Exception("Usuário não autenticado.");
        }

        $pdo = Conexao::getInstance();
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

