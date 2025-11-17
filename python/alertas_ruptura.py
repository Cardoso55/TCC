# alertas_ruptura.py
import math
import numpy as np
import pandas as pd
from sqlalchemy import text

def calcular_media_diaria(vendas_df, dias_anteriores=30):
    """
    Recebe vendas_df com colunas: 'codigo_produto','quantidade_vendida','data_venda' (ou 'data').
    Retorna DataFrame: codigo_produto, media_diaria (float)
    """
    df = vendas_df.copy()
    # tenta detectar coluna de data
    if 'data_venda' in df.columns:
        date_col = 'data_venda'
    elif 'data' in df.columns:
        date_col = 'data'
    else:
        raise ValueError("Data column not found (esperado 'data_venda' ou 'data').")
    df[date_col] = pd.to_datetime(df[date_col], errors='coerce')
    if df[date_col].isnull().all():
        raise ValueError("Todas as datas inválidas no CSV.")
    ultima = df[date_col].max()
    inicio = ultima - pd.Timedelta(days=dias_anteriores - 1)
    janela = df[df[date_col] >= inicio]
    # agrupa e soma vendas na janela
    soma = janela.groupby('codigo_produto', as_index=False)['quantidade_vendida'].sum().rename(columns={'quantidade_vendida':'sum_window'})
    soma['media_diaria'] = soma['sum_window'] / dias_anteriores
    return soma[['codigo_produto','media_diaria']]

def calcular_dias_ate_acabar(estoque_df, medias_df, fallback_days=30):
    """
    estoque_df deve ter colunas: 'id_estoque', 'codigo_produto', 'quantidade_atual'
    medias_df: codigo_produto, media_diaria
    Retorna DataFrame com: codigo_produto, quantidade_atual, media_diaria, dias_ate_acabar (float/inf), risco
    """
    df = estoque_df.copy()
    if 'codigo_produto' not in df.columns:
        # tenta outros nomes comuns
        if 'codigo' in df.columns:
            df = df.rename(columns={'codigo':'codigo_produto'})
        else:
            raise ValueError("estoque_df precisa conter coluna 'codigo_produto'.")

    merged = df.merge(medias_df, on='codigo_produto', how='left')
    # preenche médias faltantes com 0
    merged['media_diaria'] = merged['media_diaria'].fillna(0.0)

    # calcula dias até acabar
    def days_func(qty, avg):
        try:
            qty = float(qty)
            avg = float(avg)
        except:
            return float('inf')
        if avg <= 0:
            return float('inf')
        return qty / avg

    merged['dias_ate_acabar'] = merged.apply(lambda r: days_func(r.get('quantidade_atual', 0), r['media_diaria']), axis=1)

    # classifica risco
    def classificar(dias):
        if np.isinf(dias):
            return 'Seguro'
        if dias <= 3:
            return 'Crítico'
        if dias <= 14:
            return 'Atenção'
        return 'Seguro'

    merged['risco'] = merged['dias_ate_acabar'].apply(classificar)

    # arredonda dias para inteiro (mantém inf)
    merged['dias_ate_acabar'] = merged['dias_ate_acabar'].apply(lambda x: int(math.ceil(x)) if not np.isinf(x) else None)

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
            # não gerar alerta para 'Seguro' por padrão (evita spam)
            continue
        alertas.append({
            'id_estoque': int(r['id_estoque']),
            'codigo_produto': str(r['codigo_produto']),
            'nivel': nivel,
            'mensagem': mensagem,
            'dias_ate_acabar': (r['dias_ate_acabar'] if r['dias_ate_acabar'] is not None else -1)
        })
    return alertas

def salvar_alertas_no_db(engine, alertas, tabela='alertas_tbl'):
    """
    Salva alertas na tabela já existente 'alertas_tbl'.
    Campos existentes:
      tipo, mensagem, nivel_propriedade, enviado_para, status, data_criacao,
      idUsuario_TBL, idProdutos_TBL
    """
    if not alertas:
        return

    insert_sql = text(f"""
        INSERT INTO {tabela} 
        (tipo, mensagem, nivel_propriedade, enviado_para, status, idUsuario_TBL, idProdutos_TBL)
        VALUES 
        (:tipo, :mensagem, :nivel_propriedade, NULL, 'aberto', :idUsuario_TBL, :idProdutos_TBL)
    """)

    try:
        with engine.begin() as conn:
            for a in alertas:
                conn.execute(insert_sql, {
                    "tipo": "ruptura",
                    "mensagem": a["mensagem"],
                    "nivel_propriedade": a["nivel"],  # critico / atencao
                    "idUsuario_TBL": 1,               # ou usuário logado
                    "idProdutos_TBL": a["id_produto"] # precisa vir no alerta
                })
    except Exception as e:
        print(f"[ERRO] Falha ao salvar alerta: {e}")

