# ia_main.py
import pandas as pd
from datetime import datetime, timedelta
from utils.db import get_connection
from utils.estoque_db import carregar_estoque_produtos
from alertas_ruptura import (
    calcular_media_diaria,
    calcular_dias_ate_acabar,
    gerar_alertas_de_ruptura,
    salvar_alertas_no_db
)
import warnings
warnings.filterwarnings("ignore")


# ===== CONFIGURAÇÃO =====
DIAS_AVISO_VALIDADE = 7  # alerta se faltar <= 7 dias para vencer
DIAS_AVISO_ESTOQUE = 3   # alerta de estoque baixo se quantidade atual <= mínima

# ----------------------
# Carregar vendas do banco
# ----------------------
def carregar_vendas(engine, dias_anteriores=None):
    query = "SELECT id_venda, id_produto, quantidade, preco_unitario, data_venda FROM vendas_tbl"
    df = pd.read_sql(query, engine, parse_dates=["data_venda"])
    if dias_anteriores:
        data_limite = pd.Timestamp.now() - pd.Timedelta(days=dias_anteriores)
        df = df[df["data_venda"] >= data_limite]
    return df

# ----------------------
# Alertas de produtos vencidos ou próximos da validade
# ----------------------
def gerar_alertas_validade(engine):
    produtos_df = pd.read_sql(
        "SELECT id_produto, nome, validade FROM produtos_tbl",
        engine,
        parse_dates=["validade"]
    )
    hoje = pd.Timestamp.now().normalize()
    alertas = []

    for _, r in produtos_df.iterrows():
        if pd.isna(r['validade']):
            continue
        dias_restantes = (r['validade'].normalize() - hoje).days
        nome_produto = r['nome'] if pd.notna(r['nome']) else "Produto desconhecido"
        if dias_restantes < 0:
            alertas.append({
                'id_estoque': -1,
                'id_produto': int(r['id_produto']),
                'nome_produto': nome_produto,
                'nivel': 'critico',
                'mensagem': f"Produto vencido: {nome_produto} — validade {r['validade'].date()}",
                'dias_ate_acabar': -1
            })
        elif dias_restantes <= DIAS_AVISO_VALIDADE:
            alertas.append({
                'id_estoque': -1,
                'id_produto': int(r['id_produto']),
                'nome_produto': nome_produto,
                'nivel': 'atenção',
                'mensagem': f"Produto próximo da validade: {nome_produto} — vence em {dias_restantes} dias",
                'dias_ate_acabar': dias_restantes
            })
    return alertas

# ----------------------
# Alertas de estoque baixo
# ----------------------
def gerar_alertas_estoque_baixo(estoque_df, engine):
    # Carrega id_produto, codigo e nome dos produtos
    produtos_df = pd.read_sql("SELECT id_produto, codigo_produto, nome FROM produtos_tbl", engine)

    # dicionário: id_produto → nome
    id_para_nome = pd.Series(
        produtos_df.nome.values,
        index=produtos_df.id_produto
    ).to_dict()

    alertas = []
    for _, row in estoque_df.iterrows():

        id_produto = int(row['idProdutos_TBL'])   # FK CERTA DO ESTOQUE

        nome_produto = id_para_nome.get(id_produto, f"Produto desconhecido ({id_produto})")

        if row['quantidade_atual'] <= row['quantidade_minima']:
            alertas.append({
                'id_estoque': row['id_estoque'],
                'id_produto': id_produto,     # agora é o ID real
                'nome_produto': nome_produto,
                'nivel': 'atenção',
                'mensagem': f"Estoque baixo: {nome_produto} — atual {row['quantidade_atual']}, mínimo {row['quantidade_minima']}",
                'dias_ate_acabar': row['quantidade_atual']
            })

    return alertas



