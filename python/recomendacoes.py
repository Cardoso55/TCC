"""
Recomendações baseadas em vendas:
- Repor produtos próximos ao reorder_point
- Identificar produtos encalhados (alto estoque e baixa venda)
- Recomendações de cross-sell simples (co-ocorrência em mesma transaction_id)
"""
import pandas as pd
from typing import List, Tuple

def recommend_restock(stock_df: pd.DataFrame, reorder_df: pd.DataFrame, threshold_days: int = 7) -> pd.DataFrame:
    """
    stock_df: product_id, current_stock
    reorder_df: product_id, reorder_point
    Retorna produtos que precisam reposição
    """
    merged = stock_df.merge(reorder_df[['product_id','reorder_point']], on='product_id', how='left')
    merged['need_restock'] = merged['current_stock'] <= merged['reorder_point']
    return merged[merged['need_restock']]

def detect_slow_movers(stock_df: pd.DataFrame, sales_last_n_days: pd.DataFrame, days: int = 30, stock_threshold: int = 20) -> pd.DataFrame:
    """
    sales_last_n_days: product_id, sum_quantity_last_n_days
    Produtos com alto estoque e pouca venda -> encalhados
    """
    df = stock_df.merge(sales_last_n_days, on='product_id', how='left').fillna(0)
    df['slow'] = (df['current_stock'] >= stock_threshold) & (df['sum_quantity_last_n_days'] <= max(1, days*0.1))
    return df[df['slow']]

def cross_sell_recommendations(transactions_df: pd.DataFrame, top_k: int = 3) -> dict:
    """
    transactions_df: columns ['transaction_id','product_id']
    Retorna dicionário product_id -> [produtos frequentemente comprados juntos]
    """
    # criar co-ocorrência por transaction
    pairs = {}
    grouped = transactions_df.groupby('transaction_id')['product_id'].apply(list)
    from collections import Counter, defaultdict
    co = defaultdict(Counter)
    for basket in grouped:
        unique = list(set(basket))
        for i in unique:
            for j in unique:
                if i == j: continue
                co[i][j] += 1
    out = {}
    for i, counter in co.items():
        out[i] = [prod for prod, _ in counter.most_common(top_k)]
    return out
