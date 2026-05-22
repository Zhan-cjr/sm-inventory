<?php
$html = file_get_contents('http://localhost:8080/admin/login');
preg_match_all('/<button[^>]*>/i', $html, $matches);
print_r($matches[0]);
preg_match_all('/<a[^>]*class="[^"]*fi-btn[^"]*"[^>]*>/i', $html, $matches_links);
print_r($matches_links[0]);
