<?php
// upload_relatorio.php - versão robusta e portátil

// Calcula a raiz do projeto (sobe 2 níveis: app/views -> TCC)
$projectRoot = dirname(__DIR__, 2); // requer PHP 7+

// Monta o caminho usando DIRECTORY_SEPARATOR para ser portátil
$uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'python' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'vendas' . DIRECTORY_SEPARATOR;

// Garante que a pasta existe e é gravável
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        die("❌ Falha ao criar pasta de upload: $uploadDir");
    }
}

// Nome do input do form: 'arquivo_csv'
if (!empty($_FILES['arquivo_csv']['name'])) {

    // Mantém o nome original do arquivo (ou força um padrão)
    $originalName = basename($_FILES['arquivo_csv']['name']);
    // Se quiser que sempre salve como 'vendas.csv', descomente a linha abaixo:
    // $originalName = 'vendas.csv';

    $destPath = $uploadDir . $originalName;

    // Valida extensão
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        die("❌ Apenas arquivos .csv são permitidos. Arquivo enviado: $originalName");
    }

    // Move o arquivo temporário para a pasta final
    if (move_uploaded_file($_FILES['arquivo_csv']['tmp_name'], $destPath)) {
        echo "✔ Arquivo enviado com sucesso!<br>";
        echo "Salvo em: " . htmlspecialchars($destPath) . "<br>";

        // Se quiser rodar o EXE/Script Python automaticamente:
        // Se usa EXE:
        // $exe = $projectRoot . DIRECTORY_SEPARATOR . 'python' . DIRECTORY_SEPARATOR . 'ia_main.exe';
        // $command = "\"$exe\" 2>&1";

        // Se usa Python (instalado no PATH do servidor):
        $pythonCmd = "python"; // ou full path quando necessário
        $script = $projectRoot . DIRECTORY_SEPARATOR . "python" . DIRECTORY_SEPARATOR . "ia_main.py";
        $command = "\"$pythonCmd\" \"$script\" 2>&1";

        // Executa e captura saída (pode demorar dependendo do processamento)
        $output = shell_exec($command);
        echo "<pre>Saída da IA:\n" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "❌ Erro ao enviar arquivo. Verifique permissão da pasta: $uploadDir";
    }

} else {
    // Formulário simples de upload
    ?>
    <form method="post" enctype="multipart/form-data">
        <h2>Enviar relatório de vendas para IA</h2>
        <input type="file" name="arquivo_csv" accept=".csv" required>
        <button type="submit">Enviar</button>
    </form>
    <?php
}
?>
