<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

// Menjalankan pengecekan produk discontinue setiap jam 12 malam
Schedule::command('inventory:auto-discontinue')->dailyAt('00:00');

// Menjalankan pengecekan dan pembuatan draft promo otomatis setiap jam 1 dini hari
Schedule::command('inventory:auto-pricing')->dailyAt('01:00');

// Membersihkan bukti faktur penerimaan barang yang berumur > 3 bulan
Schedule::command('app:cleanup-faktur-images')->dailyAt('01:30');
