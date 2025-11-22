<?php 
require_once __DIR__ . '/../models/ChecklistModel.php';
require_once __DIR__ . '/../models/ProdutoModel.php';
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/PedidoReposicaoModel.php';

class ChecklistController {

    // Listar checklists
    public static function listar($filtros = []) {
        return ChecklistModel::listarChecklists($filtros);
    }

    // Criar checklist
    public static function criar($dados) {
        if (!isset($dados['tipo'], $dados['conteudo'], $dados['idUsuarios_TBL'])) {
            return ['erro' => 'Dados incompletos'];
        }
        return ChecklistModel::criarChecklist($dados);
    }

    // Confirmar checklist
    public static function confirmar($idChecklist, $idUsuario, $idPedido = null) {
        $idChecklist = (int)$idChecklist;
        $idUsuario = (int)$idUsuario;
        $idPedido = $idPedido !== null ? (int)$idPedido : null;

        // Pega detalhes do checklist
        $checklist = ChecklistModel::detalhesChecklist($idChecklist);
        if (!$checklist) die("Checklist não encontrado!");

        // Atualiza status do checklist
        ChecklistModel::confirmarChecklist($idChecklist, $idUsuario, $idPedido);

        // Atualiza pedido vinculado e estoque
        if ($idPedido) {
            $pedido = PedidoReposicaoModel::buscarPedidoParaCompra($idPedido);
            if ($pedido) {
                // Atualiza estoque
                ProdutoModel::atualizarEstoque($pedido['id_produto'], $pedido['quantidade'], 'entrada');

                // Atualiza status do pedido
                $conn = conectarBanco();
                $stmt = $conn->prepare("UPDATE pedidosreposicao_tbl SET status='confirmado', data_recebimento=NOW() WHERE id_pedido=?");
                $stmt->bind_param("i", $idPedido);
                $stmt->execute();
                $stmt->close();
                $conn->close();
            }
        }

        // Atualiza status da compra se todos checklists confirmados
        if (!empty($checklist['idCompra_TBL'])) {
            $todosConfirmados = ChecklistModel::todosConfirmadosPara($checklist['idCompra_TBL']);
            if ($todosConfirmados) {
                CompraModel::atualizarStatus($checklist['idCompra_TBL'], 'confirmado');
            }
        }

        // Redireciona para página de checklists com sucesso
        header("Location: /TCC/index.php?pagina=checklists&sucesso=1");
        exit;
    }

    // Gerar checklist automaticamente para compra
    public static function gerarParaCompra($idCompra, $idUsuario, $idProduto, $quantidade, $idPedido) {
        ChecklistModel::criarChecklist([
            'tipo' => 'entrada',
            'conteudo' => "Verificar recebimento de $quantidade unidades do produto ID $idProduto",
            'idUsuarios_TBL' => $idUsuario,
            'idCompra_TBL' => $idCompra,
            'idProduto_TBL' => $idProduto,
            'idPedidosReposicao_TBL' => $idPedido
        ]);
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
