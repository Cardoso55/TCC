# python/niveis_estoque.py
import pandas as pd

def calcular_estoque_minimo(df_vendas, tempo_reposicao_dias=7, margem_seguranca=0.2):
    """
    Calcula o estoque mínimo ideal de cada produto com base nas vendas.
    """

    # Garante que as colunas esperadas existem
    colunas_necessarias = {'produto', 'quantidade', 'data'}
    if not colunas_necessarias.issubset(df_vendas.columns):
        print("⚠️ O DataFrame não contém as colunas necessárias:", colunas_necessarias)
        return pd.DataFrame()

    # Agrupa vendas por produto e por dia
    df_vendas['data'] = pd.to_datetime(df_vendas['data'])
    vendas_diarias = df_vendas.groupby(['produto', 'data'])['quantidade'].sum().reset_index()

    # Calcula média diária de vendas por produto
    media_diaria = vendas_diarias.groupby('produto')['quantidade'].mean().reset_index()
    media_diaria.rename(columns={'quantidade': 'media_vendas_diaria'}, inplace=True)

    # Calcula estoque mínimo
    media_diaria['estoque_minimo'] = (
        media_diaria['media_vendas_diaria'] * tempo_reposicao_dias
    ) * (1 + margem_seguranca)

    # Arredonda os valores
    media_diaria['estoque_minimo'] = media_diaria['estoque_minimo'].round(0).astype(int)

    return media_diaria
