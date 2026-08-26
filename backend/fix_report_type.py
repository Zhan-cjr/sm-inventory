import re

fixes = [
    ("app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php", "'po'", "'pesanan-pembelian'"),
    ("app/Filament/Resources/GoodsReceipts/Tables/GoodsReceiptsTable.php", "'receipt'", "'penerimaan-barang'"),
    ("app/Filament/Resources/PurchaseReturns/Tables/PurchaseReturnsTable.php", "'purchase-return'", "'retur-pembelian'"),
    ("app/Filament/Resources/StockTransfers/Tables/StockTransfersTable.php", "'transfer'", "'stock-transfer'"),
    ("app/Filament/Resources/StockAdjustments/StockAdjustmentResource.php", "'adjustment'", "'koreksi-stok'"),
]

for file, old, new in fixes:
    with open(file, 'r') as f:
        content = f.read()

    # We only want to replace the type under 'export_xls'
    # We can match Action::make('export_xls') and replace the next occurrence of old type
    
    parts = content.split("Action::make('export_xls')")
    if len(parts) > 1:
        # replace the old type ONLY in the second part
        parts[1] = parts[1].replace(f"'type' => {old}", f"'type' => {new}", 1)
        
    content = "Action::make('export_xls')".join(parts)

    with open(file, 'w') as f:
        f.write(content)
    
    print(f"Fixed {file}")
