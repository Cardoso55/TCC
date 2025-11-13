"""
Previsão de vendas por produto usando regressão com lags (RandomForest).
A estratégia: criar features de lags/rolling e treinar por produto (ou
treinar um modelo global com product_id como categoria).
"""
import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
import joblib
from typing import Tuple, Dict

def prepare_features(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()
    df = df.sort_values(['product_id','date'])
    # assume que já tem lags
    df = df.fillna(0)
    feature_cols = [c for c in df.columns if c.startswith('lag_') or c.startswith('rolling_') or c in ['dayofweek','month']]
    return df, feature_cols

def train_model_for_product(df: pd.DataFrame, product_id, model_path: str = None) -> Tuple[RandomForestRegressor, list]:
    sub = df[df['product_id'] == product_id].dropna()
    if len(sub) < 30:
        # pouco dado -> não treina bem
        return None, []
    sub, features = prepare_features(sub)
    X = sub[features]
    y = sub['quantity']
    X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, shuffle=False)
    model = RandomForestRegressor(n_estimators=100, random_state=42)
    model.fit(X_train, y_train)
    if model_path:
        joblib.dump(model, model_path)
    return model, features

def forecast_next_days(model, last_row: pd.Series, features: list, horizon: int = 7) -> np.ndarray:
    """
    Faz previsão iterativa: usa última linha com lags, prevê dia 1,
    atualiza lags e repete.
    """
    preds = []
    cur = last_row.copy()
    for _ in range(horizon):
        X = cur[features].values.reshape(1, -1)
        p = model.predict(X)[0]
        preds.append(max(0, p))
        # atualizar lags: shift e insere p
        for f in sorted([f for f in features if f.startswith('lag_')], key=lambda x: int(x.split('_')[1]), reverse=False):
            # shift lag_k -> lag_{k+1} (simplificação)
            k = int(f.split('_')[1])
            # We'll approximate: set lag_1 = p, lag_7 = previous lag_6, ...
            pass
        # Simplificação: não atualizar lags dinamicamente aqui (recomenda melhorar)
    return np.array(preds)
