import re
import os

files = [
    "app/Filament/Pages/LaporanBarangDibeli.php",
    "app/Filament/Pages/LaporanStokOpnameRingkas.php",
    "app/Filament/Pages/LaporanStokOpnameDetail.php",
    "app/Filament/Pages/LaporanPenjualan.php",
    "app/Filament/Pages/LaporanPenjualanKasir.php",
    "app/Filament/Pages/LaporanShiftKasir.php",
    "app/Filament/Pages/LaporanLabaRugi.php",
    "app/Filament/Pages/ArsipLaporanEOD.php"
]

for f in files:
    if not os.path.exists(f):
        continue
        
    with open(f, 'r') as file:
        content = file.read()
        
    type_match = re.search(r"'type'\s*=>\s*'([^']+)'", content)
    if not type_match:
        if "ArsipLaporanEOD" in f:
            report_type = "arsip-transaksi"
        else:
            print(f"Report type not found in {f}")
            continue
    else:
        report_type = type_match.group(1)
    
    exporter_match = re.search(r"->exporter\(([^)]+)\)", content)
    if not exporter_match:
        print(f"Exporter class not found in {f}")
        continue
    exporter_class = exporter_match.group(1)
    
    replacement = f"""\\Filament\\Actions\\ActionGroup::make([
                    \\Filament\\Actions\\ExportAction::make()
                        ->label('Export Xlsx (Raw Data)')
                        ->exporter({exporter_class})
                        ->formats([\\Filament\\Actions\\Exports\\Enums\\ExportFormat::Xlsx])
                        ->icon('heroicon-o-table-cells'),
                    \\Filament\\Actions\\Action::make('export_xls')
                        ->label('Export Xls (Format Cetak)')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (\\Filament\\Tables\\Contracts\\HasTable $livewire) => route('print.report', [
                            'type' => '{report_type}',
                            'export' => 'xls',
                            'tableFilters' => $livewire->tableFilters,
                            'tableSearchQuery' => method_exists($livewire, 'getTableSearch') ? $livewire->getTableSearch() : null,
                        ]), true)
                ])
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button(),
            ]);"""
            
    pattern = r"(?:\\?Filament\\Actions\\)?ExportAction::make\(\)[\s\S]*?\]\);"
    
    new_content, count = re.subn(pattern, lambda m: replacement, content)
    
    if count > 0:
        with open(f, 'w') as file:
            file.write(new_content)
        print(f"Updated {f}")
    else:
        print(f"Could not replace ExportAction in {f}")
