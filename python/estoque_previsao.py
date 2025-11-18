#!/usr/bin/env python3
"""
estoque_previsao.py
Lê um CSV de vendas e gera previsões mensais por produto usando média móvel ponderada.

Uso:
    python estoque_previsao.py path/para/vendas.csv

Saída:
    - prints resumo no console
    - escreve `previsoes_vendas.json`
    - gera gráficos PNG em output/graficos/
"""

import sys
import json
from collections import defaultdict
from datetime import datetime
import pandas as pd
import argparse
from pathlib import Path
import matplotlib.pyplot as plt
import os

# Pesos padrão para média móvel ponderada
DEFAULT_WEIGHTS = [0.1, 0.3, 0.6]

# ============================================================
#   FUNÇÕES PRINCIPAIS
# ============================================================

def guess_columns(df):
    cols = [c.lower() for c in df.columns]
    prod_candidates = ['produto_id', 'produto', 'prod_id', 'id_produto', 'sku']
    date_candidates = ['data', 'date', 'venda_data', 'created_at']
    qtd_candidates = ['quantidade', 'qtd', 'qty', 'quant', 'amount', 'vendas']

    def find(cands):
        for cand in cands:
            if cand in cols:
                return df.columns[cols.index(cand)]
        for col in df.columns:
            lc = col.lower()
            for cand in cands:
                if cand in lc:
                    return col
        return None

    return find(prod_candidates), find(date_candidates), find(qtd_candidates)


def carregar_csv(path):
    path = Path(path)
    if not path.exists():
        raise FileNotFoundError(f"CSV não encontrado: {path}")
    try:
        df = pd.read_csv(path, sep=None, engine='python', encoding='utf-8')
    except Exception:
        df = pd.read_csv(path, encoding='latin-1')
    return df


def preparar_dataset(df):
    col_prod, col_date, col_qtd = guess_columns(df)
    if not all([col_prod, col_date, col_qtd]):
        raise ValueError("Não foi possível identificar colunas automaticamente.")

    df = df.rename(columns={col_prod: 'produto_id', col_date: 'data', col_qtd: 'quantidade'})

    df['data'] = pd.to_datetime(df['data'], errors='coerce')
    df['quantidade'] = pd.to_numeric(df['quantidade'], errors='coerce').fillna(0)

    df['periodo'] = df['data'].dt.to_period('M')

    vendas_mensais = (
        df.groupby(['produto_id', 'periodo'])['quantidade']
        .sum()
        .reset_index()
        .sort_values(['produto_id', 'periodo'])
    )

    return vendas_mensais


def calcular_previsao_por_produto(vendas_produto_periodo, weights=DEFAULT_WEIGHTS):
    vendas = list(vendas_produto_periodo)
    n = len(weights)

    if len(vendas) == 0:
        return 0.0

    if len(vendas) < n:
        return round(float(pd.Series(vendas).mean()), 2)

    ultimos = vendas[-n:]
    total_w = sum(weights)
    norm = [w / total_w for w in weights]

    previsao = sum(v * w for v, w in zip(ultimos, norm))
    return round(float(previsao), 2)


def gerar_previsoes(vendas_mensais, weights):
    resultados = {}
    grouped = vendas_mensais.groupby('produto_id')

    for produto, group in grouped:
        group = group.sort_values('periodo')
        vendas = group['quantidade'].tolist()
        periodos = group['periodo'].astype(str).tolist()

        if len(vendas) < 3:
            previsao = round(float(pd.Series(vendas).mean()), 2)
        else:
            previsao = calcular_previsao_por_produto(vendas, weights)

        resultados[str(produto)] = {
            "previsao_proximo_mes": previsao,
            "ultimas_vendas": vendas[-6:],
            "ultimos_periodos": periodos[-6:]
        }

    return resultados


# ============================================================
#   GERAR GRÁFICOS
# ============================================================

def gerar_grafico(produto, historico, previsao):
    pasta = "output/graficos"
    os.makedirs(pasta, exist_ok=True)

    meses = list(range(1, len(historico) + 1))

    plt.figure(figsize=(6, 4))
    plt.plot(meses, historico, marker="o", label="Vendas reais")
    plt.plot(len(meses) + 1, previsao, marker="x", color="red", label="Previsão")

    plt.title(f"Produto {produto} - Histórico e Previsão")
    plt.xlabel("Período (meses)")
    plt.ylabel("Quantidade Vendida")
    plt.legend()
    plt.tight_layout()

    caminho = f"{pasta}/{produto}.png"
    plt.savefig(caminho)
    plt.close()

    print(f"Gráfico gerado: {caminho}")


# ============================================================
#   MAIN + GERAÇÃO DOS GRÁFICOS
# ============================================================

def main(csv_path, out_json='previsoes_vendas.json', weights=DEFAULT_WEIGHTS):
    df_raw = carregar_csv(csv_path)
    vendas_mensais = preparar_dataset(df_raw)
    previsoes = gerar_previsoes(vendas_mensais, weights)

    with open(out_json, 'w', encoding='utf-8') as f:
        json.dump(previsoes, f, ensure_ascii=False, indent=2)

    print(f"Previsões escritas em {out_json}\n")

    for pid, info in previsoes.items():
        print(f"Produto {pid} -> previsão próximo mês: {info['previsao_proximo_mes']} (últimos: {info['ultimas_vendas']})")

    print("\nGerando gráficos...\n")

    for produto, info in previsoes.items():
        gerar_grafico(produto, info["ultimas_vendas"], info["previsao_proximo_mes"])


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("csv", help="Caminho para o CSV de vendas")
    parser.add_argument("--out", "-o", default="previsoes_vendas.json")
    parser.add_argument("--weights", "-w", nargs="+", type=float, default=DEFAULT_WEIGHTS)
    args = parser.parse_args()

    main(args.csv, out_json=args.out, weights=args.weights)
