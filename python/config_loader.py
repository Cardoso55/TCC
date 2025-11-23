# arquivo: /python/config_loader.py
from typing import List
from replenishment_manager import ProductConfig

def load_products_from_db(conn) -> List[ProductConfig]:
    """
    Carrega configurações de produtos do banco.
    Tenta juntar produtos_tbl com produtos_config_tbl (se existir).
    Adapte nomes/colunas conforme seu schema.
    """
    cur = conn.cursor()
    # Query padrão: se você não tiver produtos_config_tbl, retorne defaults.
    sql = """
    SELECT p.id_produto, p.nome,
           COALESCE(cfg.lead_time_days, 7) AS lead_time_days,
           COALESCE(cfg.min_qt, 0) AS min_qt,
           COALESCE(cfg.max_qt, 100) AS max_qt,
           COALESCE(cfg.safety_stock, 0) AS safety_stock,
           COALESCE(cfg.reorder_multiple, 1) AS reorder_multiple,
           COALESCE(cfg.enabled, 1) AS enabled
    FROM produtos_tbl p
    LEFT JOIN produtos_config_tbl cfg ON cfg.id_produto = p.id_produto
    """
    try:
        cur.execute(sql)
        rows = cur.fetchall()
    except Exception as e:
        # fallback: se nao existir produtos_config_tbl, tenta só produtos_tbl e usa defaults
        # Ajuste se sua tabela produtos_tbl já tem alguns campos (ex: lead_time)
        cur.close()
        cur = conn.cursor()
        cur.execute("SELECT id_produto, nome FROM produtos_tbl")
        rows = cur.fetchall()
        # transformar rows simples em formato padrao:
        transformed = []
        for r in rows:
            pid = int(r[0])
            name = r[1] if len(r) > 1 else None
            transformed.append((
                pid, name, 7, 0, 100, 0, 1, 1
            ))
        rows = transformed

    configs = []
    for r in rows:
        # Se row vier do primeiro SELECT: (id_produto, nome, lead_time_days, min_qt, max_qt, safety_stock, reorder_multiple, enabled)
        # Se row vier do fallback, já está no mesmo formato.
        pid = int(r[0])
        nome = r[1]
        lead_time_days = int(r[2])
        min_qt = int(r[3])
        max_qt = int(r[4])
        safety_stock = int(r[5])
        reorder_multiple = int(r[6]) if r[6] is not None else None
        enabled = bool(int(r[7]))
        cfg = ProductConfig(
            id_produto=pid,
            nome=nome,
            lead_time_days=lead_time_days,
            min_qt=min_qt,
            max_qt=max_qt,
            safety_stock=safety_stock,
            reorder_multiple=reorder_multiple,
            enabled=enabled
        )
        configs.append(cfg)

    cur.close()
    return configs
