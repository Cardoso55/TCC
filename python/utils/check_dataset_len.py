import sys, os
sys.path.append(os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

from utils.dataset_builder import prepare_demand_dataset

df = prepare_demand_dataset("models/datasets/demanda_base.csv")
print(df.head())
print("\nTotal de linhas:", len(df))
