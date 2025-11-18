<?php
require_once __DIR__ . '/../database/conexao.php';

class CompraModel
{
    /* ===============================
       1. CRIAR COMPRA
       - valor_total começa como 0
    =============================== */
    public static function criarCompra($fornecedor, $valorTotal, $idUsuario)
{
    $db = conectarBanco();

    // Verifica se usuário existe
    $stmtCheck = $db->prepare("SELECT id_usuario FROM usuarios_tbl WHERE id_usuario = ?");
    $stmtCheck->bind_param("i", $idUsuario);
    $stmtCheck->execute();
    $res = $stmtCheck->get_result();
    if ($res->num_rows === 0) {
        throw new Exception("Usuário com ID $idUsuario não existe. Compra não pode ser criada.");
    }

    $sql = "INSERT INTO compras_tbl (fornecedor, valor_total, data_compra, idUsuarios_TBL)
            VALUES (?, ?, NOW(), ?)";
    $stmt = $db->prepare($sql);
    $stmt->bind_param("sdi", $fornecedor, $valorTotal, $idUsuario);
    $stmt->execute();

    return $db->insert_id;
}


    /* ===============================
       2. VINCULAR PEDIDOS À COMPRA
    =============================== */
    public static function vincularPedidosACompra($id_compra, $id_pedido)
    {
        $db = conectarBanco();

        $sql = "UPDATE pedidosreposicao_tbl 
                SET id_compra = ?
                WHERE id_pedido = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("ii", $id_compra, $id_pedido);
        return $stmt->execute();
    }

    /* ===============================
       3. LISTAR TODAS AS COMPRAS
    =============================== */
    public static function listarCompras()
    {
        $db = conectarBanco();

        $sql = "SELECT 
                    c.id_compra,
                    c.fornecedor,
                    c.valor_total,
                    c.data_compra,
                    u.nome AS nome_usuario
                FROM compras_tbl c
                LEFT JOIN usuarios_tbl u 
                       ON u.id_usuario = c.idUsuarios_TBL
                ORDER BY c.id_compra DESC";

        $result = $db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /* ===============================
       4. BUSCAR COMPRA POR ID
    =============================== */
    public static function buscarCompraPorId($id)
    {
        $db = conectarBanco();

        $sql = "SELECT 
                    c.*,
                    u.nome AS nome_usuario
                FROM compras_tbl c
                LEFT JOIN usuarios_tbl u 
                       ON u.id_usuario = c.idUsuarios_TBL
                WHERE id_compra = ?";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    /* ===============================
       5. LISTAR PEDIDOS DA COMPRA
          (somente a-caminho + confirmado)
    =============================== */
    public static function listarPedidosDaCompra($idCompra)
{
    $db = conectarBanco();

    $sql = "SELECT 
                p.id_pedido,
                p.id_compra,
                p.status,
                p.fornecedor,
                p.data_pedido,
                p.data_recebimento,
                p.idUsuarios_TBL,
                p.id_produto,
                p.quantidade,
                prod.preco_unitario,
                (p.quantidade * prod.preco_unitario) AS total_item,
                prod.nome,
                u.nome AS nome_usuario
            FROM pedidosreposicao_tbl p
            LEFT JOIN produtos_tbl prod 
                   ON prod.id_produto = p.id_produto
            LEFT JOIN usuarios_tbl u
                   ON u.id_usuario = p.idUsuarios_TBL
            WHERE p.id_compra = ?
              AND (p.status = 'a-caminho' OR p.status = 'confirmado')
            ORDER BY p.status DESC, p.data_pedido DESC";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
        // erro na preparação da query, retorna array vazio
        return [];
    }

    $stmt->bind_param("i", $idCompra);
    if (!$stmt->execute()) {
        // erro na execução, retorna array vazio
        return [];
    }

    $result = $stmt->get_result();
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC); // sempre retorna array
}


    /* ===============================
       6. ATUALIZAR VALOR TOTAL DA COMPRA
          -> soma apenas itens confirmados
    =============================== */
    public static function atualizarValorTotal($id_compra)
    {
        $db = conectarBanco();

        $sql = "UPDATE compras_tbl c
                JOIN (
                    SELECT 
                        id_compra, 
                        SUM(quantidade * valor_unitario) AS total_confirmado
                    FROM pedidosreposicao_tbl
                    WHERE status = 'confirmado'
                    AND id_compra = ?
                ) t ON c.id_compra = t.id_compra
                SET c.valor_total = IFNULL(t.total_confirmado, 0)";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id_compra);
        return $stmt->execute();
    }
    /* ===============================
    7. ATUALIZAR STATUS DA COMPRA
    =============================== */
    public static function atualizarStatus($id_compra, $status)
    {
        $db = conectarBanco();

        // Primeiro, checa se a coluna 'status' existe
        $colunaStatus = $db->query("SHOW COLUMNS FROM compras_tbl LIKE 'status'");
        if ($colunaStatus->num_rows === 0) {
            // Cria coluna se não existir
            $db->query("ALTER TABLE compras_tbl ADD COLUMN status VARCHAR(50) DEFAULT 'a-caminho'");
        }

        $sql = "UPDATE compras_tbl SET status = ? WHERE id_compra = ?";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Erro ao preparar query de atualização de status: " . $db->error);
        }

        $stmt->bind_param("si", $status, $id_compra);
        if (!$stmt->execute()) {
            throw new Exception("Erro ao executar atualização de status: " . $stmt->error);
        }

        $stmt->close();
        $db->close();
        return true;
    }
}
 