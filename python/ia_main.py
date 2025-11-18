import json
import os
import sys
import pandas as pd
import numpy as np
from sklearn.linear_model import LinearRegression
from utils.estoque_db import carregar_estoque_produtos, atualizar_estoque_minimo
from alertas_ruptura import calcular_media_diaria, calcular_dias_ate_acabar, gerar_alertas_de_ruptura, salvar_alertas_no_db

# ----------------------
# Função para caminhos relativos no exe
# ----------------------
def caminho_relativo(*args):
    """Retorna caminho absoluto relativo ao script ou exe."""
    try:
        # Se for exe gerado pelo PyInstaller
        base_path = sys._MEIPASS
    except AttributeError:
        # Se for rodando como script Python
        base_path = os.path.dirname(os.path.abspath(__file__))
    return os.path.join(base_path, *args)

# ----------------------
# Previsões usando regressão linear
# ----------------------
def gerar_previsoes(df_vendas, dias_futuros=30):
    previsoes = {}
    df_grouped = df_vendas.groupby(["codigo_produto", "data_venda"]).agg({
        "quantidade_vendida": "sum",
        "nome": "first",
        "preco_unitario": "first"
    }).reset_index()

    for codigo, grupo in df_grouped.groupby("codigo_produto"):
        grupo = grupo.sort_values("data_venda")
        grupo["dias"] = (grupo["data_venda"] - grupo["data_venda"].min()).dt.days

        X = grupo["dias"].values.reshape(-1, 1)
        y = grupo["quantidade_vendida"].values

        if len(X) < 2:
            previsao_total = max(0, int(np.round(y.sum())))
        else:
            modelo = LinearRegression()
            modelo.fit(X, y)
            dias_para_prever = np.array(range(X.max()+1, X.max()+dias_futuros+1)).reshape(-1, 1)
            previsao = modelo.predict(dias_para_prever)
            previsao_total = int(np.round(np.sum(np.maximum(previsao, 0))))

        ultimo = grupo.iloc[-1]
        previsoes[codigo] = {
            "nome": ultimo["nome"],
            "preco_unitario": float(ultimo["preco_unitario"]),
            "previsao": previsao_total
        }

    return previsoes

# ----------------------
# Salva previsões em JSON
# ----------------------
def salvar_previsoes_json(previsoes):
    caminho_json = caminho_relativo("previsoes_vendas.json")
    with open(caminho_json, "w", encoding="utf-8") as f:
        json.dump(previsoes, f, ensure_ascii=False, indent=4)
    print(f"JSON de previsões atualizado: {caminho_json}")

# ----------------------
# CSV mais recente
# ----------------------
def obter_csv_mais_recente():
    pasta_vendas = caminho_relativo("data", "vendas")
    arquivos = [os.path.join(pasta_vendas, f) for f in os.listdir(pasta_vendas) if f.endswith(".csv")]
    if not arquivos:
        print("Nenhum arquivo CSV encontrado na pasta de vendas.")
        return None
    arquivo_recente = max(arquivos, key=os.path.getmtime)
    print(f"CSV selecionado: {arquivo_recente}")
    return arquivo_recente

# ----------------------
# Estoque mínimo
# ----------------------
def calcular_estoque_minimo(caminho_csv, engine):
    df = pd.read_csv(caminho_csv, parse_dates=["data_venda"])
    if "codigo_produto" not in df.columns or "quantidade_vendida" not in df.columns:
        print("CSV inválido. Deve conter 'codigo_produto' e 'quantidade_vendida'.")
        return

    df_grouped = df.groupby("codigo_produto")["quantidade_vendida"].sum().reset_index()
    df_grouped["quantidade_minima_sugerida"] = (df_grouped["quantidade_vendida"] * 3).astype(int)

    print("Médias calculadas:")
    print(df_grouped)

    estoque_df = carregar_estoque_produtos(engine)
    for _, row in df_grouped.iterrows():
        cod = row["codigo_produto"]
        minimo = int(row["quantidade_minima_sugerida"])
        if estoque_df.empty:
            continue
        match = estoque_df[estoque_df["codigo_produto"] == cod]
        if not match.empty:
            id_estoque = int(match.iloc[0]["id_estoque"])
            atualizar_estoque_minimo(engine, id_estoque, minimo)
            print(f"Atualizado: Produto {cod} | mínimo = {minimo}")
        else:
            print(f"Produto {cod} não existe no banco.")

