files = [
    "app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php",
    "app/Filament/Resources/GoodsReceipts/Tables/GoodsReceiptsTable.php",
    "app/Filament/Resources/PurchaseReturns/Tables/PurchaseReturnsTable.php",
    "app/Filament/Resources/StockTransfers/Tables/StockTransfersTable.php",
    "app/Filament/Resources/StockAdjustments/StockAdjustmentResource.php"
]

for file in files:
    with open(file, 'r') as f:
        content = f.read()

    # The issue: \Filament\Actions\\Filament\Actions\ActionGroup
    content = content.replace(r'\Filament\Actions\\Filament\Actions\ActionGroup', r'\Filament\Actions\ActionGroup')

    with open(file, 'w') as f:
        f.write(content)
    
    print(f"Fixed {file}")
