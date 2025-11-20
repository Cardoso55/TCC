import sys
import os
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

import json
import joblib
import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from datetime import datetime

from utils.dataset_builder import prepare_demand_dataset


# Caminhos
CSV_PATH = "models/datasets/demanda_base.csv"
MODEL_PATH = "models/demand_forecast_model.pkl"
METADATA_PATH = "models/models_metadata.json"

print("📦 Carregando dataset...")
df = prepare_demand_dataset(CSV_PATH)

# Features usadas no modelo
FEATURES = [
    "id_produto",
    "dia",
    "mes",
    "ano",
    "dia_semana",
    "lag_1",
    "lag_7",
    "lag_30",
    "media_7",
    "media_30"
]

TARGET = "quantidade"

print("🔧 Separando treino e teste...")
X = df[FEATURES]
y = df[TARGET]

X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42
)

print("🤖 Treinando modelo RandomForest...")
model = RandomForestRegressor(
    n_estimators=200,
    random_state=42,
    n_jobs=-1
)

model.fit(X_train, y_train)

print("📊 Avaliando modelo...")
score = model.score(X_test, y_test)

print(f"🔮 Acurácia aproximada: {score:.4f}")

print("💾 Salvando modelo em:", MODEL_PATH)
joblib.dump(model, MODEL_PATH)

print("📝 Salvando metadados...")
metadata = {
    "demand_forecast_model": {
        "created_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "algorithm": "RandomForestRegressor",
        "accuracy": round(float(score), 4),
        "features": FEATURES
    }
}

with open(METADATA_PATH, "w") as f:
    json.dump(metadata, f, indent=4)

print("✅ Modelo treinado e salvo com sucesso!")
