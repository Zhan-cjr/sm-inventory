<?php
$lines = shell_exec('tail -n 2000 storage/logs/laravel.log');
$lines = explode("\n", $lines);
foreach($lines as $i => $line) {
    if (strpos($line, 'local.ERROR') !== false) {
        echo "\n---\n";
        for($j=0; $j<20; $j++) {
            if(isset($lines[$i+$j])) echo $lines[$i+$j] . "\n";
        }
    }
}
