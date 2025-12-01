<?php 
require_once __DIR__ . '/../models/ChecklistModel.php';
require_once __DIR__ . '/../models/ProdutoModel.php';
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/PedidoReposicaoModel.php';

class ChecklistController {

    // Listar checklists de saída (Setor de Vendas)
    public static function listarSaida($filtros = []) {
        return ChecklistModel::listarChecklistsSaida($filtros);
    }

    // Listar checklists de entrada (Estoque)
    public static function listarEntrada($filtros = []) {
        return ChecklistModel::listarChecklistsEntrada($filtros);
    }

    // Criar checklist
    public static function criar($dados) {
        if (!isset($dados['tipo'], $dados['conteudo'], $dados['idUsuarios_TBL'])) {
            return ['erro' => 'Dados incompletos'];
        }
        return ChecklistModel::criarChecklist($dados);
    }

    private static function processarSaidaEstoque($checklist, $idUsuario, $quantidade) {
    require_once __DIR__ . "/../models/Movimentacoes.php";
    require_once __DIR__ . "/../models/EstoqueModel.php";

    $idProduto = $checklist['idProduto_TBL'];
    $quantidade = $pedidoSaida['quantidade'] ?? $quantidade;

    $estoque = EstoqueModel::buscarPorProduto($idProduto);
    if (!$estoque) die("Erro: Estoque não encontrado para o produto {$idProduto}.");

    $idEstoque = $estoque['id_estoque'];
    $novaQuantidade = $estoque['quantidade_atual'] - $quantidade; // aqui debita a venda

    // Atualiza estoque
    EstoqueModel::atualizarQuantidade($idEstoque, $idProduto, $novaQuantidade);

    // Registra movimentação de saída
    MovimentacoesModel::registrarMovimentacao(
        $idUsuario,
        $idEstoque,
        $idProduto,
        $quantidade,
        'saida',
        'Checklist saída',
        'Movimentação gerada automaticamente ao confirmar checklist de saída'
    );
}


    // Confirmar checklist (mesma função que você já tinha)
    // Confirmar checklist (atualiza e dispara ações específicas)
public static function confirmar($idChecklist, $idUsuario, $idPedidoRecebido) {
    
    $checklist = ChecklistModel::buscarPorId($idChecklist);
    if (!$checklist) die("Checklist não encontrado.");

    $idCompra = $checklist['idCompra_TBL'] ?? null;
    $idReposicao = $checklist['idPedidosReposicao_TBL'] ?? null;

    // Confirma checklist
    ChecklistModel::confirmarChecklist($idChecklist, $idUsuario);

    // Confirma compra, se houver
    if ($idCompra) {
        require_once __DIR__ . "/../models/CompraModel.php";
        CompraModel::marcarComoConcluida($idCompra);
        $compra = CompraModel::buscarPorId($idCompra);
        $quantidade = $compra['quantidade'] ?? 1;
    }

    // Confirma pedido de reposição, se houver
    if ($idReposicao) {
        require_once __DIR__ . "/../models/PedidoReposicaoModel.php";
        PedidoReposicaoModel::marcarComoConcluido($idReposicao);
        $pedido = PedidoReposicaoModel::buscarPorId($idReposicao);
        $quantidade = $pedido['quantidade'] ?? ($quantidade ?? 1);
    }

    // 1) Se veio de compra, usa quantidade da compra
    if ($idCompra) {
        $compra = CompraModel::buscarPorId($idCompra);
        $quantidade = $compra['quantidade'] ?? null;
    }

    // 2) Se veio de reposição, usa quantidade da reposição
    if ($idReposicao) {
        $pedido = PedidoReposicaoModel::buscarPorId($idReposicao);
        $quantidade = $pedido['quantidade'] ?? $quantidade;
    }

    // 3) Se veio de pedido de saída, pega quantidade CORRETA
    if (!empty($checklist['idPedidosSaida_TBL'])) {
        require_once __DIR__ . "/../models/PedidoSaidaModel.php";
        $pedidoSaida = PedidoSaidaModel::getPedidoById($checklist['idPedidosSaida_TBL']);
        $quantidade = $pedidoSaida['quantidade'] ?? $quantidade;
    }

    // 4) Se mesmo assim não tem quantidade, ERRO
    if (!$quantidade) {
        die("Erro: Não foi possível determinar a quantidade da movimentação.");
    }


    // Processa entrada ou saída
    if (!empty($checklist['idProduto_TBL'])) {
        if ($checklist['tipo'] === 'saída') {

            $idPedidoSaida = $checklist['idPedidosSaida_TBL'] ?? null;
            $idUsuario = $_SESSION['user_id'] ?? null;
            $idProduto = $checklist['idProduto_TBL'];


            // Chamando a função isolada para venda/saída
            self::processarSaidaEstoque($checklist, $idUsuario, $quantidade);
            // Atualiza o status do pedido de saída
            if ($idPedidoSaida) {
                PedidoSaidaModel::marcarComoConcluido($idPedidoSaida);
            }


            // Registra venda (se quiser rastrear)
           PedidoSaidaModel::registrarVenda(
                $idProduto,
                $idUsuario,
                $quantidade
            );


        } else {
            // Entrada normal
            require_once __DIR__ . "/../models/Movimentacoes.php";
            require_once __DIR__ . "/../models/EstoqueModel.php";

            $idProduto = $checklist['idProduto_TBL'];
            $estoque = EstoqueModel::buscarPorProduto($idProduto);
            if (!$estoque) die("Erro: Estoque não encontrado para o produto {$idProduto}.");

            $idEstoque = $estoque['id_estoque'];
            $novaQuantidade = $estoque['quantidade_atual'] + $quantidade;

            // Atualiza estoque
            EstoqueModel::atualizarQuantidade($idEstoque, $idProduto, $novaQuantidade);

            // Registra movimentação de entrada
            MovimentacoesModel::registrarMovimentacao(
                $idUsuario,
                $idEstoque,
                $idProduto,
                $quantidade,
                'entrada',
                'Checklist entrada',
                'Movimentação gerada automaticamente ao confirmar checklist'
            );
        }
    }

    header("Location: ?pagina=checklist&tipo=" . $checklist['tipo'] . "&sucesso=1");
    exit;
}





    // Adicionar observação
    public static function adicionarObservacao($idChecklist, $observacao) {
        return ChecklistModel::adicionarObservacao($idChecklist, $observacao);
    }

    // Detalhes do checklist
    public static function detalhes($idChecklist) {
        return ChecklistModel::detalhesChecklist($idChecklist);
    }
}