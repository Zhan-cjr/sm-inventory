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

    # Fix ActionGroup
    content = re.sub(r'\\\\Filament\\\\Actions\\\\\\\\Filament\\\\Actions\\\\ActionGroup::make', r'\\Filament\\Actions\\ActionGroup::make', content)
    content = re.sub(r'\\Filament\\Actions\\\\\\\\Filament\\\\Actions\\\\ActionGroup::make', r'\\Filament\\Actions\\ActionGroup::make', content)
    content = re.sub(r'\\\\Filament\\\\Actions\\\\ActionGroup::make', r'\\Filament\\Actions\\ActionGroup::make', content)
    
    # Fix Action
    content = content.replace(r'\\Filament\\Actions\\Action::make', r'\Filament\Actions\Action::make')
    
    # Fix HasTable
    content = content.replace(r'\\Filament\\Tables\\Contracts\\HasTable', r'\Filament\Tables\Contracts\HasTable')

    with open(file, 'w') as f:
        f.write(content)
    
    print(f"Fixed {file}")
