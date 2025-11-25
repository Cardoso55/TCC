<?php
// Caminho do script Python
$script = __DIR__ . "/../../python/modelo/agendador_previsao.py";

$comando = "python \"$script\" 2>&1";
$output = shell_exec($comando);

file_put_contents(__DIR__ . "/../../storage/logs/previsao_log.txt",
    "CMD: $comando\n\nOUTPUT:\n$output\n",
    FILE_APPEND
);

header("Location: /TCC/app/views/dashboard.php");
exit;

