import os

csv_path = os.path.join(os.path.dirname(__file__), "demanda_dataset.csv")

print("CSV path:", csv_path)
print("Existe?:", os.path.exists(csv_path))

if os.path.exists(csv_path):
    with open(csv_path, "r", encoding="utf-8") as f:
        print("\nConteúdo do arquivo:")
        print(f.read())
