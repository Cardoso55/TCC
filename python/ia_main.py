import os
import pandas as pd
from sqlalchemy import text
from utils.db_connector import conectar

def calcular_estoque_minimo(vendas_csv, engine):
    print("🔍 Lendo arquivo de vendas...")
    df_vendas = pd.read_csv(vendas_csv)

    if "codigo_produto" not in df_vendas.columns or "quantidade_vendida" not in df_vendas.columns:
        print("❌ CSV inválido. Deve conter colunas: codigo_produto, quantidade_vendida.")
        return

    # Agrupar vendas por produto e calcular média diária
    df_media = (
        df_vendas.groupby("codigo_produto")["quantidade_vendida"]
        .mean()
        .reset_index()
        .rename(columns={"quantidade_vendida": "media_diaria"})
    )

    # Calcular estoque mínimo com margem de 3 dias
    df_media["quantidade_minima_sugerida"] = (df_media["media_diaria"] * 3).round().astype(int)

    print("📊 Médias calculadas:")
    print(df_media)

    # Buscar relação código_produto ↔ id_produto ↔ id_estoque
    query = """
        SELECT 
            e.id_estoque,
            e.idProdutos_TBL AS id_produto_fk,
            p.codigo_produto
        FROM estoque_tbl e
        INNER JOIN produtos_tbl p ON e.idProdutos_TBL = p.id_produto
    """
    df_relacao = pd.read_sql(query, engine)

    # Juntar com o cálculo das médias
    df_final = pd.merge(df_relacao, df_media, on="codigo_produto", how="inner")

    # Atualizar o banco com os novos estoques mínimos
    with engine.begin() as conn:
        for _, row in df_final.iterrows():
            update_query = text("""
                UPDATE estoque_tbl
                SET quantidade_minima = :quantidade_minima
                WHERE id_estoque = :id_estoque
            """)
            conn.execute(update_query, {
                "quantidade_minima": int(row["quantidade_minima_sugerida"]),
                "id_estoque": int(row["id_estoque"])
            })
            print(f"✅ Produto {row['codigo_produto']} atualizado → Estoque mínimo = {row['quantidade_minima_sugerida']}")

    print("🎯 Estoques mínimos atualizados com sucesso!")


def main():
    engine = conectar()
    if not engine:
        print("❌ Erro na conexão com o banco.")
        return

    caminho_csv = os.path.join(os.path.dirname(__file__), "data", "vendas.csv")
    if not os.path.exists(caminho_csv):
        print("❌ Arquivo vendas.csv não encontrado em /python/data/")
        return

    calcular_estoque_minimo(caminho_csv, engine)
    engine.dispose()

if __name__ == "__main__":
    main()
