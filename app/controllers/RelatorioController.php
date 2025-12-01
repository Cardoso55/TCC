<?php
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/VendasModel.php';
require_once __DIR__ . '/../models/ProdutoModel.php';
require_once __DIR__ . '/../dompdf/autoload.inc.php'; // ajusta o caminho se necessário

use Dompdf\Dompdf;

class RelatorioController
{
    // Retorna filtro de datas vindo da UI: inicio, fim ou range (dias)
    private static function getFiltroDatas(): array
    {
        $inicio = $_GET['inicio'] ?? null;
        $fim = $_GET['fim'] ?? null;
        $range = $_GET['range'] ?? null;

        // se passou range em dias (ex: 7,30,365) usar isso
        if ($range) {
            $fim = date("Y-m-d");
            $inicio = date("Y-m-d", strtotime("-{$range} days"));
        }

        return [
            'inicio' => $inicio,
            'fim' => $fim,
            'range' => $range
        ];
    }

    // Converte o filtro para um número de dias para as funções simples do VendasModel
    private static function filtroParaDias(?string $inicio, ?string $fim, $range): ?int
    {
        if ($range) {
            return (int)$range;
        }

        if ($inicio && $fim) {
            $dtInicio = new DateTime($inicio);
            $dtFim = new DateTime($fim);
            $interval = $dtInicio->diff($dtFim);
            // adicionar 1 dia para incluir ambos os extremos
            return (int)$interval->days + 1;
        }

        // null = sem filtro (tudo)
        return null;
    }

    /**
     * Retorna resumo das compras (entradas) consolidado por produto.
     */
    public static function getCompras(): array
    {
        $produtos = ProdutoModel::buscarComEstoque();
        $filtro = self::getFiltroDatas();
        $inicio = $filtro['inicio'];
        $fim = $filtro['fim'];
        $range = $filtro['range'];
        $dias = self::filtroParaDias($inicio, $fim, $range);

        $resultado = [];
        $quantidadeTotalGeral = 0;
        $totalGastoGeral = 0;

        foreach ($produtos as $p) {
            $idProduto = $p['id_produto'] ?? $p['idProdutos_TBL'] ?? null;
            if (!$idProduto) continue;

            // pega pedidos desse produto (CompraModel já retorna valor_compra quando existe)
            $pedidos = CompraModel::listarPedidosPorProduto($idProduto);

            $quantidadeTotal = 0;
            $totalGasto = 0;
            $ultimaCompra = null;

            foreach ($pedidos as $pedido) {
                // se houver filtro por datas, verifica data_pedido dentro do intervalo (quando informado)
                if ($inicio && $fim && !empty($pedido['data_pedido'])) {
                    $dataPedido = substr($pedido['data_pedido'], 0, 10);
                    if ($dataPedido < $inicio || $dataPedido > $fim) continue;
                } elseif ($dias && !empty($pedido['data_pedido'])) {
                    // quando usamos dias, comparamos com hoje
                    $dataPedido = new DateTime(substr($pedido['data_pedido'], 0, 10));
                    $limite = new DateTime();
                    $limite->modify("-{$dias} days");
                    if ($dataPedido < $limite) continue;
                }

                $qtd = (int) ($pedido['quantidade'] ?? 0);
                $quantidadeTotal += $qtd;

                // preferimos valor_compra (custo) — fallback para preco_unitario se necessário
                if (isset($pedido['valor_compra']) && $pedido['valor_compra'] !== '') {
                    $unitCompra = (float) $pedido['valor_compra'];
                } else {
                    $unitCompra = (float) ($pedido['preco_unitario'] ?? 0);
                }

                $totalGasto += $qtd * $unitCompra;

                if (!$ultimaCompra || ($pedido['data_pedido'] ?? '') > $ultimaCompra) {
                    $ultimaCompra = $pedido['data_pedido'];
                }
            }

            // guardando para resumo geral
            $quantidadeTotalGeral += $quantidadeTotal;
            $totalGastoGeral += $totalGasto;

            $resultado[] = [
                'id_produto' => $idProduto,
                'nome' => $p['nome'] ?? '',
                'quantidade_atual' => $p['estoque_atual'] ?? 0,
                'quantidade_total' => $quantidadeTotal,
                'total_gasto' => (float)$totalGasto,
                'preco_medio' => $quantidadeTotal > 0 ? ($totalGasto / $quantidadeTotal) : 0,
                'ultima_compra' => $ultimaCompra,
            ];
        }

        return [
            'total_gasto' => (float)$totalGastoGeral,
            'quantidade_total' => (int)$quantidadeTotalGeral,
            'preco_medio_geral' => $quantidadeTotalGeral > 0 ? $totalGastoGeral / $quantidadeTotalGeral : 0,
            'produtos' => $resultado
        ];
    }

