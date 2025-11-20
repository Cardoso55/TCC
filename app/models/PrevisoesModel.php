<?php
require_once 'app/database/conexao.php';

class PrevisoesModel{
    public function getUltimasPrevisoes() {
    $conn = conectarBanco();
    $sql = 
    "SELECT 
        pr.nome AS produto,
        p.tipo_previsao,
        p.previsao_quantidade,
        p.data_previsao,
        p.criado_em
    FROM previsoes_tbl p
    INNER JOIN produtos_tbl pr ON p.id_produto = pr.id_produto
    INNER JOIN (
        SELECT id_produto, tipo_previsao, MAX(data_previsao) AS ultima_data
        FROM previsoes_tbl
        GROUP BY id_produto, tipo_previsao
    ) ult ON p.id_produto = ult.id_produto
        AND p.tipo_previsao = ult.tipo_previsao
        AND p.data_previsao = ult.ultima_data
    ORDER BY pr.nome ASC, p.tipo_previsao ASC;
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

}

?>