import os
import json
import pandas as pd
import joblib
from datetime import datetime
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestRegressor
from sklearn.metrics import mean_absolute_error


# Caminhos
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
METADATA_PATH = os.path.join(BASE_DIR, "models_metadata.json")
SAVED_MODELS_DIR = os.path.join(BASE_DIR, "saved_models")

# Garantir pasta de modelos salvos
os.makedirs(SAVED_MODELS_DIR, exist_ok=True)


# ================================
# Função para registrar metadados
# ================================
def save_metadata(model_name, algorithm, accuracy):
    # Carregar JSON
    if os.path.exists(METADATA_PATH):
        with open(METADATA_PATH, "r") as f:
            metadata = json.load(f) if f.read().strip() else {}
    else:
        metadata = {}

    metadata[model_name] = {
        "created_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "algorithm": algorithm,
        "accuracy": accuracy,
        "version": metadata.get(model_name, {}).get("version", 0) + 1
    }

    with open(METADATA_PATH, "w") as f:
        json.dump(metadata, f, indent=4)


# ================================
# Função principal de treinamento
# ================================
def treinar_previsao_demanda(caminho_csv):
    print("\n=== Treinando modelo de previsão de demanda ===")

    # 1. Carregar dados
    df = pd.read_csv(caminho_csv)

    # 2. Separar features e target
    X = df.drop("demanda", axis=1)
    y = df["demanda"]

    # 3. Dividir treino/teste
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42
    )

    # 4. Criar modelo
    model = RandomForestRegressor(n_estimators=200)

    # 5. Treinar modelo
    model.fit(X_train, y_train)

    # 6. Avaliar
    preds = model.predict(X_test)
    erro = mean_absolute_error(y_test, preds)

    print(f"Erro médio absoluto: {erro}")

    # 7. Salvar modelo
    nome_modelo = f"demand_forecast_model_v{datetime.now().strftime('%Y%m%d_%H%M%S')}.pkl"
    caminho_modelo = os.path.join(SAVED_MODELS_DIR, nome_modelo)

    joblib.dump(model, caminho_modelo)
    print(f"Modelo salvo em: {caminho_modelo}")

    # 8. Registrar metadados
    save_metadata("demand_forecast_model", "RandomForest", float(erro))

    print("Treinamento concluído!\n")

    return caminho_modelo, erro
