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
}
