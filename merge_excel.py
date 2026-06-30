import os
import pandas as pd

folder_path = r'd:\APLIKASI PROJECT\sminventory\DATABARANG'
output_file = os.path.join(folder_path, 'Produk_Global_Merged.xlsx')

all_data = []

for filename in os.listdir(folder_path):
    if filename.endswith('.xlsx') or filename.endswith('.xls'):
        filepath = os.path.join(folder_path, filename)
        if filepath == output_file:
            continue
        if filename.startswith('~$'): # Skip temp files
            continue
            
        print(f"Processing {filename}...")
        
        # Read the file without assuming header to find the actual header row
        try:
            df_temp = pd.read_excel(filepath, header=None, nrows=30)
            
            header_row_idx = -1
            for idx, row in df_temp.iterrows():
                # Check if 'Barcode' is in the row's values (case-insensitive)
                row_values = [str(val).strip().lower() for val in row.values]
                if 'barcode' in row_values:
                    header_row_idx = idx
                    break
            
            if header_row_idx != -1:
                # Re-read the file with the correct header
                df = pd.read_excel(filepath, header=header_row_idx)
                all_data.append(df)
            else:
                print(f"Warning: Could not find 'Barcode' header in {filename}")
        except Exception as e:
            print(f"Error processing {filename}: {e}")

if all_data:
    print("Concatenating all data...")
    merged_df = pd.concat(all_data, ignore_index=True)
    
    # Standardize 'Barcode' column name if there are case differences
    barcode_col = None
    for col in merged_df.columns:
        if str(col).strip().lower() == 'barcode':
            barcode_col = col
            break
    
    if barcode_col:
        initial_count = len(merged_df)
        
        # Convert barcode to string to avoid mixed types issues when dropping duplicates
        merged_df[barcode_col] = merged_df[barcode_col].astype(str)
        
        merged_df = merged_df.drop_duplicates(subset=[barcode_col])
        final_count = len(merged_df)
        print(f"Removed {initial_count - final_count} duplicates.")
        
        print("Saving to Excel...")
        merged_df.to_excel(output_file, index=False)
        print(f"Successfully saved to {output_file}")
    else:
        print("Error: 'Barcode' column not found in merged data.")
else:
    print("No data found to merge.")
