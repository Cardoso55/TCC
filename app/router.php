<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ComprasController.php';
require_once __DIR__ . '/controllers/ProdutoController.php';
require_once __DIR__ . '/controllers/RelatorioController.php';
function router()
{
    $pagina = $_GET['pagina'] ?? 'dashboard';



    // ==========================================================
    // ROTAS PÚBLICAS
    // ==========================================================
    $rotasPublicas = ['login', 'trocar_senha', 'logout'];

    if (in_array($pagina, $rotasPublicas)) {
        switch ($pagina) {
            case 'login':
                require __DIR__ . '/views/login.php';
                exit;
            case 'trocar_senha':
                require __DIR__ . '/views/trocar_senha.php';
                exit;
            case 'logout':
                $auth = new AuthController();
                $auth->logout();
                exit;
        }
    }

    // ==========================================================
    // TODAS AS OUTRAS ROTAS → EXIGEM AUTENTICAÇÃO
    // ==========================================================
    AuthController::checkAuth();
    $nivelLogado = $_SESSION['user_level'] ?? '';

    // ==========================================================
    // BLOQUEAR ACESSO POR HIERARQUIA
    // ==========================================================
    $acessoRestrito = [
        'usuarios' => ['diretor', 'gerente', 'supervisor'],
        'checklist' => ['operario', 'supervisor', 'gerente', 'diretor', 'setor-de-vendas'],
    ];

    if (isset($acessoRestrito[$pagina]) && !in_array($nivelLogado, $acessoRestrito[$pagina])) {
        header("Location: /TCC/index.php?pagina=dashboard&msg=noaccess");
        exit;
    }

  
    switch ($pagina) {

        // ==========================================
        // VIEWS SIMPLES
        // ==========================================
        case 'dashboard':
        case 'perfil':
        case 'usuarios':
        case 'estoque':
        case 'compras':
        case 'reposicoes':
        case 'relatorios':
        case 'alertas':
        case 'ia':
        case 'configuracoes':
        case 'checklist':
            require __DIR__ . "/views/{$pagina}.php";
            break;

        // ==========================================
        // CONTROLLERS ESPECÍFICOS
        // ==========================================
        case 'detalhes_compra':
            $controller = new ComprasController();
            $controller->detalhes();
            break;

        case 'pdf_financeiro':
            RelatorioController::gerarPDFFinanceiro();
            break;
        case 'pdf_compras':
            RelatorioController::gerarPDFCompras();
            break;
        case 'pdf_vendas':
            RelatorioController::gerarPDFVendas();
            break;

        case 'produto':
            $controller = new ProdutoController();

            // FILTRO AJAX
            if ($_GET['acao'] ?? '' === 'filtrar') {

                $filtros = [
                    'codigo'      => $_GET['codigo'] ?? '',
                    'nome'        => $_GET['nome'] ?? '',
                    'categoria'   => $_GET['categoria'] ?? '',
                    'preco'       => $_GET['preco'] ?? '',
                    'quantidade'  => $_GET['quantidade'] ?? '',
                    'ordenar_por' => $_GET['ordenar_por'] ?? null,
                    'ordem'       => $_GET['ordem'] ?? null
                ];

                $produtos = ProdutoController::filtrar($filtros);

                header("Content-Type: application/json");
                echo json_encode($produtos);
                exit;
            }

            // ... código POST / GET atual ...

            // RECURSO: RECALCULAR MÍNIMOS
            if (isset($_GET['acao']) && $_GET['acao'] === 'recalcular_minimos') {
                header('Content-Type: application/json; charset=utf-8');

                try {
                    $res = $controller->recalcularMinimos();
                    echo json_encode($res);
                } catch (Throwable $e) {
                    http_response_code(500);
                    echo json_encode(['sucesso' => false, 'mensagem' => $e->getMessage()]);
                }
                exit;
            }


            // POST (CADASTRAR / EDITAR / REPOSIÇÃO / SAÍDA)
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $acao = $_POST['acao'] ?? '';

                switch ($acao) {
                    case 'cadastrar':
                        $controller::cadastrarProduto($_POST, $_FILES);
                        header('Location: ?pagina=estoque');
                        exit;

                    case 'editar':
                        $controller::editarProduto($_POST, $_FILES);
                        header('Location: ?pagina=estoque');
                        exit;

                    case 'criar': // reposição
                        $controller::criarReposicao($_POST);
                        echo "Pedido de reposição enviado com sucesso!";
                        exit;

                    case 'criar_saida': // nova saída de vendas
                        require_once __DIR__ . '/controllers/VendasController.php';
                        $vendasCtrl = new VendasController();
                        $res = $vendasCtrl->criarSaida($_POST);
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($res);
                        exit;
                }   
            }

            // GET (FILTRAR / ORDENAR / EXCLUIR)
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['acao'])) {
                $acao = $_GET['acao'];

                switch ($acao) {
                    case 'filtrar':
                    case 'ordenar':
                        $produtos = $controller::filtrarAjax($_GET);
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($produtos);
                        exit;

                    case 'excluir':
                        $id = isset($_GET['id_produto']) ? (int) $_GET['id_produto'] : 0;
                        $resultado = $controller::excluirProduto($id);
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode($resultado);
                        exit;
                }
            }
            
            break;

        case 'ia_atualizarStatus':
            require_once __DIR__ . '/controllers/IAController.php';
            $controller = new IAController();
            $controller->atualizarStatus();
            exit;


        // ==========================================
        // OUTRAS ROTAS ESPECÍFICAS
        // ==========================================
        case 'requisicao':
            require_once __DIR__ . '/controllers/RequisicaoController.php';
            $controller = new RequisicaoController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST')
                exit;
            $controller->listar();
            break;

        // CONFIRMAR CHECKLIST (ENTRADA OU SAÍDA)
        case 'checklist_confirmar':
            require_once __DIR__ . '/controllers/ChecklistController.php';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $idChecklist = $_POST['idChecklist'] ?? null;
                $idUsuario = $_POST['idUsuario'] ?? null;
                $idPedido = $_POST['idPedido'] ?? null;

                if (!$idChecklist || !$idUsuario || !$idPedido) {
                    die("Erro: faltando dados para confirmar o checklist.");
                }

                ChecklistController::confirmar($idChecklist, $idUsuario, $idPedido);
                exit;
            }
            break;



        // ADICIONAR OBSERVAÇÃO AO CHECKLIST
        case 'checklist_observacao':
            require_once __DIR__ . '/controllers/ChecklistController.php';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                $idChecklist = $_POST['idChecklist'] ?? null;
                $observacao = $_POST['observacao'] ?? null;

                if (!$idChecklist) {
                    die("Erro: idChecklist não informado.");
                }

                ChecklistController::adicionarObservacao($idChecklist, $observacao);
                $tipo = $_POST['tipo'] ?? 'saída';
                header("Location: ?pagina=checklist&tipo=$tipo&obs=ok");
                exit;
            }
            break;


        case 'solicitacoes':
            require_once __DIR__ . '/controllers/SolicitacoesController.php';
            $controller = new SolicitacoesController();
            $controller->index();
            break;

        case 'solicitacao_detalhes':
            require_once __DIR__ . '/controllers/SolicitacoesController.php';
            $controller = new SolicitacoesController();
            $controller->detalhes($_GET['id'] ?? null);
            break;

        // ==========================================
        // NOVA ROTA: SAÍDAS
        // ==========================================
        case 'saidas':
            require_once __DIR__ . '/controllers/VendasController.php';
            $controller = new VendasController();
            $saidas = $controller->listarSaidas();
            require __DIR__ . '/views/saidas.php';
            break;

        // ==========================================
        // APROVAR / RECUSAR SAÍDA
        // ==========================================
        case 'aprovar_saida':
            require_once __DIR__ . '/controllers/VendasController.php';
            $vc = new VendasController();
            $res = $vc->aprovar($_POST['id_pedido_saida']);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($res);
            exit;

        case 'recusar_saida':
            require_once __DIR__ . '/controllers/VendasController.php';
            $vc = new VendasController();
            $res = $vc->recusar($_POST['id_pedido_saida']);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($res);
            exit;


        default:
            require __DIR__ . '/views/404.php';
            break;
    }
}
