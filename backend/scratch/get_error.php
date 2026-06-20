<?php
$content = file_get_contents('d:/APLIKASI PROJECT/sminventory/backend/storage/logs/laravel.log');
// find the last occurrence of "local.ERROR:"
$pos = strrpos($content, 'local.ERROR:');
if ($pos !== false) {
    echo substr($content, $pos, 1000); // print the first 1000 chars of the error
} else {
    echo "No error found.";
}
