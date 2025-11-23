<?php
require_once __DIR__ . '/../models/PedidoReposicaoModel.php';

class IAController
{
    /** exibe lista de pedidos gerados pela IA */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'] ?? null;

        $pedidos = PedidoReposicaoModel::listarPedidosIA();

        if (method_exists($this, 'loadView')) {
            $this->loadView('ia', compact('pedidos', 'user_id'));
            return;
        }

        $view = __DIR__ . '/../views/ia.php';
        if (file_exists($view)) {
            require $view;
            return;
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'pedidos' => $pedidos]);
    }

    /** atualiza status via AJAX */
    public function atualizarStatus()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // limpa QUALQUER output antes do JSON
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $action = $_POST['action'] ?? null;
        $user_id = $_SESSION['user_id'] ?? null;

        if (!$user_id) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Usuário não autenticado.']);
            return;
        }

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de pedido inválido.']);
            return;
        }

       if ($action === 'aprovar' || $action === 'aceitar') {

    $ok = PedidoReposicaoModel::aceitarPedido($id);
    $msg = $ok ? 'Pedido aprovado.' : 'Erro ao aprovar.';

        if ($ok) {
            // 🔥 GERA CHECKLIST AUTOMATICAMENTE
            require_once __DIR__ . '/../controllers/ChecklistController.php';
            
            $pedido = PedidoReposicaoModel::buscarPedidoParaCompra($id);

            if ($pedido) {
                require_once __DIR__ . '/../controllers/ChecklistController.php';

                $compra_id = null; // pode ser NULL
                $usuario_id = $user_id;
                $produto_id = $pedido['id_produto'] ?? 0;    // garante número
                $quantidade = $pedido['quantidade'] ?? 1;    // evita NULL
                $pedido_id = $id;

                ChecklistController::gerarParaCompra(
                    $compra_id,
                    $usuario_id,
                    $produto_id,
                    $quantidade,
                    $pedido_id
                );
            }

        }
    }
        elseif ($action === 'rejeitar' || $action === 'negar') {
            $ok = PedidoReposicaoModel::negarPedido($id);
            $msg = $ok ? 'Pedido rejeitado.' : 'Erro ao rejeitar.';
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
            return;
        }

        echo json_encode(['success' => $ok, 'message' => $msg]);
    }


    /** busca detalhes de um pedido */
    public function detalhes()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        header('Content-Type: application/json');

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID inválido.']);
            return;
        }

        $pedido = PedidoReposicaoModel::buscarPedidoParaCompra($id);
        if (!$pedido) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
            return;
        }

        echo json_encode(['success' => true, 'pedido' => $pedido]);
    }
}
