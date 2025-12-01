<?php
require_once __DIR__ . '/../database/conexao.php';

class EstoqueModel
{
    /**
     * Salva um novo registro de estoque
     * $estoque = ['idProduto' => int, 'quantidade' => int]
     */
    public static function salvar($estoque)
    {
        $conn = conectarBanco();

        $sql = "INSERT INTO estoque_tbl (idProdutos_TBL, quantidade_atual) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $err = $conn->error;
            $conn->close();
            return ['erro' => $err];
        }

        $idProduto = (int)$estoque['idProduto'];
        $quantidade = (int)$estoque['quantidade'];

        $stmt->bind_param("ii", $idProduto, $quantidade);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();

        return $ok ? ['sucesso' => true, 'id_estoque' => $conn->insert_id] : ['erro' => 'Falha ao salvar estoque'];
    }

    /**
     * Gera os alertas para a IA (retorna array)
     */
    public static function getAlertasIA()
    {
        $conn = conectarBanco();
        $alertas = [];

        // 1) Estoque abaixo do mínimo
        $q1 = $conn->query("
            SELECT p.nome AS nome_produto, e.quantidade_atual, e.quantidade_minima
            FROM produtos_tbl p
            LEFT JOIN estoque_tbl e ON e.idProdutos_TBL = p.id_produto
            WHERE e.quantidade_minima IS NOT NULL
              AND e.quantidade_atual < e.quantidade_minima
        ");
        if ($q1) {
            while ($r = $q1->fetch_assoc()) {
                $alertas[] = [
                    'tipo' => 'estoque_baixo',
                    'titulo' => 'Estoque Baixo',
                    'icone' => '⚠️',
                    'mensagem' => "{$r['nome_produto']} está abaixo do mínimo ({$r['quantidade_atual']} / {$r['quantidade_minima']})."
                ];
            }
            $q1->free();
        }

        // 2) Produtos prestes a vencer (<= 30 dias)
        $q2 = $conn->query("
            SELECT p.nome AS nome_produto, p.validade,
                   DATEDIFF(p.validade, CURDATE()) AS dias
            FROM produtos_tbl p
            WHERE p.validade IS NOT NULL
              AND DATEDIFF(p.validade, CURDATE()) BETWEEN 0 AND 30
        ");
        if ($q2) {
            while ($r = $q2->fetch_assoc()) {
                $alertas[] = [
                    'tipo' => 'vence_logo',
                    'titulo' => 'Validade Próxima',
                    'icone' => '⏳',
                    'mensagem' => "{$r['nome_produto']} vence em {$r['dias']} dias."
                ];
            }
            $q2->free();
        }

        // 3) Produtos vencidos
        $q3 = $conn->query("
            SELECT p.nome AS nome_produto, p.validade
            FROM produtos_tbl p
            WHERE p.validade IS NOT NULL
              AND p.validade < CURDATE()
        ");
        if ($q3) {
            while ($r = $q3->fetch_assoc()) {
                $alertas[] = [
                    'tipo' => 'vencido',
                    'titulo' => 'Produto Vencido',
                    'icone' => '❌',
                    'mensagem' => "{$r['nome_produto']} já está vencido!"
                ];
            }
            $q3->free();
        }

        // 4) Produtos parados (sem vendas há >= 30 dias)
        $q4 = $conn->query("
            SELECT p.nome AS nome_produto,
                   MAX(v.data_venda) AS ultima_venda,
                   DATEDIFF(CURDATE(), MAX(v.data_venda)) AS dias
            FROM produtos_tbl p
            LEFT JOIN vendas_tbl v ON v.id_produto = p.id_produto
            GROUP BY p.id_produto
            HAVING dias >= 30 OR ultima_venda IS NULL
        ");
        if ($q4) {
            while ($r = $q4->fetch_assoc()) {
                $dias = $r['dias'] !== null ? $r['dias'] : 'desconhecido';
                $alertas[] = [
                    'tipo' => 'parado',
                    'titulo' => 'Produto Parado',
                    'icone' => '🛑',
                    'mensagem' => "{$r['nome_produto']} está parado há {$dias} dias."
                ];
            }
            $q4->free();
        }

        $conn->close();
        return $alertas;
    }

    /**
     * Output JSON para listar alertas (pode ser chamado por controller)
     */
    public static function listarAlertasIA()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(self::getAlertasIA(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Buscar por produto no estoque
     */
    public static function buscarPorProduto($idProduto)
    {
        $conn = conectarBanco();
        $sql = "SELECT * FROM estoque_tbl WHERE idProdutos_TBL = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return null;
        }
        $stmt->bind_param("i", $idProduto);
        $stmt->execute();
        $res = $stmt->get_result();
        $estoque = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        $conn->close();
        return $estoque;
    }

    /**
     * Atualiza quantidade atual do estoque (id_estoque + idProduto)
     */
    public static function atualizarQuantidade($idEstoque, $idProduto, $novaQuantidade)
    {
        $conn = conectarBanco();
        $sql = "
            UPDATE estoque_tbl
            SET quantidade_atual = ?
            WHERE id_estoque = ? AND idProdutos_TBL = ?
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return false;
        }
        $stmt->bind_param("iii", $novaQuantidade, $idEstoque, $idProduto);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return (bool)$ok;
    }

    /**
     * Calcula a quantidade mínima baseada em vendas (últimos 30 dias por padrão)
     */
    public static function calcularQuantidadeMinima($idProduto, $leadTimeDias = 4, $percentualSeguranca = 0.5, $periodoDias = 30)
    {
        $conn = conectarBanco();

        $sql = "
            SELECT COALESCE(SUM(quantidade),0) AS total
            FROM vendas_tbl
            WHERE id_produto = ?
              AND data_venda >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return 0;
        }
        $stmt->bind_param("ii", $idProduto, $periodoDias);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $conn->close();

        $totalVendido = (int)($res['total'] ?? 0);
        $dias = max(1, (int)$periodoDias);
        $mdv = $totalVendido / $dias; // média diária

        $seguranca = $mdv * $leadTimeDias * $percentualSeguranca;
        $quantidadeMinima = (int)ceil(($mdv * $leadTimeDias) + $seguranca);

        // garantir pelo menos 1 quando houver vendas; se não houver vendas, devolve 0
        if ($totalVendido > 0 && $quantidadeMinima < 1) $quantidadeMinima = 1;

        return $quantidadeMinima;
    }

    /**
     * Atualiza o campo quantidade_minima do estoque para um produto
     */
    public static function atualizarMinimoEstoque($idProduto)
    {
        $conn = conectarBanco();

        $novoMinimo = self::calcularQuantidadeMinima($idProduto);
        $sql = "UPDATE estoque_tbl SET quantidade_minima = ? WHERE idProdutos_TBL = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $conn->close();
            return false;
        }
        $stmt->bind_param("ii", $novoMinimo, $idProduto);
        $ok = $stmt->execute();
        $stmt->close();
        $conn->close();
        return $ok ? $novoMinimo : false;
    }

    /**
     * Recalcula o mínimo para todos os produtos do estoque
     */
    public static function recalcularMinimosTodosProdutos()
    {
        $conn = conectarBanco();
        $res = $conn->query("SELECT idProdutos_TBL FROM estoque_tbl");
        if (!$res) {
            $conn->close();
            return false;
        }

        $ids = [];
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int)$row['idProdutos_TBL'];
        }
        $res->free();
        $conn->close();

        foreach ($ids as $id) {
            // chama o método estático (cada chamada abre/fecha conexão internamente)
            self::atualizarMinimoEstoque($id);
        }

        return true;
    }
}
