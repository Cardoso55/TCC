# arquivo: /python/test_replenishment.py
import utils.db
from python.replenishment_manager import ReplenishmentManager
from config_loader import load_products_from_db
from forecast_wrapper import forecast_wrapper

manager = ReplenishmentManager(
    get_connection=utils.db.get_connection,
    forecast_provider=forecast_wrapper,
    products_config_loader=load_products_from_db,
    insert_target="pedidoreposicao_tbl",
    dry_run=True,  # IMPORTANT: start safe
)

decisions = manager.run_replenishment(horizon="daily", created_by=1)
print("Decisions count:", len(decisions))
for d in decisions:
    print(f"Produto {d.id_produto}: estoque {d.estoque_atual}, incoming {d.incoming}, previsao {d.forecast:.2f} -> pedir {d.qty_to_order} chegada {d.data_prevista_chegada.date()}")
