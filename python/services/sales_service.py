# arquivo: /python/services/sales_service.py
import utils.db

def get_past_sales(product_id: int, dias: int = 30) -> int:
    """
    Retorna a soma das vendas do produto nos últimos X dias.
    Isso funciona como consumo histórico.
    """
    conn = utils.db.get_connection()
    cur = conn.cursor()

    sql = """
        SELECT COALESCE(SUM(quantidade), 0)
        FROM vendas_tbl
        WHERE id_produto = %s
        AND data_venda >= NOW() - INTERVAL %s DAY
    """

    try:
        cur.execute(sql, (product_id, dias))
        row = cur.fetchone()
        return int(row[0])
    except Exception as e:
        print(f"[ERRO][SALES] Falha ao buscar vendas do produto {product_id}: {e}")
        return 0
    finally:
        cur.close()
        conn.close()
