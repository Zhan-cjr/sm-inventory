import os
import re

directory = 'app/Filament/Exports/'

names = {
    'TransactionExporter.php': 'Penjualan Kasir',
    'TransactionItemExporter.php': 'Laporan Jasa Terjual',
    'StockAdjustmentExporter.php': 'Koreksi Stok',
    'StockOpnameItemExporter.php': 'Detail Stok Opname',
    'StockExporter.php': 'Laporan Persediaan',
    'GoodsReceiptItemExporter.php': 'Laporan Barang Dibeli',
    'StockOpnameSessionExporter.php': 'Ringkas Stok Opname',
    'PurchaseOrderExporter.php': 'Pesanan Pembelian',
    'ProductExporter.php': 'Daftar Produk',
    'ShiftExporter.php': 'Laporan Shift Kasir',
    'AllSalesItemExporter.php': 'Laporan Barang Dijual',
    'LabaRugiExporter.php': 'Laporan Laba Rugi',
    'GoodsReceiptExporter.php': 'Penerimaan Barang',
    'StockTransferExporter.php': 'Stock Transfer',
    'PurchaseReturnExporter.php': 'Retur Pembelian'
}

for filename in os.listdir(directory):
    if filename.endswith(".php"):
        filepath = os.path.join(directory, filename)
        with open(filepath, 'r') as file:
            content = file.read()
        
        name = names.get(filename, filename.replace('Exporter.php', ''))
        
        # Replace "Export selesai." with "Export {name} selesai."
        new_content = content.replace("'Export selesai. ' .", f"'Export {name} selesai. ' .")
        
        with open(filepath, 'w') as file:
            file.write(new_content)
        
        print(f"Updated {filename} to use name {name}")
