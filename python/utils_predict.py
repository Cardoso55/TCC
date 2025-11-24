# python/utils_predict.py
import pandas as pd
import numpy as np
import joblib
from datetime import datetime, timedelta
from utils.db import get_connection  # sua conexão mysql.connector

# ============================
# PEGAR PRODUTOS
# ============================
def get_produtos():
    try:
        conn = get_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id_produto AS id, nome FROM produtos_tbl")
        produtos = cursor.fetchall()
        cursor.close()
        conn.close()
        return produtos
    except Exception as e:
        print("Erro ao buscar produtos:", e)
        return []

# ============================
# PEGAR HISTÓRICO DE VENDAS
# ============================
def get_historico_vendas():
    try:
        conn = get_connection()
        cursor = conn.cursor()
        cursor.execute("SELECT id_produto, data_venda, quantidade FROM vendas_tbl")
        rows = cursor.fetchall()
        df = pd.DataFrame(rows, columns=['id_produto', 'data', 'quantidade'])
        df['data'] = pd.to_datetime(df['data'])
        cursor.close()
        conn.close()
        return df
    except Exception as e:
        print("Erro ao buscar histórico de vendas:", e)
        return pd.DataFrame(columns=['id_produto', 'data', 'quantidade'])

# ============================
# LIMPAR PREVISÕES ANTIGAS
# ============================
def limpar_previsoes():
    try:
        conn = get_connection()
        cursor = conn.cursor()
        cursor.execute("TRUNCATE TABLE previsoes_tbl")
        conn.commit()
        cursor.close()
        conn.close()
        print("Todas as previsões antigas foram removidas")
    except Exception as e:
        print("Erro ao limpar previsões:", e)

# ============================
# INSERIR PREVISÃO NO BANCO
# ============================
def inserir_previsao(df_previsao, tipo_previsao="diario"):
    try:
        conn = get_connection()
        cursor = conn.cursor()

        for _, row in df_previsao.iterrows():
            if tipo_previsao == "diario":
                data_prevista = datetime.now() + timedelta(days=1)
            elif tipo_previsao == "semanal":
                data_prevista = datetime.now() + timedelta(days=7)
            elif tipo_previsao == "mensal":
                data_prevista = datetime.now() + timedelta(days=30)
            else:
                data_prevista = datetime.now() + timedelta(days=1)

            cursor.execute("""
                INSERT INTO previsoes_tbl
                (id_produto, data_previsao, tipo_previsao, previsao_quantidade)
                VALUES (%s, %s, %s, %s)
                ON DUPLICATE KEY UPDATE previsao_quantidade=%s
            """, (
                int(row["id_produto"]),
                data_prevista.strftime("%Y-%m-%d"),
                tipo_previsao,
                float(row["previsao_quantidade"]),
                float(row["previsao_quantidade"])
            ))

        conn.commit()
        cursor.close()
        conn.close()
        print(f"Previsão {tipo_previsao} inserida no banco")
    except Exception as e:
        print("Erro ao inserir previsões:", e)

# ============================
# CARREGAR MODELO
# ============================
def carregar_modelo(caminho_modelo="modelo/modelo_demanda.pkl"):
    try:
        return joblib.load(caminho_modelo)
    except Exception as e:
        print("Erro ao carregar modelo:", e)
        return None

# ============================
# PREPARAR FEATURES PARA ML
# ============================
def preparar_features(df, dias_a_frente=1):
    df = df.copy()
    df = df.sort_values(["id_produto", "data"])

    # Colunas temporais
    df["ano"] = df["data"].dt.year
    df["mes"] = df["data"].dt.month
    df["dia_num"] = df["data"].dt.day
    df["dia_semana"] = df["data"].dt.weekday
    df["fim_de_semana"] = df["dia_semana"].isin([5,6]).astype(int)
    df["trimestre"] = df["data"].dt.quarter

    # Lags
    df["lag_1"] = df.groupby("id_produto")["quantidade"].shift(1)
    df["lag_7"] = df.groupby("id_produto")["quantidade"].shift(7)
    df["lag_30"] = df.groupby("id_produto")["quantidade"].shift(30)

    # Médias móveis
    df["media_7"] = df.groupby("id_produto")["quantidade"].rolling(7).mean().reset_index(0, drop=True)
    df["media_30"] = df.groupby("id_produto")["quantidade"].rolling(30).mean().reset_index(0, drop=True)

    # Última linha de cada produto
    df_previsao = df.groupby("id_produto").tail(1).copy()
    return df_previsao
