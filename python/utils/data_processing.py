# python/utils/data_processing.py
import pandas as pd

def carregar_dados_vendas(caminho_csv):
    """
    Lê e processa o arquivo CSV de vendas.
    Retorna um DataFrame limpo e padronizado.
    """

    try:
        df = pd.read_csv(caminho_csv)

        # Normaliza os nomes das colunas
        df.columns = df.columns.str.strip().str.lower()

        # Exemplo de colunas esperadas: 'produto', 'quantidade', 'preco', 'data'
        if 'data' in df.columns:
            df['data'] = pd.to_datetime(df['data'], errors='coerce')

        if 'preco' in df.columns and 'quantidade' in df.columns:
            df['total_venda'] = df['preco'] * df['quantidade']

        # Remove linhas com dados ausentes importantes
        df = df.dropna(subset=['produto', 'quantidade', 'preco'])

        return df

    except Exception as e:
        print(f"Erro ao carregar o CSV: {e}")
        return pd.DataFrame()
