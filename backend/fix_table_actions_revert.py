import re

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

    # Revert \Filament\Tables\Actions\ to \Filament\Actions\
    content = content.replace(r'\Filament\Tables\Actions\ActionGroup', r'\Filament\Actions\ActionGroup')
    content = content.replace(r'\Filament\Tables\Actions\Action', r'\Filament\Actions\Action')
    content = content.replace(r'\Filament\Tables\Actions\ExportAction', r'\Filament\Actions\ExportAction')

    with open(file, 'w') as f:
        f.write(content)
    
    print(f"Reverted {file}")
