# arquivo: /python/forecast_wrapper.py
import utils.db

def forecast_wrapper(product_id: int, tipo_previsao: str) -> float:
    """
    Busca previsao na ordem:
      1) tipo_previsao solicitado
      2) 'semanal'
      3) 'mensal'
    Retorna 0.0 se não encontrar nada.
    """
    conn = utils.db.get_connection()
    cur = conn.cursor()

    tipos_tentativa = [tipo_previsao]
    if tipo_previsao != 'semanal':
        tipos_tentativa.append('semanal')
    if 'mensal' not in tipos_tentativa:
        tipos_tentativa.append('mensal')

    sql = """
        SELECT previsao_quantidade
        FROM previsoes_tbl
        WHERE id_produto = %s AND tipo_previsao = %s
        ORDER BY criado_em DESC
        LIMIT 1
    """

    try:
        for tp in tipos_tentativa:
            cur.execute(sql, (product_id, tp))
            row = cur.fetchone()
            if row and row[0] is not None:
                return float(row[0])
        return 0.0
    except Exception as e:
        print(f"[ERRO][FORECAST] Falha ao buscar previsão para produto {product_id}: {e}")
        return 0.0
    finally:
        cur.close()
        conn.close()
