<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach(\App\Models\Organization::all() as $org) { 
    if(!$org->posSettings()->where('key_name', 'btn_ppob_menu')->exists()) { 
        $org->posSettings()->create([
            'key_name' => 'btn_ppob_menu', 
            'display_name' => 'Menu PPOB', 
            'shortcut_key' => 'F10', 
            'is_active' => true
        ]); 
        echo "Added for " . $org->name . "\n";
    } 
}
echo "Done.\n";
