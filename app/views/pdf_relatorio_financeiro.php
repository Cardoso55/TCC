<!doctype html>
<html>
<head>
<meta charset="utf-8"/>
<style>
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; }
  h1 { text-align:center; margin-bottom:10px; }
  table { width:100%; border-collapse: collapse; margin-top:10px; }
  th, td { border: 1px solid #ccc; padding:6px; text-align:left; }
  th { background:#eee; }
  .right { text-align:right; }
</style>
</head>
<body>
<h1>Relatório Financeiro</h1>

<table>
  <tr><th>Receita total</th><td class="right">R$ <?= number_format($resumo['receita_total'] ?? 0,2,',','.') ?></td></tr>
  <tr><th>Custo total</th><td class="right">R$ <?= number_format($resumo['custo_total'] ?? 0,2,',','.') ?></td></tr>
  <tr><th>Lucro real total</th><td class="right">R$ <?= number_format($resumo['lucro_real_total'] ?? 0,2,',','.') ?></td></tr>
  <tr><th>Valor do estoque parado</th><td class="right">R$ <?= number_format($resumo['valor_estoque_parado'] ?? 0,2,',','.') ?></td></tr>
</table>

<h3>Detalhamento por produto</h3>
<table>
  <thead>
    <tr><th>Produto</th><th class="right">Vendidos</th><th class="right">Receita</th><th class="right">Custo</th><th class="right">Lucro</th><th class="right">Estoque</th><th class="right">Valor Estoque</th></tr>
  </thead>
  <tbody>
    <?php foreach ($resumo['produtos'] ?? [] as $p): ?>
      <tr>
        <td><?= htmlspecialchars($p['nome'] ?? '') ?></td>
        <td class="right"><?= $p['quantidade_vendida'] ?? 0 ?></td>
        <td class="right">R$ <?= number_format($p['receita'] ?? 0,2,',','.') ?></td>
        <td class="right">R$ <?= number_format($p['custo'] ?? 0,2,',','.') ?></td>
        <td class="right">R$ <?= number_format($p['lucro'] ?? 0,2,',','.') ?></td>
        <td class="right"><?= $p['estoque_atual'] ?? 0 ?></td>
        <td class="right">R$ <?= number_format($p['valor_estoque'] ?? 0,2,',','.') ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
