<?php
require_once __DIR__ . "/../database/conexao.php";

/**
 * PedidoReposicaoModel (UNIFICADO E CONSERTADO)
 *
 * Fluxo:
 *  - Operário cria -> status = 'aguardando_aprovacao' (vai para supervisor)
 *  - Supervisor cria -> status = 'pendente' (vai direto p/ compras)
 *  - Supervisor aprova -> vira 'pendente'
 *  - Compras aceita -> vira 'a_caminho'
 *  - Checklist confirmado -> atualiza estoque e conclui compra
 *
 * Status: aguardando_aprovacao, pendente, a_caminho, negado, confirmado, em_compra
 */
class PedidoReposicaoModel
{
    // ---------------------------------------------------------------------
    // 1) CRIAÇÃO — assinatura antiga corrigida
    // ---------------------------------------------------------------------
    public static function criarPedido(
    $id_produto,
    $quantidade,
    $fornecedor,
    $cargo_usuario,
    $id_usuario
) {
    $db = conectarBanco();

    // Definir nível e status
    if ($cargo_usuario === 'operario') {
        $nivel = 'supervisor';
        $status = 'aguardando_aprovacao';
    } else {
        $nivel = 'setor-de-compras';
        $status = 'pendente';
    }

    $sql = "INSERT INTO pedidosreposicao_tbl
            (id_produto, quantidade, fornecedor, nivel_aprovacao, status, idUsuarios_TBL, data_pedido)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        return [
            'success' => false,
            'error' => 'Erro ao preparar statement: ' . $db->error
        ];
    }

    $stmt->bind_param(
        "issssi",   // tipos
        $id_produto,
        $quantidade,
        $fornecedor,
        $nivel,
        $status,
        $id_usuario
    );

    if ($stmt->execute()) {
        return [
            'success' => true,
            'message' => 'Pedido criado com sucesso',
            'nivel_aprovacao' => $nivel
        ];
    }

