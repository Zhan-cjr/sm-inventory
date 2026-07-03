<?php
$log = file_get_contents('tail.log');
preg_match_all('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] local\.ERROR: (.*?)(?=\n\[\d{4}-\d{2}-\d{2}|$)/s', $log, $matches);
if (count($matches[0]) > 0) {
    echo substr(end($matches[1]), 0, 1500);
} else {
    echo "No errors found";
}
