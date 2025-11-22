import subprocess
import datetime
import sys
import os

def log(msg):
    agora = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[AI PIPELINE] {agora} - {msg}")

def run_step(description, command):
    log(f"INICIANDO → {description}")
    try:
        result = subprocess.run(command, capture_output=True, text=True)

        if result.stdout:
            log(f"OUTPUT STDOUT:\n{result.stdout}")

        if result.stderr:
            log(f"OUTPUT STDERR:\n{result.stderr}")

        if result.returncode != 0:
            log(f"❌ ERRO ao executar: {description}")
            return False
        
        log(f"✔ FINALIZADO → {description}")
        return True

    except Exception as e:
        log(f"❌ ERRO GRAVE executando '{description}': {str(e)}")
        return False


if __name__ == "__main__":
    log("=== IA AUTOMÁTICA DO TCC INICIADA ===")

    BASE_PATH = os.path.dirname(os.path.abspath(__file__))

    # 1️⃣ RODAR PREVISÕES
    previsao_script = os.path.join(BASE_PATH, "modelo", "agendador_previsao.py")
    run_step("GERAÇÃO DE PREVISÕES", ["python", previsao_script])

    # 2️⃣ RODAR DECISÕES DE REPOSIÇÃO (IA PRO)
    decisao_script = os.path.join(BASE_PATH, "export_replenishment_json.py")
    run_step("GERAÇÃO DE DECISÕES DE REPOSIÇÃO", ["python", decisao_script])

    log("=== IA AUTOMÁTICA FINALIZADA ===")
