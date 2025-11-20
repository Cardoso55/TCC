import pandas as pd

def prepare_demand_dataset(df):
    # Garantir que a data está correta
    df["data_venda"] = pd.to_datetime(df["data_venda"])

    # Ordenar por data
    df = df.sort_values("data_venda")

    # Criar features de data
    df["ano"] = df["data_venda"].dt.year
    df["mes"] = df["data_venda"].dt.month
    df["dia"] = df["data_venda"].dt.day
    df["dia_semana"] = df["data_venda"].dt.weekday
    df["dia_num"] = (df["data_venda"] - df["data_venda"].min()).dt.days

    # Criar lags básicos
    df["lag_1"] = df["quantidade"].shift(1)

    # médias móveis curtas
    df["media_3"] = df["quantidade"].rolling(3).mean()

    # remover linhas inválidas
    df = df.dropna()

    df = df.reset_index(drop=True)
    return df
