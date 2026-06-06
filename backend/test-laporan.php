<?php
$page = app(\App\Filament\Pages\LaporanKeuangan::class);
echo "Mounting...\n";
$page->mount();
echo "Mounted!\n";
$viewData = (fn() => $this->getViewData())->call($page);
echo "ViewData count: " . count($viewData) . "\n";
