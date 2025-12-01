<!doctype html>
<html>
<head><meta charset="utf-8"/><style>body{font-family:DejaVu Sans,Arial;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #ccc;padding:6px;}</style></head>
<body>
<h1>Relatório de entradas</h1>
<p><strong>Total gasto:</strong> R$ <?= number_format($resumo['total_gasto'] ?? 0,2,',','.') ?></p>
<p><strong>Quantidade total:</strong> <?= $resumo['quantidade_total'] ?? 0 ?></p>
<table>
  <thead><tr><th>Produto</th><th>Quantidade</th><th>Preço médio</th><th>Total gasto</th><th>Última entrada</th></tr></thead>
  <tbody>
    <?php foreach ($resumo['produtos'] ?? [] as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['nome']) ?></td>
        <td><?= $p['quantidade_total'] ?></td>
        <td>R$ <?= number_format($p['preco_medio'] ?? 0,2,',','.') ?></td>
        <td>R$ <?= number_format($p['total_gasto'] ?? 0,2,',','.') ?></td>
        <td><?= $p['ultima_compra'] ?: '—' ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
