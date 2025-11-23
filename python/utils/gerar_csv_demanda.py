# python/utils/gerar_csv_demanda.py
import pandas as pd
from data_loader import carregar_dataset_demanda
import os

def prepare_from_dataframe(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()

    # Normalizar quantidade
    if "quantidade_vendida" in df.columns:
        df["quantidade"] = df["quantidade_vendida"]

    # Normalizar data
    if "dia" in df.columns:
        df["data"] = pd.to_datetime(df["dia"])
    elif "data_venda" in df.columns:
        df["data"] = pd.to_datetime(df["data_venda"])
    elif "data" in df.columns:
        df["data"] = pd.to_datetime(df["data"])
    else:
        raise ValueError("Nenhuma coluna de data encontrada!")

    df = df.sort_values(["id_produto", "data"])

    # Features temporais
    df["ano"] = df["data"].dt.year
    df["mes"] = df["data"].dt.month
    df["dia_num"] = df["data"].dt.day
    df["dia_semana"] = df["data"].dt.weekday

    # Agora criar lags — MAS sem excluir tudo no final
    df["lag_1"] = df.groupby("id_produto")["quantidade"].shift(1)

    # Só cria lag_7 se existir histórico suficiente
    df["lag_7"] = df.groupby("id_produto")["quantidade"].shift(7)
    df["lag_30"] = df.groupby("id_produto")["quantidade"].shift(30)

    # Médias móveis
    df["media_7"] = df.groupby("id_produto")["quantidade"].rolling(7).mean().reset_index(0, drop=True)
    df["media_30"] = df.groupby("id_produto")["quantidade"].rolling(30).mean().reset_index(0, drop=True)

    # Agora o segredo:
    # Em vez de deletar tudo, mantém só o que tem lag_1 válido
    df = df[df["lag_1"].notna()].reset_index(drop=True)

    return df


def main():
    csv_out = os.path.join(os.path.dirname(__file__), "demanda_dataset.csv")
    print("📦 Carregando vendas do banco...")
    df = carregar_dataset_demanda()
    print("Linhas carregadas do banco:", len(df))

    print("🔧 Preparando dataset (lags, médias)...")
    try:
        df_prep = prepare_from_dataframe(df)
    except Exception as e:
        print("Erro ao preparar dataframe:", e)
        raise

    print("Linhas após preparar:", len(df_prep))
    print("Colunas finais:", df_prep.columns.tolist())

    print(f"💾 Salvando CSV em {csv_out} ...")
    df_prep.to_csv(csv_out, index=False, encoding="utf-8")

    print("🎉 CSV gerado com sucesso!")

if __name__ == "__main__":
    main()