    /**
     * Retorna resumo de vendas (visão macro) — agora com filtro por período.
     */
  public static function getVendas(): array
{
    // mantém o mesmo fluxo de filtro que o resto do controller
    $filtro = self::getFiltroDatas();
    $inicio = $filtro['inicio'];
    $fim = $filtro['fim'];
    $range = $filtro['range'];
    $dias = self::filtroParaDias($inicio, $fim, $range); // null ou int

    // pegar totais via model (essas funções devem existir no VendasModel)
    $receitaTotal = VendasModel::getReceitaTotal($dias);
    $quantidadeTotal = VendasModel::getQuantidadeTotal($dias);
    $ticketMedio = $quantidadeTotal ? ($receitaTotal / $quantidadeTotal) : 0;

    // produtos — usamos o retorno cru do model e mapeamos para um formato consistente
    $porProdutoRaw = VendasModel::getProdutosMaisVendidos($dias);
    $porProduto = [];

    foreach ($porProdutoRaw as $r) {
        // mapear campos existentes sem inventar nomes
        $nome = $r['nome'] ?? ($r['produto_nome'] ?? '');
        $total_vendido = (int)($r['total_vendido'] ?? $r['quantidade'] ?? 0);

        // receita: usa o campo 'receita' se existir, senão tenta 'valor_total', senão calcula com preco_unitario_atual
        if (isset($r['receita'])) {
            $receita = (float)$r['receita'];
        } elseif (isset($r['valor_total'])) {
            $receita = (float)$r['valor_total'];
        } else {
            $receita = (float)($r['preco_unitario_atual'] ?? ($r['preco_unitario'] ?? 0)) * $total_vendido;
        }

        // preço unitário atual (garantir que sempre exista como float)
        $precoUnit = isset($r['preco_unitario_atual']) ? (float)$r['preco_unitario_atual'] : (float)($r['preco_unitario'] ?? 0);

        $porProduto[] = [
            'nome' => $nome,
            'total_vendido' => $total_vendido,
            'receita' => $receita,
            'preco_unitario_atual' => $precoUnit,
            // opcional: repassar id se o model retornar
            'id_produto' => $r['id_produto'] ?? ($r['id'] ?? null)
        ];
    }

    // vendas por canal (não mexer — model que cuide disso)
    $porCanal = VendasModel::getVendasPorCanal($dias);

    return [
        'receita_total' => (float)$receitaTotal,
        'quantidade_total' => (int)$quantidadeTotal,
        'ticket_medio' => (float)$ticketMedio,
        'por_produto' => $porProduto,
        'por_canal' => $porCanal
    ];
}



