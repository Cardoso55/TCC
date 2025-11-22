import os
import json
import sys
from datetime import datetime
from replenishment_manager import ReplenishmentManagerPRO, ProductConfig
from utils.db import get_connection as conectarBanco
from forecast_wrapper import forecast_wrapper
from config_loader import load_products_from_db


def validar_usuario_no_banco(user_id):
    conn = conectarBanco()
    cur = conn.cursor()

    cur.execute("SELECT COUNT(*) FROM usuarios_tbl WHERE id_usuario = %s", (user_id,))
    count = cur.fetchone()[0]

    cur.close()
    conn.close()

    return count > 0


# ---------------------------------------------------------
# 1) OBRIGA que o script tenha user_id
# ---------------------------------------------------------
user_env = os.environ.get("USER_ID")

if not user_env:
    print("\n❌ ERRO: USER_ID não foi definido!")
    print("➡ Execute assim pelo PHP ou via terminal:")
    print("   USER_ID=6 python export_replenishment_json.py\n")
    sys.exit(1)

try:
    created_by = int(user_env)
except ValueError:
    print("❌ ERRO: USER_ID precisa ser um número inteiro!")
    sys.exit(1)

# ---------------------------------------------------------
# 2) VALIDA SE O USUÁRIO EXISTE NO BANCO
# ---------------------------------------------------------
if not validar_usuario_no_banco(created_by):
    print(f"\n❌ ERRO: O usuário com ID {created_by} não existe no banco!")
    print(f"➡ IDs válidos: 4, 6, 9, 10, 11, 12\n")
    sys.exit(1)

print(f"[IA PRO] Registrando decisões com created_by = {created_by}")


# instanciar o manager (exemplo, ajusta conforme seu código)
manager = ReplenishmentManagerPRO(
    get_connection=lambda: conectarBanco(),
    forecast_provider=forecast_wrapper,
    products_config_loader=load_products_from_db,
    dry_run=False
)

# Pega o user_id enviado pelo PHP
created_by = int(os.environ.get("USER_ID", 1))  # fallback 1 só se não vier nada

print(f"[IA PRO] Registrando decisões com created_by = {created_by}")

# Executa a reposição
decisions = manager.run_replenishment(
    tipo_previsao="semanal",
    created_by=created_by
)

print("Decisões exportadas com sucesso!")

saida_json = []
for d in decisions:
    saida_json.append({
        "id_produto": d.id_produto,
        "nome": d.nome,
        "estoque_atual": d.estoque_atual,
        "incoming": d.incoming,
        "forecast": d.forecast,
        "vendas_historicas": d.vendas_historicas,
        "demanda_ajustada": d.demanda_ajustada,
        "qty_to_order": d.qty_to_order,
        "data_prevista_chegada": d.data_prevista_chegada.strftime("%Y-%m-%d %H:%M:%S")
        # removido 'tipo_previsao'
    })

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
OUTPUT_FILE = os.path.join(BASE_DIR, "previsoes_vendas.json")

with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
    json.dump(saida_json, f, ensure_ascii=False, indent=2)

print(f"[IA REPOSIÇÃO PRO] JSON gerado com {len(decisions)} decisões.")
