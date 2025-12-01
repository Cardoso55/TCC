<?php
require_once __DIR__ . '/../models/EstoqueModel.php';

class EstoqueService
{
    /**
     * Recalcula o mínimo de estoque para TODOS os produtos
     * usando a lógica interna do EstoqueModel.
     */
    public function recalcularTodosMinimos()
    {
        return EstoqueModel::recalcularMinimosTodosProdutos();
    }

    /**
     * Recalcula a quantidade mínima de UM produto específico.
     * Retorna a nova quantidade mínima calculada.
     */
    public function recalcularMinimoProduto($idProduto)
    {
        return EstoqueModel::atualizarMinimoEstoque($idProduto);
    }

    /**
     * Calcula a quantidade mínima baseada em vendas (sem gravar no banco)
     * Apenas retorna o valor calculado.
     */
    public function calcularMinimoSimples($idProduto)
    {
        return EstoqueModel::calcularQuantidadeMinima($idProduto);
    }
}
