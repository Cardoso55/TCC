<?php
// upload_relatorio.php - versão com layout moderno

// Calcula a raiz do projeto (sobe 2 níveis: app/views -> TCC)
$projectRoot = dirname(__DIR__, 2); // requer PHP 7+

// Monta o caminho usando DIRECTORY_SEPARATOR para ser portátil
$uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'python' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'vendas' . DIRECTORY_SEPARATOR;

// Garante que a pasta existe e é gravável
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        die("Falha ao criar pasta de upload: $uploadDir");
    }
}

// Variável para mensagens de status
$statusMsg = '';
$outputIA = '';

if (!empty($_FILES['arquivo_csv']['name'])) {

    $originalName = basename($_FILES['arquivo_csv']['name']);
    $destPath = $uploadDir . $originalName;

    // Valida extensão
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        $statusMsg = "Apenas arquivos .csv são permitidos. Arquivo enviado: $originalName";
    } else {
        // Move o arquivo temporário para a pasta final
        if (move_uploaded_file($_FILES['arquivo_csv']['tmp_name'], $destPath)) {
            $statusMsg = "✔ Arquivo enviado com sucesso!<br>Salvo em: " . htmlspecialchars($destPath);

            // Executa script Python
            $pythonCmd = "python"; // ou full path quando necessário
            $script = $projectRoot . DIRECTORY_SEPARATOR . "python" . DIRECTORY_SEPARATOR . "ia_main.py";
            $command = "\"$pythonCmd\" \"$script\" 2>&1";

            $outputIA = shell_exec($command);
        } else {
            $statusMsg = "Erro ao enviar arquivo. Verifique permissão da pasta: $uploadDir";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Relatório de Estoque</title>
  <link rel="stylesheet" href="/TCC/public/css/reset.css">
  <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
  <link rel="stylesheet" href="/TCC/public/css/relatorios.css">
</head>
<body>
    <div class="all">
        <?php include 'partials/sidebar.php'; ?>

        <div class="main-content">
            <h2 class="title">Relatório de Estoque</h2>

            <section class="report-container">

                <!-- Formulário de Upload -->
                <div class="upload-box">
                    <h3>Enviar relatório de vendas para IA</h3>
                    <form method="post" enctype="multipart/form-data">
                        <input type="file" name="arquivo_csv" accept=".csv" required>
                        <button type="submit">Enviar</button>
                    </form>
            
                </div>


                <div class="tabs">
                    <button class="tab on">Entradas</button>
                    <button class="tab">Saídas</button>
                    <button class="tab">Resumo</button>
                </div>

                <div class="report-box">
                    <div class="resume-top">
                        <div class="info">
                            <h3>Entradas</h3>
                            <p><strong>R$ 0.00</strong></p>
                        </div>
                        <div class="info">
                            <h3>Saídas</h3>
                            <p><strong>R$ 0.00</strong></p>
                        </div>
                        <div class="info">
                            <h3>Saldo Atual</h3>
                            <p><strong>R$ 0.00</strong></p>
                        </div>
                        <div class="info">
                            <h3>Produtos no Estoque</h3>
                            <p><strong>0</strong></p>
                        </div>
                    </div>

                    <div class="graphic-placeholder">
                        <p>📊 Espaço reservado para o gráfico de movimentação de estoque</p>
                    </div>

                    <div class="table-content">
                        <h3>Últimas Movimentações</h3>
                        <table>
                        <thead>
                            <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Produto</th>
                            <th>Quantidade</th>
                            <th>Responsável</th>
                            <th>Origem/Destino</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <td>00/00/2025</td>
                            <td>Entrada</td>
                            <td>Produto A</td>
                            <td>50</td>
                            <td>João</td>
                            <td>Fornecedor XPTO</td>
                            </tr>
                            <tr>
                            <td>00/00/2025</td>
                            <td>Saída</td>
                            <td>Produto B</td>
                            <td>20</td>
                            <td>Maria</td>
                            <td>Loja Central</td>
                            </tr>
                            <tr>
                            <td>00/00/2025</td>
                            <td>Entrada</td>
                            <td>Produto C</td>
                            <td>100</td>
                            <td>Carlos</td>
                            <td>Fornecedor ABC</td>
                            </tr>
                        </tbody>
                        </table>
                    </div>

                    <div class="export-btn">
                        <button class="btn-pdf">📄 Exportar PDF</button>
                        <button class="btn-excel">📊 Exportar Excel</button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>
</html>
