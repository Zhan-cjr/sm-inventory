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

    # Change \Filament\Actions\ActionGroup to \Filament\Tables\Actions\ActionGroup if in a Table file
    # Wait, StockAdjustmentResource is NOT a table file, it's a Resource file. 
    # But usually List/Resource pages use page actions, Table components use table actions.
    # Actually, Filament v3 Table headerActions also use \Filament\Tables\Actions.
    
    # Let's just blindly replace \Filament\Actions\ActionGroup with \Filament\Tables\Actions\ActionGroup
    # inside headerActions.
    content = content.replace(r'\Filament\Actions\ActionGroup::make', r'\Filament\Tables\Actions\ActionGroup::make')
    
    # Also replace \Filament\Actions\Action::make('export_xls') with \Filament\Tables\Actions\Action
    content = content.replace(r'\Filament\Actions\Action::make', r'\Filament\Tables\Actions\Action::make')

    # Also make sure ExportAction is fully qualified
    # If it is ExportAction::make, replace with \Filament\Tables\Actions\ExportAction::make
    content = re.sub(r'(?<!\\)ExportAction::make', r'\\Filament\\Tables\\Actions\\ExportAction::make', content)

    with open(file, 'w') as f:
        f.write(content)
    
    print(f"Fixed {file}")
