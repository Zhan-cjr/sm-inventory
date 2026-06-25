<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$token = env('TELEGRAM_BOT_TOKEN');
echo file_get_contents("https://api.telegram.org/bot{$token}/getWebhookInfo");
