import mysql.connector
from mysql.connector import Error

def get_connection():
    try:
        conn = mysql.connector.connect(
            host="127.0.0.1",
            user="root",
            password="",   # se tiver senha, coloque aqui
            database="macawsystems"
        )
        if conn.is_connected():
            return conn
        else:
            print("❌ Conexão falhou (is_connected=False)")
            return None

    except Error as e:
        print(f"❌ Erro ao conectar no MySQL: {e}")
        return None
