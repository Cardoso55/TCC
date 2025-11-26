<?php
require_once __DIR__ . '/../models/VendasModel.php';

class VendasController {

    // Cria um pedido de saída
    public function criarSaida($dados) {
        return VendasModel::criarSaida($dados);
    }

    // Lista todos os pedidos de saída
    public function listarSaidas() {
        return VendasModel::listarSaidas();
    }

    // Aprova pedido de saída e cria checklist inicial
    public function aprovar($idPedido) {
        $res = VendasModel::aprovarPedido($idPedido);
        if ($res) {
            $idChecklist = VendasModel::criarChecklistInicialSaida($idPedido);
            return ['sucesso' => true, 'idChecklist' => $idChecklist];
        }
        return ['erro' => 'Falha ao aprovar pedido.'];
    }

    // Recusa pedido de saída
    public function recusar($idPedido) {
        $res = VendasModel::recusarPedido($idPedido);
        return ['sucesso' => $res];
    }

    // Confirma saída: conclui checklist, baixa estoque, registra venda e movimentação
    public static function confirmarSaida($idChecklist, $idPedido, $idUsuario) {
        $vm = new VendasModel();
        return $vm->finalizarChecklistSaida($idChecklist, $idPedido, $idUsuario);
    }
}
