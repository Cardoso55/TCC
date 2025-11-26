# arquivo: /python/replenishment_manager_pro.py
from dataclasses import dataclass
from datetime import datetime, timedelta
from typing import List, Callable, Tuple
import utils.db

@dataclass
class ProductConfig:
    id_produto: int
    nome: str
    lead_time_days: int = 7
    min_qt: int = 0
    max_qt: int = 100
    safety_stock: int = 0
    reorder_multiple: int = 1
    enabled: bool = True

@dataclass
class ReplenishmentDecision:
    id_produto: int
    nome: str
    estoque_atual: int
    incoming: int
    forecast: float
    vendas_historicas: int
    demanda_ajustada: int
    qty_to_order: int
    data_prevista_chegada: datetime

class ReplenishmentManagerPRO:
    def __init__(
        self,
        get_connection: Callable,
        forecast_provider: Callable,  # (product_id, tipo_previsao) -> float
        products_config_loader: Callable,  # carrega configs dos produtos
        insert_target: str = "pedidosreposicao_tbl",
        dry_run: bool = False
    ):
        self.get_connection = get_connection
        self.forecast_provider = forecast_provider
        self.products_config_loader = products_config_loader
        self.insert_target = insert_target
        self.dry_run = dry_run

    # ---------------------- Estoque Atual --------------------------
    def get_current_stock(self, conn, product_id: int):
        cur = conn.cursor()
        cur.execute(
            "SELECT quantidade_atual, quantidade_minima, quantidade_maxima "
            "FROM estoque_tbl WHERE idProdutos_TBL = %s",
            (product_id,)
        )
        row = cur.fetchone()
        cur.close()
        if row:
            return int(row[0]), int(row[1]), int(row[2])
        return 0, 0, 0

    # ---------------------- Estoque a caminho ---------------------
    def get_incoming_stock(self, conn, product_id: int):
        cur = conn.cursor()
        sql = f"""
            SELECT COALESCE(SUM(quantidade), 0)
            FROM {self.insert_target}
            WHERE id_produto = %s
              AND status NOT IN ('entregue','cancelado')
        """
        cur.execute(sql, (product_id,))
        row = cur.fetchone()
        cur.close()
        return int(row[0]) if row else 0

    # ---------------------- Vendas Históricas --------------------
    def get_past_sales(self, conn, product_id: int, dias: int = 30):
        cur = conn.cursor()
        sql = """
            SELECT COALESCE(SUM(quantidade), 0)
            FROM vendas_tbl
            WHERE id_produto = %s
              AND data_venda >= NOW() - INTERVAL %s DAY
        """
        cur.execute(sql, (product_id, dias))
        row = cur.fetchone()
        cur.close()
        return int(row[0]) if row else 0

    # ---------------------- Calcula quantidade a pedir -------------
    def calculate_replenishment(
        self,
        estoque_atual: int,
        incoming: int,
        forecast: float,
        vendas_historicas: int,
        min_qt: int,
        max_qt: int,
        safety_stock: int,
        reorder_multiple: int
    ) -> Tuple[int, int]:
        demanda_ajustada = max(forecast, vendas_historicas) + safety_stock
        cobertura = estoque_atual + incoming

        if cobertura >= demanda_ajustada:
            return demanda_ajustada, 0

        necessario = demanda_ajustada - cobertura

        # aplica mínimo e máximo
        if necessario < min_qt:
            necessario = min_qt
        if max_qt > 0 and necessario > max_qt:
            necessario = max_qt

        # aplica múltiplo
        if reorder_multiple > 1:
            if necessario % reorder_multiple != 0:
                necessario = ((necessario // reorder_multiple) + 1) * reorder_multiple

        return demanda_ajustada, max(0, int(necessario))

    # ---------------------- Inserção do pedido ---------------------
    def insert_order(self, conn, product_id, qty, data_prevista_chegada, id_usuario):
        sql = """
        INSERT INTO pedidosreposicao_tbl
        (idUsuarios_TBL, id_produto, quantidade, nivel_aprovacao, status, data_prevista_chegada, gerado_por_ia)
        VALUES (%s, %s, %s, %s, %s, %s, 1)
        """

        with conn.cursor() as cur:
            cur.execute(
                sql, 
                (id_usuario, product_id, qty, 'setor-de-compras', 'pendente', data_prevista_chegada)
            )
        conn.commit()


        cur.close()

    # ---------------------- Loop principal -----------------------
    def run_replenishment(self, tipo_previsao: str, created_by: int = 1, dias_venda: int = 30) -> List[ReplenishmentDecision]:
        assert tipo_previsao in ("diaria", "semanal", "mensal"), "tipo_previsao deve ser: diaria, semanal, mensal"

        conn = self.get_connection()
        produtos = self.products_config_loader(conn)
        decisões: List[ReplenishmentDecision] = []

        for cfg in produtos:
            if not cfg.enabled:
                continue

            estoque_atual, min_qt, max_qt = self.get_current_stock(conn, cfg.id_produto)
            incoming = self.get_incoming_stock(conn, cfg.id_produto)
            forecast = self.forecast_provider(cfg.id_produto, tipo_previsao)
            print(f"[DEBUG][FORECAST] Produto ID {cfg.id_produto}: forecast usado = {forecast}")
            vendas_historicas = self.get_past_sales(conn, cfg.id_produto, dias=dias_venda)

            demanda_ajustada, qty_to_order = self.calculate_replenishment(
                estoque_atual=estoque_atual,
                incoming=incoming,
                forecast=forecast,
                vendas_historicas=vendas_historicas,
                min_qt=min_qt,
                max_qt=max_qt,
                safety_stock=cfg.safety_stock,
                reorder_multiple=cfg.reorder_multiple
            )

            if qty_to_order > 0:
                entrega = datetime.now() + timedelta(days=cfg.lead_time_days)

                decisão = ReplenishmentDecision(
                    id_produto=cfg.id_produto,
                    nome=cfg.nome,
                    estoque_atual=estoque_atual,
                    incoming=incoming,
                    forecast=forecast,
                    vendas_historicas=vendas_historicas,
                    demanda_ajustada=demanda_ajustada,
                    qty_to_order=qty_to_order,
                    data_prevista_chegada=entrega
                )

                decisões.append(decisão)

                print(
                    f"[IA REPOSIÇÃO PRO] Produto {cfg.nome} "
                    f"(ID {cfg.id_produto}): estoque {estoque_atual}, incoming {incoming}, "
                    f"previsão={forecast:.2f}, vendas={vendas_historicas}, "
                    f"demanda ajustada={demanda_ajustada}, pedido={qty_to_order}"
                )

                if not self.dry_run:
                    self.insert_order(conn, cfg.id_produto, qty_to_order, entrega, created_by)

        conn.close()
        return decisões