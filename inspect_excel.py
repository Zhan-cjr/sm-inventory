import pandas as pd

try:
    df1 = pd.read_excel(r'd:\APLIKASI PROJECT\sminventory\DATABARANG\1.xlsx', header=None, nrows=20)
    print("=== 1.xlsx (First 20 rows) ===")
    print(df1.to_string())
    
    df2 = pd.read_excel(r'd:\APLIKASI PROJECT\sminventory\DATABARANG\2.xlsx', header=None, nrows=20)
    print("\n=== 2.xlsx (First 20 rows) ===")
    print(df2.to_string())
except Exception as e:
    print("Error:", e)
