import pandas as pd
from db import get_connection

def extract_sales_data():
    conn = get_connection()

    query = """
    SELECT 
        v.id_venda,
        v.id_produto,
        p.nome AS nome_produto,
        p.categoria,
        v.quantidade,
        v.preco_unitario,
        v.valor_total,
        v.canal_venda,
        v.data_venda,
        e.quantidade_atual,
        e.quantidade_minima,
        e.quantidade_maxima
    FROM vendas_tbl v
    JOIN produtos_tbl p ON p.id_produto = v.id_produto
    JOIN estoque_tbl e ON e.idProdutos_TBL = v.id_produto
    ORDER BY v.data_venda ASC;
    """

    df = pd.read_sql(query, conn)
    conn.close()

    output_path = "data/vendas_dataset.csv"
    df.to_csv(output_path, index=False, encoding="utf-8")

    print(f"[OK] Dataset criado em: {output_path}")
    print(df.head())

if __name__ == "__main__":
    extract_sales_data()
