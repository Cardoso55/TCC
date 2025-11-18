import pandas as pd
from sqlalchemy import text

def carregar_estoque_produtos(engine):
    try:
        query = """
        SELECT 
            e.id_estoque,
            e.quantidade_atual,
            e.quantidade_minima,
            p.codigo_produto
        FROM estoque_tbl e
        JOIN produtos_tbl p ON e.idProdutos_TBL = p.id_produto
        """
        return pd.read_sql(query, engine)
    except Exception as e:
        print(f"Erro ao carregar estoque: {e}")
        return pd.DataFrame()

def atualizar_estoque_minimo(engine, id_estoque, novo_minimo):
    try:
        with engine.begin() as conn:
            conn.execute(
                text("""
                    UPDATE estoque_tbl
                    SET quantidade_minima = :minimo
                    WHERE id_estoque = :id
                """),
                {
                    "minimo": int(novo_minimo),     # <-- CORREÇÃO DEFINITIVA
                    "id": int(id_estoque)           # <-- CORREÇÃO DEFINITIVA
                }
            )
    except Exception as e:
        print(f"Erro ao atualizar estoque mínimo: {e}")
