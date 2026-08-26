<?php
$dir = new RecursiveDirectoryIterator('app/Filament/Pages');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/\.php$/');

foreach ($files as $file) {
    $content = file_get_contents($file->getPathname());
    if (strpos($content, "'tableSearchQuery'") === false && strpos($content, "make('cetak_daftar')") !== false) {
        $content = preg_replace(
            "/'tableFilters' => \\\$livewire->tableFilters,/",
            "'tableFilters' => \$livewire->tableFilters,\n                            'tableSearchQuery' => method_exists(\$livewire, 'getTableSearch') ? \$livewire->getTableSearch() : null,",
            $content
        );
        file_put_contents($file->getPathname(), $content);
        echo "Updated search query in: " . $file->getPathname() . "\n";
    }
}
