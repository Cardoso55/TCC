<?php
require_once __DIR__ . '/../models/VendasModel.php';

class VendasController {

    public function criarSaida($dados) {
        return VendasModel::criarSaida($dados);
    }

    public function listarSaidas() {
        return VendasModel::listarSaidas();
    }

    public function aprovar($idPedido) {
    $res = VendasModel::aprovarPedido($idPedido);
    if ($res) {
        // Cria checklist para saída
        VendasModel::criarChecklistInicialSaida($idPedido);
        return ['sucesso' => true];
    } else {
        return ['erro' => 'Falha ao aprovar pedido.'];
    }
}


    public function recusar($idPedido) {
        $res = VendasModel::recusarPedido($idPedido);
        return ['sucesso' => $res];
    }

    public static function confirmarSaida($idChecklist, $idPedido, $idUsuario) {

    // 1. Concluir checklist
    VendasModel::concluirChecklist($idChecklist);

    // 2. Concluir pedido de saída
    VendasModel::concluirPedidoSaida($idPedido);

    // 3. Pegar info do pedido
    $info = VendasModel::getInfoPedidoSaida($idPedido);

    if (!$info) {
        return ['erro' => 'Pedido não encontrado'];
    }

    $idProduto = (int)$info['id_produto'];
    $quantidade = (int)$info['quantidade'];
    $precoUnitario = (float)$info['preco_unitario'];

    // 4. Baixar estoque
    VendasModel::baixarEstoque($idProduto, $quantidade);

    // 5. Registrar movimentação
    VendasModel::registrarMovimentacao($idProduto, $quantidade);

    // 6. Registrar venda
    VendasModel::registrarVenda($idProduto, $idUsuario, $quantidade, $precoUnitario);

    return ['sucesso' => true];
}
}