    /**
     * Relatório financeiro por produto (receita - custo).
     * Agora usa o filtro por período quando possível.
     */
    public static function getFinanceiro(): array
    {
        $filtro = self::getFiltroDatas();
        $inicio = $filtro['inicio'];
        $fim = $filtro['fim'];
        $range = $filtro['range'];
        $dias = self::filtroParaDias($inicio, $fim, $range);

        // pega produtos
        $produtos = method_exists('ProdutoModel', 'buscarComEstoque')
            ? ProdutoModel::buscarComEstoque()
            : (method_exists('ProdutoModel', 'buscarTodos') ? ProdutoModel::buscarTodos() : []);

        // vendas agregadas (passa $dias para o model quando suportado)
        $produtosVendidos = VendasModel::getProdutosMaisVendidos($dias);
        $receitaPorId = [];

        foreach ($produtosVendidos as $pv) {
            $id = $pv['id_produto'] ?? $pv['id'] ?? null;
            // se model não retornou id, tenta mapear por nome (menos ideal)
            $nomePv = $pv['nome'] ?? ($pv['produto_nome'] ?? null);
            if ($id) {
                $receitaPorId[$id] = [
                    'receita' => (float)($pv['receita'] ?? ($pv['valor_total'] ?? 0)),
                    'quantidade' => (int)($pv['total_vendido'] ?? $pv['quantidade'] ?? 0),
                    'nome' => $nomePv
                ];
            } elseif ($nomePv) {
                // fallback: mapear por nome
                $receitaPorId['by_name:' . $nomePv] = [
                    'receita' => (float)($pv['receita'] ?? ($pv['valor_total'] ?? 0)),
                    'quantidade' => (int)($pv['total_vendido'] ?? $pv['quantidade'] ?? 0),
                    'nome' => $nomePv
                ];
            }
        }

        $result = [];
        $valorEstoqueGeral = 0;
        $lucroRealGeral = 0;
        $receitaGeral = 0;
        $custoGeral = 0;

        foreach ($produtos as $p) {
            $idProduto = $p['id_produto'] ?? $p['idProdutos_TBL'] ?? null;
            if (!$idProduto) continue;
            $nome = $p['nome'] ?? ($p['nome_produto'] ?? '');
            $valorCompra = (float)($p['valor_compra'] ?? 0);
            $estoqueAtual = (int)($p['estoque_atual'] ?? 0);
            $valorEstoqueProduto = $estoqueAtual * $valorCompra;

            // procura por id primeiro, se não achar, por nome (fallback)
            if (isset($receitaPorId[$idProduto])) {
                $recData = $receitaPorId[$idProduto];
                $receita = (float)$recData['receita'];
                $qtdVendida = (int)$recData['quantidade'];
            } elseif (isset($receitaPorId['by_name:' . $nome])) {
                $recData = $receitaPorId['by_name:' . $nome];
                $receita = (float)$recData['receita'];
                $qtdVendida = (int)$recData['quantidade'];
            } else {
                $receita = 0;
                $qtdVendida = 0;
            }

            // se não vendeu no período, ocultar do relatório financeiro (mas somar estoque parado)
            if ($qtdVendida <= 0) {
                $valorEstoqueGeral += $valorEstoqueProduto;
                continue;
            }

            $custo = $valorCompra * $qtdVendida;
            $lucro = $receita - $custo;

            $valorEstoqueGeral += $valorEstoqueProduto;
            $receitaGeral += $receita;
            $custoGeral += $custo;
            $lucroRealGeral += $lucro;

            $result[] = [
                'id_produto' => $idProduto,
                'nome' => $nome,
                'quantidade_vendida' => $qtdVendida,
                'receita' => (float)$receita,
                'custo' => (float)$custo,
                'lucro' => (float)$lucro,
                'estoque_atual' => $estoqueAtual,
                'valor_estoque' => $valorEstoqueProduto
            ];
        }

        return [
            'produtos' => $result,
            'receita_total' => (float)$receitaGeral,
            'custo_total' => (float)$custoGeral,
            'lucro_real_total' => (float)$lucroRealGeral,
            'valor_estoque_parado' => (float)$valorEstoqueGeral
        ];
    }

    // Gera PDF do Relatório Financeiro
    public static function gerarPDFFinanceiro()
    {
        $financeiro = self::getFinanceiro();
        $resumo = $financeiro;

        ob_start();
        include __DIR__ . '/../views/pdf_relatorio_financeiro.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("relatorio_financeiro.pdf", ["Attachment" => true]);
    }

    // Gera PDF de Compras
    public static function gerarPDFCompras()
    {
        $compras = self::getCompras();
        $resumo = $compras;

        ob_start();
        include __DIR__ . '/../views/pdf_relatorio_entradas.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("relatorio_entradas.pdf", ["Attachment" => true]);
    }

    // Gera PDF de Vendas
    public static function gerarPDFVendas()
    {
        $vendas = self::getVendas();
        $resumo = $vendas;

        ob_start();
        include __DIR__ . '/../views/pdf_relatorio_saidas.php';
        $html = ob_get_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("relatorio_saidas.pdf", ["Attachment" => true]);
    }
}
