<?php
session_start();

$userId = $_SESSION['user_id'] ?? null;

if (!$userId || !isset($_POST['executar'])) {
    echo "Usuário não logado ou chamada inválida!";
    exit;
}

$projectRoot = dirname(__DIR__, 2);
$script = $projectRoot . DIRECTORY_SEPARATOR . "python" . DIRECTORY_SEPARATOR . "ia_main.py";

// Executa Python com USER_ID
$command = "python \"$script\" $userId 2>&1";
$output = shell_exec($command);

// Salva log
$logPath = $projectRoot . DIRECTORY_SEPARATOR . "storage" . DIRECTORY_SEPARATOR . "logs" . DIRECTORY_SEPARATOR . "arquivo" . DIRECTORY_SEPARATOR . "ia_dashboard_log.txt";
$logDir = dirname($logPath);
if (!file_exists($logDir)) mkdir($logDir, 0777, true);
file_put_contents($logPath, "[".date("Y-m-d H:i:s")."]\n$output\n\n", FILE_APPEND);

// ----------------------------
// SALVA RECOMENDAÇÕES NO BANCO
// ----------------------------
require_once __DIR__ . "/../database/conexao.php";
$conn = conectarBanco();

// Limpa recomendações antigas
$conn->query("TRUNCATE TABLE ia_recomendacoes_tbl");

// Extrai recomendações da saída do Python
// Supondo que cada linha tenha o formato: CODIGO_PRODUTO: RECOMENDACAO
$linhas = explode("\n", $output);
foreach ($linhas as $linha) {
    if (strpos($linha, ':') !== false) {
        list($codigo, $recomendacao) = explode(':', $linha, 2);
        $codigo = trim($codigo);
        $recomendacao = trim($recomendacao);

        // Pega o nome do produto da tabela produtos_tbl
        $stmt = $conn->prepare("SELECT nome FROM produtos_tbl WHERE codigo_produto = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $res = $stmt->get_result();
        $nome_produto = $res->num_rows ? $res->fetch_assoc()['nome'] : "Produto desconhecido";

        // Insere na tabela ia_recomendacoes_tbl
        $stmt2 = $conn->prepare("INSERT INTO ia_recomendacoes_tbl (codigo_produto, nome_produto, recomendacao) VALUES (?, ?, ?)");
        $stmt2->bind_param("sss", $codigo, $nome_produto, $recomendacao);
        $stmt2->execute();
        $stmt2->close();
    }
}

echo "IA executada com sucesso! Saída:\n$output";
