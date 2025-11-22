import utils.db
from replenishment_manager import ReplenishmentManagerPRO, ProductConfig
from config_loader import load_products_from_db
from forecast_wrapper import forecast_wrapper

manager = ReplenishmentManagerPRO(
    get_connection=utils.db.get_connection,
    forecast_provider=forecast_wrapper,
    products_config_loader=load_products_from_db,
    insert_target="pedidosreposicao_tbl",
    dry_run=True  # TESTE seguro
)

decisions = manager.run_replenishment(tipo_previsao="diaria", created_by=1)

print("\nResumo PRO das decisões:")
for d in decisions:
    print(f"{d.nome} (ID {d.id_produto}) → Pedido sugerido: {d.qty_to_order}, Chegada: {d.data_prevista_chegada.date()}")
