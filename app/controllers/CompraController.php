<?php
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/PedidoReposicao.php';

class CompraController
{
    public function detalhes(){
    if (!isset($_GET['id'])) {
        die("ID da compra não informado.");
    }

    $id = intval($_GET['id']);

    // Busca a compra e garante que existe
    $compra = CompraModel::buscarCompraPorId($id);
    if (!$compra) {
        die("Erro: compra não encontrada.");
    }

    // Busca pedidos vinculados à compra
    $pedidos = CompraModel::listarPedidosDaCompra($id);

    // Se a função retornar false, transforma em array vazio
    if (!$pedidos || !is_array($pedidos)) {
        $pedidos = [];
    }

    // Inclui a view
    require __DIR__ . '/../views/detalhes_compra.php';
}

        
    }

