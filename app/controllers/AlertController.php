<?php
require_once __DIR__ . "/../database/conexao.php";

class AlertController {

    private $conn;

    public function __construct() {
        $this->conn = conectarBanco(); // uma única conexão
    }

    /* ============================================================
       1) ALERTA DE ESTOQUE BAIXO
       ============================================================ */
    public function gerarAlertasEstoqueBaixo() {

        $sql = "
            SELECT 
                p.id_produto,
                p.nome,
                e.quantidade_atual,
                e.quantidade_minima
            FROM estoque_tbl e
            JOIN produtos_tbl p ON e.idProdutos_TBL = p.id_produto
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($prod = $result->fetch_assoc()) {

            if ($prod['quantidade_atual'] <= $prod['quantidade_minima']) {

                $msg = "O produto {$prod['nome']} está com estoque baixo ({$prod['quantidade_atual']} unidades).";

                // chama o criador universal ✔
                $this->criarAlerta($prod['id_produto'], "estoque_baixo", $msg);

            }
        }

        echo "✔ Alertas de estoque baixo gerados!<br>";
    }

    /* ============================================================
       2) ALERTA DE PRODUTO PARADO
       ============================================================ */
    public function gerarAlertasProdutoParado() {

        $sql = "
            SELECT 
                p.id_produto,
                p.nome,
                MAX(m.data_movimentacao) AS ultima_saida
            FROM produtos_tbl p
            LEFT JOIN movimentacoes_tbl m
                ON p.id_produto = m.idProdutos_TBL
                AND m.tipo = 'saida'
            GROUP BY p.id_produto, p.nome
        ";

        $result = $this->conn->query($sql);


        if (!$result) {
            die("ERRO SELECT PRODUTOS PARADOS: " . $this->conn->error);
        }

        $hoje = new DateTime();

        while ($row = $result->fetch_assoc()) {

            $idProduto = $row['id_produto'];
            $nome = $row['nome'];
            $ultimaSaida = $row['ultima_saida'];

            // Produto nunca teve saída
            if ($ultimaSaida === null) {
                $msg = "O produto '$nome' nunca teve saídas. Está completamente parado.";
                $this->criarAlerta($idProduto, "produto_parado", $msg);
                continue;
            }

            // Produto parado
            $dataSaida = new DateTime($ultimaSaida);
            $dias = $dataSaida->diff($hoje)->days;

            if ($dias >= 30) {
                $msg = "O produto '$nome' está parado há $dias dias.";
                $this->criarAlerta($idProduto, "produto_parado", $msg);
            }
        }

        echo "✔ Alertas de produtos parados gerados!<br>";
    }

    public function gerarAlertasValidade() {
        $conn = conectarBanco();

        $sql = "
            SELECT 
                id_produto,
                nome,
                validade
            FROM produtos_tbl
            WHERE validade IS NOT NULL
        ";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {

            $hoje = new DateTime();

            while ($row = $result->fetch_assoc()) {

                $id = $row['id_produto'];
                $nome = $row['nome'];
                $validade = new DateTime($row['validade']);
                
                $dias = $hoje->diff($validade)->days;
                $jaVenceu = $validade < $hoje;

                // Produto vencido
                if ($jaVenceu) {
                    $mensagem = "O produto '$nome' está VENCIDO desde {$validade->format('d/m/Y')}.";
                    $this->criarAlerta($id, "produto_vencido", $mensagem);
                    continue;
                }

                // Validade próxima (15 dias)
                if ($dias <= 15) {
                    $mensagem = "O produto '$nome' está próximo do vencimento. Faltam $dias dias (vence em {$validade->format('d/m/Y')}).";
                    $this->criarAlerta($id, "validade_proxima", $mensagem);
                }
            }
        }

        echo "✔ Alertas de validade gerados!<br>";
    }

    private function alertaJaExiste($idProduto, $tipo) {
    $conn = conectarBanco();

    $sql = "
        SELECT id_alerta 
        FROM alertas_tbl
        WHERE idProdutos_TBL = ?
          AND tipo = ?
          AND status = 'pendente'
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $idProduto, $tipo);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0; // true se já existe
}


    /* ============================================================
       FUNÇÃO PADRÃO PARA CRIAR ALERTA
       ============================================================ */
    public function criarAlerta($idProduto, $tipo, $mensagem, $prioridade = "medio")
    {
        // ❗ Verifica se já existe
        if ($this->alertaJaExiste($idProduto, $tipo)) {
            return; 
        }

        $conn = conectarBanco();

        $stmt = $conn->prepare("
            INSERT INTO alertas_tbl 
            (tipo, mensagem, nivel_prioridade, enviado_para, status, data_criacao, idProdutos_TBL) 
            VALUES (?, ?, ?, 'admin', 'pendente', NOW(), ?)
        ");
        
        $stmt->bind_param("sssi", $tipo, $mensagem, $prioridade, $idProduto);
        $stmt->execute();
    }
    
}
