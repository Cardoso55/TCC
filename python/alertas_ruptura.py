# alertas_ruptura.py
import math
import numpy as np
import pandas as pd

def calcular_media_diaria(vendas_df, dias_anteriores=30):
    """
    Recebe vendas_df com colunas: 'id_produto','quantidade','data_venda'.
    Retorna DataFrame: id_produto, media_diaria (float)
    """
    df = vendas_df.copy()
    df['data_venda'] = pd.to_datetime(df['data_venda'], errors='coerce')
    ultima = df['data_venda'].max()
    inicio = ultima - pd.Timedelta(days=dias_anteriores - 1)
    janela = df[df['data_venda'] >= inicio]

    if janela.empty:
        return pd.DataFrame(columns=['id_produto','media_diaria'])

    soma = janela.groupby('id_produto', as_index=False)['quantidade'].sum().rename(columns={'quantidade':'sum_window'})
    soma['media_diaria'] = soma['sum_window'] / dias_anteriores
    return soma[['id_produto','media_diaria']]

def calcular_dias_ate_acabar(estoque_df, medias_df, fallback_days=30):
    """
    estoque_df deve ter colunas: 'id_estoque', 'codigo_produto', 'quantidade_atual'
    medias_df: id_produto, media_diaria
    Retorna DataFrame com: id_estoque, codigo_produto, quantidade_atual, media_diaria, dias_ate_acabar, risco
    """
    df = estoque_df.copy()
    
    # Força tipos iguais pra evitar erros de merge
    df['codigo_produto'] = df['codigo_produto'].astype(str)
    medias_df['id_produto'] = medias_df['id_produto'].astype(str)

    # merge
    merged = df.merge(medias_df, left_on='codigo_produto', right_on='id_produto', how='left')
    merged['media_diaria'] = merged['media_diaria'].fillna(0.0)

    def days_func(qty, avg):
        try:
            qty = float(qty)
            avg = float(avg)
        except:
            return fallback_days
        if avg <= 0:
            return fallback_days
        return qty / avg

    merged['dias_ate_acabar'] = merged.apply(lambda r: math.ceil(days_func(r.get('quantidade_atual', 0), r['media_diaria'])), axis=1)

    def classificar(dias):
        if dias <= 3:
            return 'Crítico'
        if dias <= 14:
            return 'Atenção'
        return 'Seguro'

    merged['risco'] = merged['dias_ate_acabar'].apply(classificar)

    return merged[['id_estoque','codigo_produto','quantidade_atual','media_diaria','dias_ate_acabar','risco']]

def gerar_alertas_de_ruptura(ruptura_df):
    """
    Recebe DataFrame resultado de calcular_dias_ate_acabar e retorna lista de dicts (alertas)
    """
    alertas = []
    for _, r in ruptura_df.iterrows():
        if r['risco'] == 'Crítico':
            nivel = 'critico'
            mensagem = f"Estoque crítico: {r['codigo_produto']} — estoque atual {r['quantidade_atual']}, deve acabar em ~{r['dias_ate_acabar']} dias."
        elif r['risco'] == 'Atenção':
            nivel = 'atencao'
            mensagem = f"Atenção: {r['codigo_produto']} — estoque atual {r['quantidade_atual']}, deve acabar em ~{r['dias_ate_acabar']} dias."
        else:
            # Seguro não gera alerta
            continue
        alertas.append({
            'id_estoque': int(r['id_estoque']),
            'codigo_produto': str(r['codigo_produto']),
            'nivel': nivel,
            'mensagem': mensagem,
            'dias_ate_acabar': r['dias_ate_acabar']
        })
    return alertas

def salvar_alertas_no_db(engine, alertas, tabela='alertas_tbl'):
    """
    Salva alertas na tabela já existente 'alertas_tbl'.
    Campos existentes:
      tipo, mensagem, nivel_prioridade, enviado_para, status, data_criacao,
      idUsuario_TBL, idProdutos_TBL
    """
    if not alertas:
        return

    insert_sql = """
        INSERT INTO {tabela} 
        (tipo, mensagem, nivel_prioridade, enviado_para, status, idUsuario_TBL, idProdutos_TBL)
        VALUES (%s, %s, %s, NULL, 'pendente', %s, %s)
    """.format(tabela=tabela)

    try:
        conn = engine
        cursor = conn.cursor()
        for a in alertas:
            cursor.execute(insert_sql, (
                "ruptura",
                a["mensagem"],
                a["nivel"],
                13,               # idUsuario_TBL
                a["codigo_produto"]  # idProdutos_TBL
            ))
        conn.commit()
        cursor.close()
    except Exception as e:
        print(f"[ERRO] Falha ao salvar alerta: {e}")
