import pandas as pd
import numpy as np
import pymysql
import pickle
from datetime import timedelta

# Configuração do banco
DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "macawsystems"
}

def carregar_vendas():
    conn = pymysql.connect(**DB_CONFIG)
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
    return df

def preparar_features(df):
    df = df.copy()
    df["data"] = pd.to_datetime(df["data_venda"])
    df["ano"] = df["data"].dt.year
    df["mes"] = df["data"].dt.month
    df["dia_num"] = df["data"].dt.dayofyear
    df["dia_semana"] = df["data"].dt.weekday
    df["fim_de_semana"] = df["dia_semana"].isin([5,6]).astype(int)
    df["trimestre"] = df["data"].dt.quarter
    df["tendencia"] = df.groupby("id_produto").cumcount()
    df["sin_ano"] = np.sin(2 * np.pi * df["dia_num"] / 365)
    df["cos_ano"] = np.cos(2 * np.pi * df["dia_num"] / 365)

    for lag in [1,7,30,90]:
        df[f"lag_{lag}"] = df.groupby("id_produto")["quantidade"].shift(lag)

    for w in [7,30,90]:
        df[f"media_{w}"] = df.groupby("id_produto")["quantidade"].rolling(w).mean().reset_index(0,drop=True)

    for w in [7,30]:
        df[f"std_{w}"] = df.groupby("id_produto")["quantidade"].rolling(w).std().reset_index(0,drop=True)

    df["diff_1"] = df["quantidade"] - df["lag_1"]
    df["diff_7"] = df["quantidade"] - df["lag_7"]
    df["diff_30"] = df["quantidade"] - df["lag_30"]

    df = df.dropna()
    return df

def criar_tabela_previsoes():
    """Cria a tabela prevision_tbl se não existir"""
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()
    cursor.execute("""
        CREATE TABLE IF NOT EXISTS previsoes_tbl (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_produto INT NOT NULL,
            data_previsao DATE NOT NULL,
            previsao FLOAT NOT NULL,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    """)
    conn.commit()
    conn.close()

def salvar_previsoes_no_banco(df_previsoes):
    conn = pymysql.connect(**DB_CONFIG)
    cursor = conn.cursor()

    # Limpa previsões antigas antes de inserir
    cursor.execute("TRUNCATE TABLE previsoes_tbl")

    insert_query = """
        INSERT INTO previsoes_tbl (id_produto, data_previsao, previsao)
        VALUES (%s, %s, %s)
    """

    for _, row in df_previsoes.iterrows():
        cursor.execute(insert_query, (int(row["id_produto"]), row["data_previsao"], float(row["previsao"])))

    conn.commit()
    conn.close()
    print("✅ Previsões salvas no banco com sucesso!")

def prever_proximos_7_dias():
    # Carrega modelo
    with open("modelo/modelo_demanda.pkl", "rb") as f:
        model = pickle.load(f)

    df = carregar_vendas()
    df = preparar_features(df)

    features = [col for col in df.columns if col not in ["quantidade","data","data_venda"]]
    previsoes = []

    for produto in df["id_produto"].unique():
        df_prod = df[df["id_produto"]==produto].copy().sort_values("data")

        for i in range(1,8):
            ultimo = df_prod.tail(1).copy()
            novo_dia = ultimo["data"].values[0] + np.timedelta64(1,'D')

            # Atualiza features de tempo
            ultimo["data"] = novo_dia
            ultimo["ano"] = pd.Timestamp(novo_dia).year
            ultimo["mes"] = pd.Timestamp(novo_dia).month
            ultimo["dia_num"] = pd.Timestamp(novo_dia).dayofyear
            ultimo["dia_semana"] = pd.Timestamp(novo_dia).weekday()
            ultimo["fim_de_semana"] = int(ultimo["dia_semana"].iloc[0] in [5,6])
            ultimo["trimestre"] = pd.Timestamp(novo_dia).quarter
            ultimo["tendencia"] += 1
            ultimo["sin_ano"] = np.sin(2 * np.pi * ultimo["dia_num"] / 365)
            ultimo["cos_ano"] = np.cos(2 * np.pi * ultimo["dia_num"] / 365)

            for lag in [1,7,30,90]:
                if len(df_prod) >= lag:
                    ultimo[f"lag_{lag}"] = df_prod["quantidade"].iloc[-lag]
                else:
                    ultimo[f"lag_{lag}"] = df_prod["quantidade"].mean()

            for w in [7,30,90]:
                if len(df_prod) >= w:
                    ultimo[f"media_{w}"] = df_prod["quantidade"].iloc[-w:].mean()
                else:
                    ultimo[f"media_{w}"] = df_prod["quantidade"].mean()

            for w in [7,30]:
                if len(df_prod) >= w:
                    ultimo[f"std_{w}"] = df_prod["quantidade"].iloc[-w:].std()
                else:
                    ultimo[f"std_{w}"] = 0

            ultimo["diff_1"] = ultimo["quantidade"].iloc[0] - ultimo["lag_1"].iloc[0]
            ultimo["diff_7"] = ultimo["quantidade"].iloc[0] - ultimo["lag_7"].iloc[0]
            ultimo["diff_30"] = ultimo["quantidade"].iloc[0] - ultimo["lag_30"].iloc[0]

            # Predição
            X_novo = ultimo[features]
            pred = model.predict(X_novo)[0]
            ultimo["quantidade"] = pred

            previsoes.append({
                "id_produto": produto,
                "data_previsao": novo_dia,
                "previsao": pred
            })

            df_prod = pd.concat([df_prod, ultimo], ignore_index=True)

    df_previsoes = pd.DataFrame(previsoes)
    return df_previsoes

if __name__ == "__main__":
    criar_tabela_previsoes()
    df_previsoes = prever_proximos_7_dias()
    salvar_previsoes_no_banco(df_previsoes)
