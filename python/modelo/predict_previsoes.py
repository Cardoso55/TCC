# python/modelo/predict_previsoes.py
import sys
import os

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
sys.path.append(ROOT)

import pandas as pd
import numpy as np
from utils_predict import (
    limpar_previsoes,
    get_historico_vendas,
    get_produtos,
    preparar_features,
    inserir_previsao
)
from datetime import datetime, timedelta
import calendar

# ============================
# LIMPAR PREVISÕES ANTIGAS
# ============================
print("Limpando previsões antigas...")
limpar_previsoes()

# ============================
# CARREGAR HISTÓRICO DE VENDAS
# ============================
print("Carregando histórico de vendas e produtos...")
df_vendas = get_historico_vendas()
produtos = get_produtos()

if df_vendas.empty or not produtos:
    print("Sem dados suficientes para gerar previsões.")
    exit()

# ============================
# FUNÇÃO AUXILIAR: PRÓXIMA DATA
# ============================
def proxima_data(tipo):
    hoje = datetime.today()
    if tipo == "diario":
        return hoje + timedelta(days=1)
    elif tipo == "semanal":
        # próxima segunda-feira
        dias_ate_segunda = (0 - hoje.weekday() + 7) % 7
        return hoje + timedelta(days=dias_ate_segunda or 7)
    elif tipo == "mensal":
        # primeiro dia do próximo mês
        mes = hoje.month + 1 if hoje.month < 12 else 1
        ano = hoje.year + 1 if mes == 1 else hoje.year
        return datetime(ano, mes, 1)

# ============================
# GERAR PREVISÕES POR TIPO
# ============================
tipos = ["diario", "semanal", "mensal"]

for tipo in tipos:
    print(f"Gerando previsões: {tipo}")

    # Preparar features (última linha de cada produto)
    df_features = preparar_features(df_vendas)

    # Calcular previsão com ruído realista
    if tipo == "diario":
        df_features["previsao_quantidade"] = df_features["quantidade"] * (1 + np.random.uniform(-0.1, 0.1, len(df_features)))
    elif tipo == "semanal":
        df_features["previsao_quantidade"] = df_features["media_7"] * 7 * (1 + np.random.uniform(-0.15, 0.15, len(df_features)))
    elif tipo == "mensal":
        df_features["previsao_quantidade"] = df_features["media_30"] * 30 * (1 + np.random.uniform(-0.2, 0.2, len(df_features)))

    # Garantir valores inteiros e >=0
    df_features["previsao_quantidade"] = df_features["previsao_quantidade"].round().clip(lower=0)

    # Definir a data correta da previsão
    df_features["data_previsao"] = proxima_data(tipo)

    # Inserir previsão no banco
    inserir_previsao(df_features, tipo_previsao=tipo)

print("Todas as previsões master geradas com sucesso!")
