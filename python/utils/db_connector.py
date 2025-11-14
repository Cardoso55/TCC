from sqlalchemy import create_engine

def conectar_engine():
    try:
        engine = create_engine(
            "mysql+mysqlconnector://root:@localhost/macawsystems",
            echo=False
        )
        return engine
    except Exception as e:
        print(f"Erro ao conectar ao MySQL: {e}")
        return None
