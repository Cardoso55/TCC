<?php
require_once __DIR__ . '/../services/EstoqueService.php';

class EstoqueController
{
    /**
     * Recalcula o estoque mínimo de TODOS os produtos.
     */
    public function revisarTodosMinimos()
    {
        header("Content-Type: application/json; charset=UTF-8");

        try {
            $service = new EstoqueService();
            $service->recalcularTodosMinimos();

            echo json_encode([
                'status' => 'ok',
                'mensagem' => 'Estoque mínimo de todos os produtos recalculado com sucesso!'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Erro ao recalcular os mínimos.',
                'erro' => $e->getMessage()
            ]);
        }
    }

    /**
     * Recalcula o estoque mínimo de UM produto específico.
     */
    public function revisarMinimoProduto()
    {
        header("Content-Type: application/json; charset=UTF-8");

        $id_produto = $_POST['id_produto'] ?? null;

        if (!$id_produto) {
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'ID do produto não informado.'
            ]);
            return;
        }

        try {
            $service = new EstoqueService();
            $service->recalcularMinimoProduto($id_produto);

            echo json_encode([
                'status' => 'ok',
                'mensagem' => "Estoque mínimo do produto #{$id_produto} recalculado com sucesso!"
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'erro',
                'mensagem' => 'Erro ao recalcular o mínimo do produto.',
                'erro' => $e->getMessage()
            ]);
        }
    }
}
