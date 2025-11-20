from dataset_builder import prepare_demand_dataset

df = prepare_demand_dataset("models/datasets/demanda_base.csv")
print(df.head())
