<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../models/PedidoReposicaoModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class SolicitacoesController {

    // Renderiza a página de solicitações
    public static function index() {
        $nivelUsuario = $_SESSION['user_level'] ?? 'supervisor';
        $pedidos = self::listarSolicitacoes($nivelUsuario);

        // Chama a view
        require __DIR__ . '/../views/solicitacoes.php';
    }

    // Lista os pedidos que precisam de aprovação do usuário logado
    public static function listarSolicitacoes($nivelUsuario) {
        return PedidoReposicaoModel::buscarPorNivel($nivelUsuario);
    }
}
