<?php
$lines = file('storage/logs/laravel.log');
$last = '';
for ($i = count($lines) - 1; $i >= 0; $i--) {
    if (strpos($lines[$i], '[2026-') === 0) {
        $last = $lines[$i];
        break;
    }
}
echo $last;
