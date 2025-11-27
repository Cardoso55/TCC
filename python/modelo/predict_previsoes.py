import os
import pandas as pd
import numpy as np
import pymysql
import pickle
from datetime import date, timedelta

# ============================
# CONEXÕES
# ============================

# Conexão PARA PANDAS (SEM DictCursor)
def get_conn_pandas():
    return pymysql.connect(
        host="localhost",
        user="root",
        password="",
        database="macawsystems"
    )

# Conexão normal para INSERT/UPDATE
def get_conn():
    return pymysql.connect(
        host="localhost",
        user="root",
        password="",
        database="macawsystems",
        cursorclass=pymysql.cursors.DictCursor
    )

# ============================
# Carregar modelo
# ============================

BASE_DIR = os.path.dirname(os.path.abspath(__file__))

with open(os.path.join(BASE_DIR, "modelo_demanda.pkl"), "rb") as f:
    modelo = pickle.load(f)

print("Modelo carregado!")

# ============================
# Carregar vendas
# ============================
def carregar_vendas():
    conn = get_conn_pandas()
    df = pd.read_sql_query(
        "SELECT id_produto, quantidade, data_venda FROM vendas_tbl ORDER BY id_produto, data_venda",
        conn
    )
    conn.close()

    df["data_venda"] = pd.to_datetime(df["data_venda"], errors="coerce")
    df = df.dropna(subset=["data_venda", "quantidade"])
    return df

df = carregar_vendas()
print("DF original:", len(df))

# ============================
# Features
# ============================
def montar_features(df):
    df = df.copy()
    df["data"] = df["data_venda"]

    df["ano"] = df["data"].dt.year
    df["mes"] = df["data"].dt.month
    df["dia_num"] = df["data"].dt.dayofyear
    df["dia_semana"] = df["data"].dt.weekday
    df["fim_de_semana"] = df["dia_semana"].isin([5, 6]).astype(int)
    df["trimestre"] = df["data"].dt.quarter
    df["tendencia"] = df.groupby("id_produto").cumcount()

    df["sin_ano"] = np.sin(2 * np.pi * df["dia_num"] / 365)
    df["cos_ano"] = np.cos(2 * np.pi * df["dia_num"] / 365)

    for lag in [1, 7, 30, 90]:
        df[f"lag_{lag}"] = df.groupby("id_produto")["quantidade"].shift(lag)

    for w in [7, 30, 90]:
        df[f"media_{w}"] = df.groupby("id_produto")["quantidade"].rolling(w).mean().reset_index(level=0, drop=True)

    for w in [7, 30]:
        df[f"std_{w}"] = df.groupby("id_produto")["quantidade"].rolling(w).std().reset_index(level=0, drop=True)

    df["diff_1"] = df["quantidade"] - df["lag_1"]
    df["diff_7"] = df["quantidade"] - df["lag_7"]
    df["diff_30"] = df["quantidade"] - df["lag_30"]

    df = df.dropna()
    return df

df_feat = montar_features(df)
print("DF após features:", len(df_feat))

# Últimas linhas
ultimas = df_feat.groupby("id_produto").tail(1)
print("Produtos únicos:", ultimas["id_produto"].nunique())

features = [
    col for col in ultimas.columns
    if col not in ["quantidade", "data", "data_venda"]
]


# ============================
# Salvar no banco
# ============================
conn = get_conn()
cursor = conn.cursor()


# datas corretas
data_diaria = date.today() + timedelta(days=1)
data_semanal = date.today() + timedelta(days=7)
data_mensal = date.today() + timedelta(days=30)

sql_insert = """
INSERT INTO previsoes_tbl
(id_produto, data_previsao, tipo_previsao, previsao_quantidade)
VALUES
(%s, %s, 'diario', %s),
(%s, %s, 'semanal', %s),
(%s, %s, 'mensal', %s)
"""
cursor.execute("TRUNCATE TABLE previsoes_tbl")

for _, row in ultimas.iterrows():
    pid = int(row["id_produto"])
    
    entrada = row[features].values.reshape(1, -1)

    p_dia = max(float(modelo.predict(entrada)[0]), 0.1)
    p_sem = p_dia * 7
    p_mes = p_dia * 30

    cursor.execute(sql_insert, (pid, data_diaria, p_dia, pid, data_semanal, p_sem, pid, data_mensal, p_mes))

conn.commit()
conn.close()

print("Previsões salvas com sucesso!")
