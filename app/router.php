<?php

function router() {

    $pagina = $_GET['pagina'] ?? 'dashboard';
    require_once __DIR__ . '/controllers/ComprasController.php';


    switch($pagina) {
        case 'dashboard':
            require __DIR__ . '/views/dashboard.php';
            break;
        case 'perfil':
            require __DIR__ . '/views/perfil.php';
            break;
        case 'usuarios':
            require __DIR__ . '/views/usuarios.php';
            break;
        case 'estoque':
            require __DIR__ . '/views/estoque.php';
            break;
        case 'compras':
            require __DIR__ . '/views/compras.php';
            break;
        case 'reposicoes':
            require __DIR__ . '/views/reposicoes.php';
            break;
        case 'solicitacoes':
            require __DIR__ . '/views/solicitacoes.php';
            break;
        case 'relatorios':
            require __DIR__ . '/views/relatorios.php';
            break;
        case 'alertas':
            require __DIR__ . '/views/alertas.php';
            break;
        case 'ia':
            require __DIR__ . '/views/ia.php';
            break;
        case 'configuracoes':
            require __DIR__ . '/views/configuracoes.php';
            break;

        // Login e Logout
        case 'login':
            require __DIR__ . '/views/auth/login.php';
            break;

        case 'logout':
            require __DIR__ . '/helpers/logout.php';
            break;

        case 'detalhes_compra':
            $controller = new ComprasController();
            $controller->detalhes();
            break;

    
        case 'requisicao':
        require __DIR__ . '/controllers/RequisicaoController.php';
        break;

        case 'checklists':
            require __DIR__ . '/views/checklist.php';
            break;
        
        case 'ia_atualizarStatus':
            require_once __DIR__ . '/controllers/IAController.php';
            $controller = new IAController();
            $controller->atualizarStatus();
        exit;


      case 'checklist_confirmar':
        require_once __DIR__ . '/controllers/ChecklistController.php';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idChecklist = $_POST['idChecklist'] ?? null;
            $idUsuario = $_POST['idUsuario'] ?? null;
            $idPedido = $_POST['idPedido'] ?? null; // precisa vir do form

            // Valida se todos os dados existem
            if (!$idChecklist || !$idUsuario || !$idPedido) {
                die("Erro: faltando dados para confirmar o checklist.");
            }

            ChecklistController::confirmar($idChecklist, $idUsuario, $idPedido);
            // A função já redireciona, então não precisa de header aqui
            exit;
        }
        break;



        case 'checklist_observacao':
            require_once __DIR__ . '/controllers/ChecklistController.php';
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $idChecklist = $_POST['idChecklist'];
                $observacao = $_POST['observacao'];
                ChecklistController::adicionarObservacao($idChecklist, $observacao);
                header('Location: ?pagina=checklists');
                exit;
            }
            break;



        default:
            echo "Página não encontrada";
            break;
    }
}
