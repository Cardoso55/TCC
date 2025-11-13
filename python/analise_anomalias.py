"""
Detecção de anomalias nas vendas com IsolationForest.
Detecta outliers na série de quantidade vendedida por produto.
"""
import pandas as pd
from sklearn.ensemble import IsolationForest
from typing import Tuple

def detect_anomalies(df: pd.DataFrame, contamination: float = 0.01, random_state: int = 42) -> pd.DataFrame:
    """
    Recebe df com colunas ['date','product_id','quantity', ...]
    Retorna df com coluna 'anomaly' = True se anomalia.
    Operação: aplica IsolationForest por produto (ou global se poucas observações).
    """
    out_frames = []
    for pid, g in df.groupby('product_id'):
        X = g[['quantity']].values
        if len(X) < 5:
            g['anomaly'] = False
            out_frames.append(g)
            continue
        iso = IsolationForest(contamination=contamination, random_state=random_state)
        preds = iso.fit_predict(X)  # -1 anomalia, 1 normal
        g = g.copy()
        g['anomaly'] = preds == -1
        out_frames.append(g)
    return pd.concat(out_frames, ignore_index=True)
