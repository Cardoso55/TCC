# python/utils/estoque_db.py
# utils/estoque_db.py
import pandas as pd
from utils.db_connector import conectar

def carregar_estoque_produtos():
    """
    Carrega os produtos e estoques do banco de dados.
    """
    engine = conectar()
    if engine is None:
        print("❌ Falha na conexão com o banco.")
        return pd.DataFrame()

    try:
        query = """
        SELECT 
            e.id_estoque,
            e.IdProdutos_TBL,
            p.nome,
            e.quantidade_atual
        FROM estoque_tbl e
        INNER JOIN produtos_tbl p ON e.IdProdutos_TBL = p.id_produto
        """
        df = pd.read_sql(query, engine)
        return df
    except Exception as e:
        print(f"❌ Erro ao carregar estoque: {e}")
        return pd.DataFrame()
    finally:
        if hasattr(engine, "dispose"):
            engine.dispose()


def atualizar_estoque_minimo(id_estoque, novo_estoque_minimo):
    engine = conectar()
    if not engine:
        return

    try:
        cursor = engine.cursor()
        cursor.execute("""
            UPDATE estoque_tbl
            SET quantidade_minima = %s
            WHERE id_estoque = %s
        """, (novo_estoque_minimo, id_estoque))
        engine.commit()
        engine.dispose()
        print(f"✅ Estoque mínimo atualizado (ID {id_estoque}): {novo_estoque_minimo}")
    except Exception as e:
        print("❌ Erro ao atualizar estoque mínimo:", e)
