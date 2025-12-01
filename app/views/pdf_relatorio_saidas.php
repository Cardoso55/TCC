<!doctype html>
<html>
<head>
<meta charset="utf-8"/>
<style>
    body {
        font-family: DejaVu Sans, Arial;
        font-size: 14px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    th, td {
        border: 1px solid #ccc;
        padding: 6px;
        text-align: left;
    }
    h1 {
        margin-bottom: 5px;
    }
</style>
</head>
<body>

<h1>Relatório de Saídas</h1>

<p><strong>Receita total:</strong>  
    R$ <?= number_format($resumo['receita_total'] ?? 0, 2, ',', '.') ?>
</p>

<p><strong>Quantidade total vendida:</strong>  
    <?= $resumo['quantidade_total'] ?? 0 ?>
</p>

<p><strong>Ticket médio:</strong>  
    R$ <?= number_format($resumo['ticket_medio'] ?? 0, 2, ',', '.') ?>
</p>

<h3>Produtos</h3>

<table>
    <thead>
        <tr>
            <th>Produto</th>
            <th>Valor Unitário</th>
            <th>Quantidade</th>
            <th>Receita</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach (($resumo['por_produto'] ?? []) as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['nome']) ?></td>

                <td>
                    R$ <?= number_format(
                        $p['preco_unitario_atual'] ?? 0,
                        2,
                        ',',
                        '.'
                    ) ?>
                </td>

                <td><?= $p['total_vendido'] ?></td>

                <td>
                    R$ <?= number_format(
                        $p['receita'] ?? 0,
                        2,
                        ',',
                        '.'
                    ) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
