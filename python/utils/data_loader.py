import sys
import os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.append(ROOT)


import pandas as pd
import numpy as np
from utils.db import get_connection

def carregar_dataset_demanda():
    """
    Carrega as vendas do banco, agrega por dia/produto e devolve dataset pronto,
    com preenchimento de NULLs para lags, médias móveis e diferenças.
    """

    conn = get_connection()
    if conn is None:
        print("❌ ERRO: Não foi possível conectar ao banco! Encerrando.")
        return None

    cursor = conn.cursor()

    query = """
        SELECT 
            id_produto,
            quantidade,
            DATE(data_venda) AS dia
        FROM vendas_tbl
        ORDER BY data_venda ASC;
    """

    cursor.execute(query)
    resultados = cursor.fetchall()
    cursor.close()
    conn.close()

    df = pd.DataFrame(resultados, columns=["id_produto", "quantidade", "dia"])

    # converter pra datetime
    df["dia"] = pd.to_datetime(df["dia"])

    # ---- 🔥 AGRUPAMENTO (ESSENCIAL) 🔥 ----
    df = df.groupby(["id_produto", "dia"], as_index=False).agg({
        "quantidade": "sum"
    })

    # Criar colunas de tempo
    df["ano"] = df["dia"].dt.year
    df["mes"] = df["dia"].dt.month
    df["dia_num"] = df["dia"].dt.dayofyear
    df["dia_semana"] = df["dia"].dt.weekday
    df["fim_de_semana"] = df["dia_semana"].isin([5,6]).astype(int)
    df["trimestre"] = df["dia"].dt.quarter

    # Tendência
    df["tendencia"] = df.groupby("id_produto").cumcount()

    # Sin/Cos sazonal
    df["sin_ano"] = np.sin(2 * np.pi * df["dia_num"] / 365)
    df["cos_ano"] = np.cos(2 * np.pi * df["dia_num"] / 365)

    # Lags
    for lag in [1,7,30,90]:
        df[f"lag_{lag}"] = df.groupby("id_produto")["quantidade"].shift(lag)
        # Preencher NULLs com média móvel de 7 dias do produto
        df[f"lag_{lag}"] = df.groupby("id_produto")[f"lag_{lag}"].transform(
            lambda x: x.fillna(x.rolling(7, min_periods=1).mean())
        )

    # Médias móveis
    for w in [7,30,90]:
        df[f"media_{w}"] = df.groupby("id_produto")["quantidade"].transform(
            lambda x: x.rolling(w, min_periods=1).mean()
        )

    # Desvios padrão
    for w in [7,30]:
        df[f"std_{w}"] = df.groupby("id_produto")["quantidade"].transform(
            lambda x: x.rolling(w, min_periods=1).std().fillna(0)
        )

    # Diferenças
    df["diff_1"] = df["quantidade"] - df["lag_1"]
    df["diff_7"] = df["quantidade"] - df["lag_7"]
    df["diff_30"] = df["quantidade"] - df["lag_30"]

    for col in ["diff_1","diff_7","diff_30"]:
        df[col] = df[col].fillna(0)

    return df