    return [
        'success' => false,
        'error' => 'Erro ao executar: ' . $stmt->error
    ];
}

    // ---------------------------------------------------------------------
    // 1b) CRIAÇÃO via array (usada pelo router)
    // ---------------------------------------------------------------------
    public static function criarPedidoFromArray(array $dados, string $cargo_usuario = 'operario')
    {
        $id_produto = (int)($dados['id_produto'] ?? 0);
        $quantidade = (int)($dados['quantidade'] ?? 0);
        $fornecedor = $dados['fornecedor'] ?? null;
        $id_usuario = (int)($dados['id_usuario'] ?? $dados['idUsuarios_TBL'] ?? 0);

        if (!$id_produto || !$quantidade || !$id_usuario) {
            return ['success' => false, 'error' => 'Dados insuficientes'];
        }

        return self::criarPedido($id_produto, $quantidade, $fornecedor, $cargo_usuario, $id_usuario);
    }

    // ---------------------------------------------------------------------
    // 2) CRIAÇÃO IA
    // ---------------------------------------------------------------------
    public static function criarPedidoIA($id_produto, $quantidade, $id_usuario, $data_prevista_chegada = null)
    {
        $db = conectarBanco();

        $sql = "INSERT INTO pedidosreposicao_tbl
                (id_produto, quantidade, fornecedor, status, data_pedido,
                 idUsuarios_TBL, gerado_por_ia, data_prevista_chegada)
                VALUES (?, ?, NULL, 'pendente', NOW(), ?, 1, ?)";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("iiis", $id_produto, $quantidade, $id_usuario, $data_prevista_chegada);

        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    // ---------------------------------------------------------------------
    // 3) LISTAR TODOS
    // ---------------------------------------------------------------------
    public static function listarPedidos()
    {
        $db = conectarBanco();

        $sql = "SELECT 
                    p.*,
                    prod.nome,
                    est.quantidade_atual
                FROM pedidosreposicao_tbl p
                LEFT JOIN produtos_tbl prod ON prod.id_produto = p.id_produto
                LEFT JOIN estoque_tbl est ON est.idProdutos_TBL = p.id_produto
                ORDER BY p.data_pedido DESC";

        $res = $db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ---------------------------------------------------------------------
    // 4) LISTAR IA
    // ---------------------------------------------------------------------
    public static function listarPedidosIA()
    {
        $db = conectarBanco();
        $sql = "SELECT p.*, prod.nome
                FROM pedidosreposicao_tbl p
                LEFT JOIN produtos_tbl prod ON prod.id_produto = p.id_produto
                WHERE p.gerado_por_ia = 1
                ORDER BY p.data_pedido DESC";
        $res = $db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ---------------------------------------------------------------------
    // 5) BUSCAR POR ID
    // ---------------------------------------------------------------------
    public static function buscarPorId($id_pedido)
    {
        $db = conectarBanco();
        $id_pedido = (int)$id_pedido;

        $sql = "SELECT pr.*,
                       prod.nome AS nome_produto,
                       prod.valor_compra,
                       est.quantidade_atual,
                       est.quantidade_minima,
                       u.id_usuario, u.nome AS usuario_nome
                FROM pedidosreposicao_tbl pr
                LEFT JOIN produtos_tbl prod ON prod.id_produto = pr.id_produto
                LEFT JOIN estoque_tbl est ON est.idProdutos_TBL = pr.id_produto
                LEFT JOIN usuarios_tbl u ON u.id_usuario = pr.idUsuarios_TBL
                WHERE pr.id_pedido = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id_pedido);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    // ---------------------------------------------------------------------
    // 6) BUSCAR POR NÍVEL (solicitações para aprovação)
    // ---------------------------------------------------------------------
    public static function buscarPorNivel($nivel)
    {
        $db = conectarBanco();

        $sql = "SELECT p.id_pedido, p.id_produto, p.quantidade, p.status,
                       p.data_pedido, p.nivel_aprovacao, prod.nome
                FROM pedidosreposicao_tbl p
                INNER JOIN produtos_tbl prod ON prod.id_produto = p.id_produto
                WHERE p.nivel_aprovacao = ? AND p.status = 'aguardando_aprovacao'
                ORDER BY p.data_pedido DESC";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("s", $nivel);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // ---------------------------------------------------------------------
    // 7) APROVAR PEDIDO — supervisor aceita
    // ---------------------------------------------------------------------
    public static function aprovarPedido($id_pedido)
    {
        $db = conectarBanco();

        $sql = "UPDATE pedidosreposicao_tbl
                SET status = 'pendente',
                    nivel_aprovacao = 'setor-de-compras',
                    data_aprovacao = NOW()
                WHERE id_pedido = ? AND status = 'aguardando_aprovacao'";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id_pedido);

        return $stmt->execute();
    }

    // ---------------------------------------------------------------------
    // 8) NEGAR
    // ---------------------------------------------------------------------
    public static function negarPedido($id_pedido)
    {
        $db = conectarBanco();
        $sql = "UPDATE pedidosreposicao_tbl
                SET status = 'negado', data_aprovacao = NOW()
                WHERE id_pedido = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id_pedido);
        return $stmt->execute();
    }

    // ---------------------------------------------------------------------
    // 9) COMPRAS ACEITA → A CAMINHO
    // ---------------------------------------------------------------------
    public static function marcarAcaminho($id_pedido)
    {
        $db = conectarBanco();
        $sql = "UPDATE pedidosreposicao_tbl
                SET status = 'a_caminho', data_aprovacao = NOW()
                WHERE id_pedido = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id_pedido);
        return $stmt->execute();
    }

    // ---------------------------------------------------------------------
    // 10) Vincular compra
    // ---------------------------------------------------------------------
    public static function atualizarCompra($id_pedido, $id_compra)
    {
        $db = conectarBanco();
        $sql = "UPDATE pedidosreposicao_tbl
                SET id_compra = ?, status = 'em_compra'
                WHERE id_pedido = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $id_compra, $id_pedido);
        return $stmt->execute();
    }

    // ---------------------------------------------------------------------
    // 11) CHECKLIST FINALIZADO → atualizar estoque e concluir compra
    // ---------------------------------------------------------------------
    public static function confirmarChecklist($id_produto, $quantidade_recebida, $id_compra)
    {
        $db = conectarBanco();

        // Verifica / atualiza estoque
        $sql = "SELECT quantidade_atual FROM estoque_tbl WHERE idProdutos_TBL = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id_produto);
        $stmt->execute();
        $result = $stmt->get_result();
        $existe = $result->fetch_assoc();
        $stmt->close();

        if ($existe) {
            $sql = "UPDATE estoque_tbl SET quantidade_atual = quantidade_atual + ? 
                    WHERE idProdutos_TBL = ?";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $quantidade_recebida, $id_produto);
        } else {
            $sql = "INSERT INTO estoque_tbl (idProdutos_TBL, quantidade_atual, quantidade_minima)
                    VALUES (?, ?, 0)";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("ii", $id_produto, $quantidade_recebida);
        }

        $ok1 = $stmt->execute();
        $stmt->close();

        // Concluir compra
        $sql = "UPDATE compras_tbl SET status = 'concluida' WHERE id_compra = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id_compra);
        $ok2 = $stmt->execute();
        $stmt->close();

        return $ok1 && $ok2;
    }

    // ---------------------------------------------------------------------
    // 12) Buscar pedido para criar compra
    // ---------------------------------------------------------------------
    public static function buscarPedidoParaCompra($idPedido)
    {
        $db = conectarBanco();

        $sql = "SELECT 
                    p.*, prod.valor_compra, prod.nome
                FROM pedidosreposicao_tbl p
                INNER JOIN produtos_tbl prod ON prod.id_produto = p.id_produto
                WHERE p.id_pedido = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $idPedido);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // ---------------------------------------------------------------------
    // 13) Atualizar status manualmente
    // ---------------------------------------------------------------------
    public static function atualizarStatus($id_pedido, $status)
    {
        $db = conectarBanco();
        $sql = "UPDATE pedidosreposicao_tbl SET status = ? WHERE id_pedido = ?";
        $stmt = $db->prepare($sql);
        $stmt->bind_param("si", $status, $id_pedido);
        return $stmt->execute();
    }

   public static function atualizarAprovacao($id_pedido, $novoNivel, $novoStatus)
{
    $db = conectarBanco();

    $sql = "UPDATE pedidosreposicao_tbl
            SET nivel_aprovacao = ?, status = ?, data_aprovacao = NOW()
            WHERE id_pedido = ?";

    $stmt = $db->prepare($sql);

    if (!$stmt) {
        return ['success' => false, 'error' => $db->error];
    }

    $stmt->bind_param("ssi", $novoNivel, $novoStatus, $id_pedido);

    if ($stmt->execute()) {
        return ['success' => true];
    }

    return ['success' => false, 'error' => $stmt->error];
}
public static function rejeitarPedido($id_pedido) {
    $db = conectarBanco();
    $sql = "UPDATE pedidosreposicao_tbl 
            SET status = 'negado', data_aprovacao = NOW()
            WHERE id_pedido = ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_pedido);
    return $stmt->execute();
}



}
