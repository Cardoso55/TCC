<?php
class ReposicaoController
{  /** confirma um pedido de reposição e cria a compra associada */
    public static function confirmarPedido($id_pedido)
    {
        require_once 'app/models/CompraModel.php';
        require_once 'app/models/PedidoReposicaoModel.php';

        // 1. Buscar dados do pedido
        $pedido = PedidoReposicaoModel::buscarPorId($id_pedido);

        if (!$pedido) {
            die("Pedido não encontrado");
        }

        // pega o valor de compra (produto.valor_compra * quantidade do pedido)
        $valor_total = $pedido['valor_compra'] * $pedido['quantidade']; 

        $fornecedor = $pedido['fornecedor']; 
        $idUsuario = $_SESSION['idUsuario']; // quem confirmou

        // 2. Criar a compra
        $id_compra = CompraModel::criarCompra($fornecedor, $valor_total, $idUsuario);

        // 3. Vincular pedido à compra
        CompraModel::vincularPedidosACompra($id_compra, $id_pedido);

        // 4. Atualizar status do pedido para “Confirmado”
        PedidoReposicaoModel::atualizarStatus($id_pedido, "Confirmado");

        return $id_compra;
    }
}
