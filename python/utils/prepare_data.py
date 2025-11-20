import pandas as pd

def prepare_dataset():
    input_path = "data/vendas_dataset.csv"
    output_path = "data/vendas_prepared.csv"

    df = pd.read_csv(input_path)

    # --- 1. Converter data para datetime ---
    df["data_venda"] = pd.to_datetime(df["data_venda"])

    # --- 2. Criar colunas derivadas ---
    df["ano"] = df["data_venda"].dt.year
    df["mes"] = df["data_venda"].dt.month
    df["dia"] = df["data_venda"].dt.day
    df["dia_semana"] = df["data_venda"].dt.dayofweek  # 0=segunda
    df["semana_ano"] = df["data_venda"].dt.isocalendar().week

    # --- 3. Agrupar vendas por dia e por produto ---
    df_grouped = df.groupby(
        ["id_produto", "nome_produto", "categoria", "data_venda", "ano", "mes", "dia", "dia_semana", "semana_ano"]
    )["quantidade"].sum().reset_index()

    # --- 4. Salvar dataset preparado ---
    df_grouped.to_csv(output_path, index=False, encoding="utf-8")

    print("[OK] Dataset preparado com sucesso!")
    print(df_grouped.head())


if __name__ == "__main__":
    prepare_dataset()
