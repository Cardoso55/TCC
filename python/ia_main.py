import os
import pandas as pd
from utils.estoque_db import carregar_estoque_produtos, atualizar_estoque_minimo
from alertas_ruptura import calcular_media_diaria, calcular_dias_ate_acabar, gerar_alertas_de_ruptura, salvar_alertas_no_db

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

# 1) calcula médias (janela 30 dias)
medias = calcular_media_diaria(df_vendas, dias_anteriores=30)

# 2) pega estoque (carregar_estoque_produtos já retorna id_estoque, codigo_produto, quantidade_atual)
estoque_df = carregar_estoque_produtos(engine)

# 3) calcula dias até acabar e risco
ruptura_df = calcular_dias_ate_acabar(estoque_df, medias, fallback_days=30)

# 4) gera alertas (lista de dicts)
alertas = gerar_alertas_de_ruptura(ruptura_df)

# 5) salva no banco (opcional)
salvar_alertas_no_db(engine, alertas)

# 6) print pra debug
print("Alertas de ruptura gerados:", alertas)

def main():
    from utils.db_connector import conectar_engine
    engine = conectar_engine()

    caminho_csv = obter_csv_mais_recente()
    if not caminho_csv:
        return

    calcular_estoque_minimo(caminho_csv, engine)

if __name__ == "__main__":
    main()
