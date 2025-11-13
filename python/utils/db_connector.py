# utils/db_connector.py
from sqlalchemy import create_engine

def conectar():
    """
    Cria e retorna uma engine SQLAlchemy conectada ao MySQL do Laragon.
    """
    try:
        engine = create_engine("mysql+mysqlconnector://root:@localhost/macawsystems")
        return engine
    except Exception as e:
        print(f"Erro ao conectar ao banco: {e}")
        return None
