import pandas as pd

file_path = r'd:\APLIKASI PROJECT\sminventory\DATABARANG\Produk_Global_Merged V2.xlsx'
print("Loading data...")
df = pd.read_excel(file_path)

initial_pemasok = df['Pemasok'].copy()

# Ensure Nm Brg is string
df['Nm Brg_str'] = df['Nm Brg'].astype(str).str.strip()
df['Brand'] = df['Nm Brg_str'].apply(lambda x: x.split()[0] if len(x.split()) > 0 else '')

# Ensure Pemasok is string for counting
df['Pemasok'] = df['Pemasok'].astype(str)

print("Calculating majority supplier per brand...")
# Map each brand to its majority supplier
brand_supplier_map = {}
for brand, group in df.groupby('Brand'):
    if brand == '':
        continue
    
    # Get the most frequent supplier for this brand
    mode_series = group['Pemasok'].mode()
    if not mode_series.empty:
        # If there's a tie, mode_series might have multiple elements, we just take the first
        majority_supplier = mode_series.iloc[0]
        brand_supplier_map[brand] = majority_supplier

# Apply the mapping
df['Pemasok_Baru'] = df.apply(
    lambda row: brand_supplier_map.get(row['Brand'], row['Pemasok']) if row['Brand'] != '' else row['Pemasok'],
    axis=1
)

# Track changes for reporting
changes = 0
for idx, row in df.iterrows():
    if str(initial_pemasok[idx]) != str(row['Pemasok_Baru']):
        if changes < 20: # print first 20 changes
            print(f"Brand '{row['Brand']}': Changed supplier from '{initial_pemasok[idx]}' -> '{row['Pemasok_Baru']}' (Item: {row['Nm Brg']})")
        changes += 1

# Apply changes
df['Pemasok'] = df['Pemasok_Baru']

# Clean up temporary columns
df = df.drop(columns=['Nm Brg_str', 'Brand', 'Pemasok_Baru'])

output_path = r'd:\APLIKASI PROJECT\sminventory\DATABARANG\Produk_Global_Merged V3.xlsx'
print(f"Total changes made: {changes}")
print(f"Saving to {output_path}...")
df.to_excel(output_path, index=False)
print("Done!")