# ----------------------
# Recomendações baseadas nos alertas (corrigido)
# ----------------------
def gerar_recomendacoes_alertas(alertas):
    recomendacoes = []

    for a in alertas:
        msg = a['mensagem'].lower()
        codigo = a['id_produto']
        nome_produto = a.get('nome_produto', f"Produto desconhecido ({codigo})")  # <-- sempre pega do alerta

        if "próximo da validade" in msg:
            recomendacoes.append({'id_produto': codigo, 'nome_produto': nome_produto, 'recomendacao': "Faça uma promoção relâmpago para vender rápido"})
            recomendacoes.append({'id_produto': codigo, 'nome_produto': nome_produto, 'recomendacao': "Remanejar para áreas com mais saída"})
        elif "vencido" in msg:
            recomendacoes.append({'id_produto': codigo, 'nome_produto': nome_produto, 'recomendacao': "Produto vencido, remover do estoque e notificar responsável"})
        elif "estoque baixo" in msg:
            recomendacoes.append({'id_produto': codigo, 'nome_produto': nome_produto, 'recomendacao': "Estoque crítico, priorizar venda e reforçar pedido de reposição"})
            recomendacoes.append({'id_produto': codigo, 'nome_produto': nome_produto, 'recomendacao': "Avisar equipe de compras e avaliar fornecedor alternativo"})
        elif "ruptura" in msg:
            recomendacoes.append({'id_produto': codigo, 'nome_produto': nome_produto, 'recomendacao': "Risco de ruptura! Antecipe pedido e acompanhe lead time do fornecedor"})

    return recomendacoes

# ----------------------
# Salvar recomendações no banco
# ----------------------

def salvar_recomendacoes_no_db(engine, recomendacoes):
    if not recomendacoes:
        return

    # Converte datas para string MySQL
    agora = datetime.now().strftime('%Y-%m-%d %H:%M:%S')

    conn = engine  # seu get_connection() retorna um MySQL connection
    cursor = conn.cursor()

    sql = """
        INSERT INTO ia_recomendacoes_tbl (codigo_produto, nome_produto, recomendacao, criado_em)
        VALUES (%s, %s, %s, %s)
    """

    for r in recomendacoes:
        codigo = r.get('id_produto')
        nome = r.get('nome_produto', f"Produto ({codigo})")
        rec = r.get('recomendacao', '')
        cursor.execute(sql, (codigo, nome, rec, agora))

    conn.commit()
    cursor.close()


# ----------------------
# Executar alertas e recomendações
# ----------------------
def executar_alertas():
    engine = get_connection()
    # Carregar mapa id → nome
    produtos_df = pd.read_sql("SELECT id_produto, nome FROM produtos_tbl", engine)
    nomes_produtos = dict(zip(produtos_df.id_produto, produtos_df.nome))


    # 1️⃣ Carrega vendas últimos 6 meses
    df_vendas = carregar_vendas(engine, dias_anteriores=180)

    # 2️⃣ Calcula média diária por produto
    medias = calcular_media_diaria(df_vendas, dias_anteriores=30) if not df_vendas.empty else pd.DataFrame(columns=['id_produto','media_diaria'])

    # 3️⃣ Carrega estoque atual
    estoque_df = carregar_estoque_produtos(engine)
    if estoque_df.empty:
        print("Estoque vazio. Nenhum alerta gerado.")
        return

    # 4️⃣ Calcula dias até acabar e risco
    ruptura_df = calcular_dias_ate_acabar(estoque_df, medias, fallback_days=30)

    # 5️⃣ Gera alertas de estoque por previsão de ruptura
    alertas_ruptura = gerar_alertas_de_ruptura(ruptura_df, nomes_produtos)


    # 6️⃣ Gera alertas de produtos vencidos/próximos da validade
    alertas_validade = gerar_alertas_validade(engine)

    # 7️⃣ Gera alertas de estoque baixo
    alertas_estoque_baixo = gerar_alertas_estoque_baixo(estoque_df, engine)  # <-- PASSA O ENGINE AQUI

    # 8️⃣ Junta todos os alertas
    alertas = alertas_ruptura + alertas_validade + alertas_estoque_baixo

    # 9️⃣ Salva alertas no banco
    salvar_alertas_no_db(engine, alertas)

    # 🔟 Mostra alertas
    print(f"Alertas gerados: {len(alertas)}")
    for a in alertas:
        print(f"{a['nivel'].upper()}: {a['mensagem']}")

    # 1️⃣1️⃣ Gera recomendações inteligentes
    recomendacoes = gerar_recomendacoes_alertas(alertas)

    # Mostra no console
    print("\n=== RECOMENDAÇÕES DA IA ===")
    for r in recomendacoes:
        print(f"{r['id_produto']}: {r['recomendacao']}")

    # Salva direto no banco igual os alertas
    salvar_recomendacoes_no_db(engine, recomendacoes)


# ----------------------
# Main
# ----------------------
if __name__ == "__main__":
    executar_alertas()
