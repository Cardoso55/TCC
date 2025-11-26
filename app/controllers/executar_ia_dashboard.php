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

// Salva log da saída (somente para debug)
$logPath = $projectRoot . "/storage/logs/arquivo/ia_dashboard_log.txt";
$logDir = dirname($logPath);
if (!file_exists($logDir)) mkdir($logDir, 0777, true);

file_put_contents($logPath, "[".date("Y-m-d H:i:s")."]\n$output\n\n", FILE_APPEND);

// IMPORTANTE: não salva nada no banco aqui,
// o Python já fez isso com salvar_recomendacoes_no_db()

echo "IA executada com sucesso! Saída:\n$output";
?>
