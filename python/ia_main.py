import os
import pandas as pd
from utils.estoque_db import carregar_estoque_produtos, atualizar_estoque_minimo

def obter_csv_mais_recente():
    pasta_vendas = os.path.join(os.path.dirname(__file__), "data", "vendas")

    arquivos = [
        os.path.join(pasta_vendas, f)
        for f in os.listdir(pasta_vendas)
        if f.endswith(".csv")
    ]

    if not arquivos:
        print("Nenhum arquivo CSV encontrado na pasta de vendas.")
        return None

    arquivo_recente = max(arquivos, key=os.path.getmtime)
    print(f"CSV selecionado: {arquivo_recente}")
    return arquivo_recente

def calcular_estoque_minimo(caminho_csv, engine):
    df = pd.read_csv(caminho_csv)

    if "codigo_produto" not in df.columns or "quantidade_vendida" not in df.columns:
        print("CSV inválido. Deve conter 'codigo_produto' e 'quantidade_vendida'.")
        return

    # Calcula média por produto
    df_grouped = df.groupby("codigo_produto")["quantidade_vendida"].mean().reset_index()
    
    # Calcula mínimo sugerido
    df_grouped["quantidade_minima_sugerida"] = (df_grouped["quantidade_vendida"] * 3).astype(int)

    print("Médias calculadas:")
    print(df_grouped)

    # Carrega produtos do estoque
    estoque_df = carregar_estoque_produtos(engine)

    for _, row in df_grouped.iterrows():
        cod = row["codigo_produto"]
        minimo = int(row["quantidade_minima_sugerida"])  # garante que não é numpy

        if estoque_df.empty:
            continue
        
        # Busca o produto no banco
        match = estoque_df[estoque_df["codigo_produto"] == cod]

        if not match.empty:
            id_estoque = int(match.iloc[0]["id_estoque"])

            # Atualiza o mínimo no banco
            atualizar_estoque_minimo(engine, id_estoque, minimo)

            # Print seguro (sem Unicode)
            print(f"Atualizado: Produto {cod} | mínimo = {minimo}")
        else:
            print(f"Produto {cod} não existe no banco.")

def main():
    from utils.db_connector import conectar_engine
    engine = conectar_engine()

    caminho_csv = obter_csv_mais_recente()
    if not caminho_csv:
        return

    calcular_estoque_minimo(caminho_csv, engine)

if __name__ == "__main__":
    main()