# ----------------------
# Recomendar por sazonalidade
# ----------------------           
def recomendar_por_sazonalidade(df_vendas, mes_atual):
    df_vendas['data_venda'] = pd.to_datetime(df_vendas['data_venda'])
    df_vendas['mes'] = df_vendas['data_venda'].dt.month

    saz = df_vendas.groupby(['codigo_produto', 'nome', 'mes'])['quantidade_vendida'].sum().reset_index()
    saz['percentual'] = saz.groupby('codigo_produto')['quantidade_vendida'].transform(lambda x: x / x.sum())

    recomendados = saz[(saz['mes'] == mes_atual) & (saz['percentual'] > 0.2)]
    recomendados = recomendados[['codigo_produto', 'nome', 'percentual']].sort_values('percentual', ascending=False)

    return recomendados

# ----------------------
# Salvar sazonalidade em JSON
# ----------------------  
def salvar_sazonalidade_json(recomendados, df_vendas):
    cod_to_nome = df_vendas.set_index("codigo_produto")["nome"].to_dict()
    lista_produtos = [{"nome": cod_to_nome.get(row["codigo_produto"], row["codigo_produto"])}
                      for _, row in recomendados.iterrows()]
    caminho_json = caminho_relativo("previsoes_sazonais.json")
    with open(caminho_json, "w", encoding="utf-8") as f:
        json.dump(lista_produtos, f, ensure_ascii=False, indent=4)
    print(f"JSON de previsões sazonais atualizado: {caminho_json}")

# ----------------------
# Gerar mais procurados
# ----------------------  
def gerar_mais_procurados(df_vendas, mes_atual):
    df_vendas['data_venda'] = pd.to_datetime(df_vendas['data_venda'])
    df_vendas['mes'] = df_vendas['data_venda'].dt.month

    vendas_mes = df_vendas.groupby(['codigo_produto', 'nome', 'mes'])['quantidade_vendida'].sum().reset_index()
    atual = vendas_mes[vendas_mes['mes'] == mes_atual].copy()
    anterior = vendas_mes[vendas_mes['mes'] == (mes_atual-1)].copy()
    
    merged = atual.merge(
        anterior[['codigo_produto','quantidade_vendida']],
        on='codigo_produto', how='left',
        suffixes=('_atual','_anterior')
    )
    merged['quantidade_vendida_anterior'].fillna(0, inplace=True)
    merged['variacao'] = ((merged['quantidade_vendida_atual'] - merged['quantidade_vendida_anterior']) /
                          merged['quantidade_vendida_anterior'].replace(0,1) * 100).round(0).astype(int)
    top3 = merged.sort_values('quantidade_vendida_atual', ascending=False).head(3)
    
    return [{'nome': row['nome'], 'variacao': row['variacao']} for _, row in top3.iterrows()]

# ----------------------
# Salvar mais procurados em JSON
# ----------------------  
def salvar_mais_procurados_json(lista_produtos):
    caminho_json = caminho_relativo("mais_procurados.json")
    with open(caminho_json, "w", encoding="utf-8") as f:
        json.dump(lista_produtos, f, ensure_ascii=False, indent=4)
    print(f"JSON de produtos mais procurados atualizado: {caminho_json}")

# ----------------------
# Main
# ----------------------
def main():
    from utils.db_connector import conectar_engine
    engine = conectar_engine()

    caminho_csv = obter_csv_mais_recente()
    if not caminho_csv:
        return

    df_vendas = pd.read_csv(caminho_csv, parse_dates=["data_venda"])
    medias = calcular_media_diaria(df_vendas, dias_anteriores=30)
    estoque_df = carregar_estoque_produtos(engine)
    ruptura_df = calcular_dias_ate_acabar(estoque_df, medias, fallback_days=30)
    alertas = gerar_alertas_de_ruptura(ruptura_df)
    salvar_alertas_no_db(engine, alertas)
    print("Alertas de ruptura gerados:", alertas)

    calcular_estoque_minimo(caminho_csv, engine)
    previsoes = gerar_previsoes(df_vendas)
    salvar_previsoes_json(previsoes)

    recomendados = recomendar_por_sazonalidade(df_vendas, mes_atual=pd.Timestamp.now().month)
    salvar_sazonalidade_json(recomendados, df_vendas)

    mes_atual = pd.Timestamp.now().month
    mais_procurados = gerar_mais_procurados(df_vendas, mes_atual)
    salvar_mais_procurados_json(mais_procurados)

if __name__ == "__main__":
    main()
