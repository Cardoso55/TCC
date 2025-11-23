import pandas as pd
from sklearn.ensemble import RandomForestRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error
from joblib import dump
import json
import os

def train_all_models():
    df = pd.read_csv("data/vendas_prepared.csv")

    output_dir = "output/models/"
    os.makedirs(output_dir, exist_ok=True)

    metadata = {}

    produtos = df["id_produto"].unique()

    for pid in produtos:
        df_prod = df[df["id_produto"] == pid]

        if len(df_prod) < 10:
            print(f"[SKIP] Produto {pid} tem poucos dados, pulando...")
            continue

        X = df_prod[["ano", "mes", "dia", "dia_semana", "semana_ano"]]
        y = df_prod["quantidade"]

        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=0.2, shuffle=True, random_state=42
        )

        model = RandomForestRegressor(
            n_estimators=200,
            max_depth=10,
            random_state=42
        )

        model.fit(X_train, y_train)

        y_pred = model.predict(X_test)
        mae = mean_absolute_error(y_test, y_pred)

        model_path = f"{output_dir}/model_{pid}.joblib"
        dump(model, model_path)

        metadata[str(pid)] = {
            "modelo": model_path,
            "mae": float(mae),
            "treino_amostras": len(df_prod)
        }

        print(f"[OK] Modelo treinado para produto {pid} — MAE = {mae}")

    with open(f"{output_dir}/models_metadata.json", "w") as f:
        json.dump(metadata, f, indent=4)

    print("\n🎯 TREINAMENTO FINALIZADO!")
    print(f"Modelos salvos em: {output_dir}")


if __name__ == "__main__":
    train_all_models()
