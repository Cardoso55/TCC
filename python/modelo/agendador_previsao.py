import subprocess
from datetime import datetime
import os

import warnings
warnings.filterwarnings("ignore")


def rodar_script(caminho_script):
    try:
        print(f"\nRodando script: {caminho_script} | {datetime.now()}")
        subprocess.run(["python", caminho_script], check=True)
        print(f"Script {caminho_script} finalizado com sucesso!")
    except subprocess.CalledProcessError as e:
        print(f"Erro ao rodar {caminho_script}: {e}")

if __name__ == "__main__":
    BASE_DIR = os.path.dirname(os.path.abspath(__file__))
    predict_script = os.path.join(BASE_DIR, "predict_previsoes.py")


    BASE_DIR = os.path.dirname(os.path.abspath(__file__))
    SCRIPT = os.path.join(BASE_DIR, "predict_previsoes.py")

    rodar_script(SCRIPT)

