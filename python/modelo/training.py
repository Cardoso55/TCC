import pandas as pd
import numpy as np
import pymysql
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, mean_squared_error
from sklearn.ensemble import HistGradientBoostingRegressor
import pickle

def carregar_vendas():
    print("Carregando vendas do banco...")

    conn = pymysql.connect(
        host="localhost",
        user="root",
        password="",
        database="macawsystems"
    )

    query = """
        SELECT 
            id_produto,
            quantidade,
            data_venda
        FROM vendas_tbl
        ORDER BY id_produto, data_venda
    """
    
    df = pd.read_sql(query, conn)
    conn.close()
    print(f"Linhas carregadas do banco: {len(df)}")
    return df

def preparar_dataset(df):
    print("Preparando dataset (features avançadas)...")

    df["data"] = pd.to_datetime(df["data_venda"])
    df["ano"] = df["data"].dt.year
    df["mes"] = df["data"].dt.month
    df["dia_num"] = df["data"].dt.dayofyear
    df["dia_semana"] = df["data"].dt.weekday
    df["fim_de_semana"] = df["dia_semana"].isin([5,6]).astype(int)
    df["trimestre"] = df["data"].dt.quarter
    
    # Tendência por produto
    df["tendencia"] = df.groupby("id_produto").cumcount()

    # Sin/Cos sazonal
    df["sin_ano"] = np.sin(2 * np.pi * df["dia_num"] / 365)
    df["cos_ano"] = np.cos(2 * np.pi * df["dia_num"] / 365)

    # Lags
    for lag in [1,7,30,90]:
        df[f"lag_{lag}"] = df.groupby("id_produto")["quantidade"].shift(lag)

    # Médias móveis
    for w in [7,30,90]:
        df[f"media_{w}"] = df.groupby("id_produto")["quantidade"].rolling(w).mean().reset_index(0,drop=True)

    # Desvios padrão
    for w in [7,30]:
        df[f"std_{w}"] = df.groupby("id_produto")["quantidade"].rolling(w).std().reset_index(0,drop=True)

    # Diferenças
    df["diff_1"] = df["quantidade"] - df["lag_1"]
    df["diff_7"] = df["quantidade"] - df["lag_7"]
    df["diff_30"] = df["quantidade"] - df["lag_30"]

    df = df.dropna()
    print(f"Linhas após preparar: {len(df)}")
    print("Colunas finais:", df.columns.tolist())
    return df

def treinar_modelo():
    df = carregar_vendas()
    df = preparar_dataset(df)

    target = "quantidade"
    features = [col for col in df.columns if col not in ["quantidade","data","data_venda"]]

    X = df[features]
    y = df[target]

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42
    )

    print("Treinando modelo avançado...")
    model = HistGradientBoostingRegressor(max_depth=6, learning_rate=0.05)

    model.fit(X_train, y_train)
    preds = model.predict(X_test)

    mae = mean_absolute_error(y_test, preds)
    rmse = mean_squared_error(y_test, preds, squared=False)
    mape = np.mean(np.abs((y_test - preds) / y_test)) * 100

    print(f"MAE: {mae:.2f}")
    print(f"RMSE: {rmse:.2f}")
    print(f"MAPE: {mape:.2f}%")

    with open("modelo/modelo_demanda.pkl", "wb") as f:
        pickle.dump(model, f)

    print("Modelo salvo em: modelo/modelo_demanda.pkl")

if __name__ == "__main__":
    treinar_modelo()
