import re
import os

files = [
    "app/Filament/Resources/ArsipTransaksiResource.php",
    "app/Filament/Resources/ArsipReturPenjualanResource.php",
    "app/Filament/Resources/PurchaseOrders/Tables/PurchaseOrdersTable.php",
    "app/Filament/Resources/GoodsReceipts/Tables/GoodsReceiptsTable.php",
    "app/Filament/Resources/PurchaseReturns/Tables/PurchaseReturnsTable.php",
    "app/Filament/Resources/StockTransfers/Tables/StockTransfersTable.php",
    "app/Filament/Resources/StockAdjustments/StockAdjustmentResource.php",
    "app/Filament/Resources/Products/Tables/ProductsTable.php",
]

for file in files:
    with open(file, 'r') as f:
        content = f.read()

    # Find the report type
    type_match = re.search(r"'type'\s*=>\s*'([^']+)'", content)
    if not type_match:
        print(f"Type not found in {file}")
        continue
    report_type = type_match.group(1)

    # Find ExportAction block
    # It might start with \Filament\Actions\ExportAction::make(...) or ExportAction::make(...)
    export_pattern = re.compile(
        r"(\s*)(?:\\\\Filament\\\\Actions\\\\)?ExportAction::make\([^)]*\).*?(?=\]\);|,\n\s*\]\);|\]\);)", 
        re.DOTALL
    )
    
    match = export_pattern.search(content)
    if not match:
        print(f"ExportAction not found in {file}")
        continue

    indent = match.group(1)
    original_export_action = match.group(0).strip()
    
    # We replace 'Export Excel' or 'Export CSV' label in the original with 'Export Xlsx (Raw Data)'
    original_export_action = re.sub(r"->label\('[^']+'\)", "->label('Export Xlsx (Raw Data)')", original_export_action)
    
    # Construct the new ActionGroup
    new_action = f"""\\\\Filament\\\\Actions\\\\ActionGroup::make([
{indent}    {original_export_action},
{indent}    \\\\Filament\\\\Actions\\\\Action::make('export_xls')
{indent}        ->label('Export Xls (Format Cetak)')
{indent}        ->icon('heroicon-o-document-text')
{indent}        ->url(fn (\\\\Filament\\\\Tables\\\\Contracts\\\\HasTable $livewire) => route('print.report', [
{indent}            'type' => '{report_type}',
{indent}            'export' => 'xls',
{indent}            'tableFilters' => $livewire->tableFilters,
{indent}            'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null
{indent}        ]), true)
{indent}])
{indent}->label('Export')
{indent}->icon('heroicon-o-arrow-down-tray')
{indent}->color('success')
{indent}->button()"""
    
    new_content = content[:match.start()] + indent + new_action + content[match.end():]
    
    with open(file, 'w') as f:
        f.write(new_content)
    
    print(f"Patched {file}")
