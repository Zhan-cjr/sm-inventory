<?php
$filepath = 'storage/logs/laravel.log';
$lines = 150;
$f = fopen($filepath, "r");
$pos = -2;
$t = " ";
$linesFound = 0;

while ($linesFound < $lines) {
    if (fseek($f, $pos, SEEK_END) == -1) break;
    $t = fgetc($f);
    if ($t == "\n") $linesFound++;
    $pos--;
}

$content = "";
while (!feof($f)) {
    $content .= fgets($f);
}
fclose($f);
file_put_contents('last_log.txt', $content);
