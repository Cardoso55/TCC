<?php
require_once __DIR__ . '/../models/ChecklistModel.php';
require_once __DIR__ . '/../models/ProdutoModel.php';
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/PedidoReposicao.php';

class ChecklistController {

    // Listar checklists com filtros opcionais
    public static function listar($filtros = []) {
        return ChecklistModel::listarChecklists($filtros);
    }

    // Criar checklist (entrada ou saída)
    public static function criar($dados) {
        if (!isset($dados['tipo'], $dados['conteudo'], $dados['idUsuarios_TBL'])) {
            return ['erro' => 'Dados incompletos'];
        }
        return ChecklistModel::criarChecklist($dados);
    }

    // Confirmar checklist
    public static function confirmar($idChecklist, $idUsuario, $idPedido) {
        $idChecklist = (int)$idChecklist;
        $idUsuario = (int)$idUsuario;
        $idPedido    = $idPedido !== null ? (int)$idPedido : null;

        $checklist = ChecklistModel::detalhesChecklist($idChecklist);
        if (!$checklist) return ['erro' => 'Checklist não encontrado'];

        // Atualiza status e data do checklist
        ChecklistModel::confirmarChecklist($idChecklist, $idUsuario, $idPedido);

        // Atualiza idPedido se foi passado e não está no checklist
        if (!$checklist['idPedidosReposicao_TBL'] && $idPedido) {
            $conn = conectarBanco();
            $stmt = $conn->prepare("UPDATE Checklist_TBL SET idPedidosReposicao_TBL = ? WHERE id_checklist = ?");
            $stmt->bind_param("ii", $idPedido, $idChecklist);
            $stmt->execute();
            $stmt->close();
            $conn->close();
            $checklist['idPedidosReposicao_TBL'] = $idPedido;
        }

        // Atualiza pedido vinculado e estoque
        if ($checklist['idPedidosReposicao_TBL']) {
            $pedido = PedidoReposicao::buscarPedidoParaCompra($checklist['idPedidosReposicao_TBL']);
            if ($pedido) {
                // Atualiza pedido
                $conn = conectarBanco();
                $stmt = $conn->prepare("UPDATE pedidosreposicao_tbl SET status='confirmado', data_recebimento=NOW() WHERE id_pedido=?");
                $stmt->bind_param("i", $pedido['id_pedido']);
                $stmt->execute();
                $stmt->close();
                $conn->close();

                // Atualiza estoque
                ProdutoModel::atualizarEstoque($pedido['id_produto'], $pedido['quantidade'], 'entrada');
            }
        }

        // Atualiza status da compra se todos checklists confirmados
        if ($checklist['idCompra_TBL']) {
            $todosConfirmados = ChecklistModel::todosConfirmadosPara($checklist['idCompra_TBL'], null);
            if ($todosConfirmados) {
                CompraModel::atualizarStatus($checklist['idCompra_TBL'], 'confirmado');
            }
        }

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
