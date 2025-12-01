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
    public static function listarPedidosDaCompra($id_compra)
    {
        $db = conectarBanco();

        $sql = "
            SELECT 
                pr.id_pedido,
                pr.id_produto,
                pr.quantidade,
                pr.status,
                pr.data_pedido,
                p.nome,
                p.preco_unitario,
                (pr.quantidade * p.preco_unitario) AS total_item,
                u.nome AS nome_usuario
            FROM pedidosreposicao_tbl pr
            JOIN produtos_tbl p ON pr.id_produto = p.id_produto
            LEFT JOIN usuarios_tbl u ON pr.idUsuarios_TBL = u.id_usuario
            WHERE pr.id_compra = ?
            ORDER BY pr.id_pedido ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bind_param("i", $id_compra);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
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
                        SELECT pr.id_compra, SUM(pr.quantidade * p.valor_compra) AS total_confirmado
                        FROM pedidosreposicao_tbl pr
                        JOIN produtos_tbl p ON pr.id_produto = p.id_produto
                        WHERE pr.id_compra = ?
                        GROUP BY pr.id_compra
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

    public static function buscarPorId($idCompra) {
        $conn = conectarBanco();
        $stmt = $conn->prepare("SELECT * FROM compras_tbl WHERE id_compra = ?");
        $stmt->bind_param("i", $idCompra);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $data;
    }

    public static function marcarComoConcluida($idCompra) {
        $conn = conectarBanco();
        $stmt = $conn->prepare("UPDATE compras_tbl SET status='concluido' WHERE id_compra=?");
        $stmt->bind_param("i", $idCompra);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok;
    }
    public static function listarPedidosPorProduto($id_produto) {
    $db = conectarBanco();

    $sql = "
        SELECT 
            pr.id_pedido,
            pr.id_produto,
            pr.quantidade,
            pr.status,
            pr.data_pedido,
            p.nome,
            p.preco_unitario,
            p.valor_compra,                -- <--- adiciona aqui
            (pr.quantidade * p.preco_unitario) AS total_item,
            c.fornecedor,
            u.nome AS nome_usuario
        FROM pedidosreposicao_tbl pr
        JOIN produtos_tbl p ON pr.id_produto = p.id_produto
        LEFT JOIN compras_tbl c ON pr.id_compra = c.id_compra
        LEFT JOIN usuarios_tbl u ON pr.idUsuarios_TBL = u.id_usuario
        WHERE pr.id_produto = ?
        ORDER BY pr.data_pedido ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->bind_param("i", $id_produto);
    $stmt->execute();

    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

public static function getGastosPorProduto($dias = null) {
        $db = conectarBanco();

        // filtra por data se $dias for inteiro
        $where = "";
        if ($dias !== null) {
            $dias = (int)$dias;
            if ($dias > 0) {
                // usa data_pedido da tabela pedidosreposicao_tbl
                $where = "WHERE pr.data_pedido >= DATE_SUB(NOW(), INTERVAL {$dias} DAY)";
            }
        }

        $sql = "
            SELECT
                p.id_produto,
                p.nome,
                SUM(pr.quantidade) AS quantidade_total,
                SUM(pr.quantidade * COALESCE(p.valor_compra, p.preco_unitario, 0)) AS total_gasto,
                -- preço médio ponderado pelo custo (fallback para preco_unitario caso não exista valor_compra)
                CASE WHEN SUM(pr.quantidade) > 0 
                     THEN SUM(pr.quantidade * COALESCE(p.valor_compra, p.preco_unitario, 0)) / SUM(pr.quantidade)
                     ELSE 0 END AS preco_medio,
                MAX(pr.data_pedido) AS ultima_compra
            FROM pedidosreposicao_tbl pr
            LEFT JOIN produtos_tbl p ON pr.id_produto = p.id_produto
            {$where}
            GROUP BY p.id_produto, p.nome
            ORDER BY total_gasto DESC
        ";

        $res = $db->query($sql);
        if ($res === false) {
            error_log("getGastosPorProduto SQL error: " . $db->error);
            $db->close();
            return [
                'total_gasto' => 0,
                'quantidade_total' => 0,
                'preco_medio_geral' => 0,
                'produtos' => []
            ];
        }

        $produtos = $res->fetch_all(MYSQLI_ASSOC);

        // agregados gerais
        $totalGastoGeral = 0.0;
        $quantidadeGeral = 0;
        foreach ($produtos as $p) {
            $totalGastoGeral += (float)$p['total_gasto'];
            $quantidadeGeral += (int)$p['quantidade_total'];
        }
        $precoMedioGeral = $quantidadeGeral > 0 ? $totalGastoGeral / $quantidadeGeral : 0;

        $db->close();

        return [
            'total_gasto' => (float)$totalGastoGeral,
            'quantidade_total' => (int)$quantidadeGeral,
            'preco_medio_geral' => (float)$precoMedioGeral,
            'produtos' => $produtos
        ];
    }
}






 