import pandas as pd

try:
    file_path = r'd:\APLIKASI PROJECT\sminventory\DATABARANG\Produk_Global_Merged V2.xlsx'
    df = pd.read_excel(file_path, nrows=50)
    print("\nData:")
    print(df[['Nm Brg', 'Pemasok']].to_string())
except Exception as e:
    print("Error:", e)
