"""
Gera alertas combinando regras simples:
- Produto encalhado
- Produto próximo de acabar (previsão vs estoque)
- Anomalias nas vendas
- Alertas customizáveis
"""
import pandas as pd

def generate_alerts(stock_df: pd.DataFrame,
                    reorder_df: pd.DataFrame,
                    forecast_df: pd.DataFrame,
                    anomalies_df: pd.DataFrame,
                    slow_movers_df: pd.DataFrame) -> pd.DataFrame:
    """
    stock_df columns: product_id, current_stock
    forecast_df columns: product_id, forecast_7d (sum or array)
    anomalies_df: linhas marcadas como anomaly True
    slow_movers_df: produtos encalhados
    """
    alerts = []
    # 1) próximos de acabar
    for _, row in forecast_df.iterrows():
        pid = row['product_id']
        forecast_7d = row.get('forecast_7d', None)
        cur_stock = int(stock_df.loc[stock_df['product_id']==pid, 'current_stock'].iloc[0]) if pid in stock_df['product_id'].values else None
        rp = int(reorder_df.loc[reorder_df['product_id']==pid, 'reorder_point'].iloc[0]) if pid in reorder_df['product_id'].values else None
        if cur_stock is None:
            continue
        if forecast_7d is not None and cur_stock <= forecast_7d:
            alerts.append({'product_id': pid, 'type': 'stock_runout_risk', 'message': f'Produto {pid} pode acabar em 7 dias (estoque {cur_stock} <= previsão {forecast_7d})'})
        elif rp is not None and cur_stock <= rp:
            alerts.append({'product_id': pid, 'type': 'below_reorder_point', 'message': f'Produto {pid} está no ponto de ressuprimento (estoque {cur_stock} <= RP {rp})'})

    # 2) anomalias -> alertas por produto
    for pid in anomalies_df[anomalies_df['anomaly']]['product_id'].unique():
        alerts.append({'product_id': pid, 'type': 'anomaly', 'message': f'Anomalia detectada nas vendas do produto {pid}.'})

    # 3) encalhados
    for _, r in slow_movers_df.iterrows():
        alerts.append({'product_id': r['product_id'], 'type': 'slow_mover', 'message': f'Produto {r["product_id"]} possivelmente encalhado (estoque {r["current_stock"]}, vendas últimos dias {r.get("sum_quantity_last_n_days",0)})'})

    return pd.DataFrame(alerts)
