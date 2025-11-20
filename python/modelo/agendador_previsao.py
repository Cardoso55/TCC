# python/agendador_previsao.py
import subprocess
from datetime import datetime

def rodar_script(caminho_script):
    try:
        print(f"\n🚀 Rodando script: {caminho_script} | {datetime.now()}")
        subprocess.run(["python", caminho_script], check=True)
        print(f"✅ Script {caminho_script} finalizado com sucesso!")
    except subprocess.CalledProcessError as e:
        print(f"❌ Erro ao rodar {caminho_script}: {e}")

if __name__ == "__main__":
    # Rodar apenas o novo script de previsões
    rodar_script("modelo/predict_previsoes.py")
