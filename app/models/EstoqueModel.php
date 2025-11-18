<?php
require_once __DIR__ . '/../database/conexao.php';

class EstoqueModel {
    public static function salvar($estoque) {
        $db = Database::conectar();
        $stmt = $db->prepare("INSERT INTO estoque_tbl (idProduto, quantidade) VALUES (?, ?)");
        return $stmt->execute([$estoque['idProduto'], $estoque['quantidade']]);
    }

    public function getAlertasIA() {
        $alertas = [];

        // 1. ESTOQUE ABAIXO DO MINIMO
        $q1 = $this->conn->query("
            SELECT nome_produto, estoque_atual, estoque_minimo 
            FROM produtos_tbl 
            WHERE estoque_atual < estoque_minimo
        ");

        while ($r = $q1->fetch_assoc()) {
            $alertas[] = [
                'tipo' => 'estoque_baixo',
                'titulo' => 'Estoque Baixo',
                'icone' => '⚠️',
                'mensagem' => "{$r['nome_produto']} está abaixo do mínimo ({$r['estoque_atual']} / {$r['estoque_minimo']})."
            ];
        }


        // 2. PRODUTOS PRESTES A VENCER (Faltando <= 30 dias)
        $q2 = $this->conn->query("
            SELECT nome_produto, validade, 
                DATEDIFF(validade, CURDATE()) AS dias
            FROM produtos_tbl
            WHERE validade IS NOT NULL
            AND DATEDIFF(validade, CURDATE()) BETWEEN 0 AND 30
        ");

        while ($r = $q2->fetch_assoc()) {
            $alertas[] = [
                'tipo' => 'vence_logo',
                'titulo' => 'Validade Próxima',
                'icone' => '⏳',
                'mensagem' => "{$r['nome_produto']} vence em {$r['dias']} dias."
            ];
        }


        // 3. PRODUTOS VENCIDOS
        $q3 = $this->conn->query("
            SELECT nome_produto, validade
            FROM produtos_tbl
            WHERE validade IS NOT NULL
            AND validade < CURDATE()
        ");

        while ($r = $q3->fetch_assoc()) {
            $alertas[] = [
                'tipo' => 'vencido',
                'titulo' => 'Produto Vencido',
                'icone' => '❌',
                'mensagem' => "{$r['nome_produto']} já está vencido!"
            ];
        }


        // 4. PRODUTOS PARADOS (sem vendas há 30 dias)
        $q4 = $this->conn->query("
            SELECT p.nome_produto, 
                MAX(v.data_venda) AS ultima_venda,
                DATEDIFF(CURDATE(), MAX(v.data_venda)) AS dias
            FROM produtos_tbl p
            LEFT JOIN vendas_tbl v ON v.id_produto = p.id_produto
            GROUP BY p.id_produto
            HAVING dias >= 30
        ");

        while ($r = $q4->fetch_assoc()) {
            $dias = $r['dias'] ?? 'desconhecido';
            $alertas[] = [
                'tipo' => 'parado',
                'titulo' => 'Produto Parado',
                'icone' => '🛑',
                'mensagem' => "{$r['nome_produto']} está parado há {$dias} dias."
            ];
        }

        return $alertas;
    }

    public function listarAlertasIA() {
        header('Content-Type: application/json');
        echo json_encode($this->model->getAlertasIA());
    }

    $router->get('/alertas-ia', 'EstoqueController@listarAlertasIA');



}
