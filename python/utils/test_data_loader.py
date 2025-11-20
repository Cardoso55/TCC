from data_loader import carregar_dataset_demanda

df = carregar_dataset_demanda()

print(df.head())
print("Total de linhas:", len(df))
