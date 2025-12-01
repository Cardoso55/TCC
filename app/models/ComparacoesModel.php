<?php
require_once __DIR__ . '/../database/conexao.php';

class ComparacoesModel
{
    private $conn;

    public function __construct()
    {
        $this->conn = conectarBanco(); // mysqli
    }

    // =============================
    // PEGAR PREVISÕES POR TIPO
    // =============================
    private function getPrevisaoTotalPorTipo($tipo)
    {
       $sql = "
            SELECT SUM(p.previsao_quantidade * pr.preco_unitario) AS total
            FROM previsoes_tbl p
            JOIN produtos_tbl pr ON p.id_produto = pr.id_produto
            WHERE p.tipo_previsao = ?
        ";


        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return 0;

        $stmt->bind_param("s", $tipo);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return floatval($row['total'] ?? 0);
    }

    // =============================
    // PEGAR VENDAS REAIS POR PERÍODO
    // =============================
    private function getVendasOntem()
{
    $sql = "
        SELECT SUM(valor_total) AS total
        FROM vendas_tbl
        WHERE DATE(data_venda) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)
    ";

    $result = $this->conn->query($sql);
    $row = $result->fetch_assoc();

    return floatval($row['total'] ?? 0);
}


    private function getVendasHoje()
{
    $sql = "
        SELECT SUM(valor_total) AS total
        FROM vendas_tbl
        WHERE DATE(data_venda) = CURDATE()
    ";

    $result = $this->conn->query($sql);
    $row = $result->fetch_assoc();

    return floatval($row['total'] ?? 0);
}

    private function getVendasSemanaAtual()
    {
        $sql = "
            SELECT SUM(valor_total) AS total
            FROM vendas_tbl
            WHERE YEARWEEK(data_venda, 1) = YEARWEEK(CURDATE(), 1)
        ";

        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();

        return floatval($row['total'] ?? 0);
    }

    private function getVendasMesAtual()
    {
        $sql = "
            SELECT SUM(valor_total) AS total
            FROM vendas_tbl
            WHERE MONTH(data_venda) = MONTH(CURDATE())
            AND YEAR(data_venda) = YEAR(CURDATE())
        ";

        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();

        return floatval($row['total'] ?? 0);
    }


    // =============================
    // COMPARAÇÃO FINAL
    // =============================

    public function comparar($tipo, $valorReal)
    {
        $previsto = $this->getPrevisaoTotalPorTipo($tipo);

        return [
            "previsto" => $previsto,
            "real" => $valorReal,
            "diferenca" => $valorReal - $previsto,
            "percentual" => $previsto > 0 ? (($valorReal / $previsto) * 100) : 0
        ];
    }

    public function compararTudo()
    {
        return [
            "amanha" => $this->comparar("diario", $this->getVendasOntem()),
            "semana" => $this->comparar("semanal", $this->getVendasSemanaAtual()),
            "mes"    => $this->comparar("mensal", $this->getVendasMesAtual())
        ];
    }
}
