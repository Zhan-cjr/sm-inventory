<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WarehouseCheck;

class ResyncWarehouseCheckStatus extends Command
{
    protected $signature = 'warehouse-check:resync';
    protected $description = 'Sinkronisasi ulang status Pengecekan Gudang berdasarkan penerimaan faktur GR';

    public function handle()
    {
        $this->info('Memulai sinkronisasi ulang status Pengecekan Gudang...');
        $checks = WarehouseCheck::whereIn('status', ['approved', 'processed', 'partially_processed'])->get();
        $count = 0;

        foreach ($checks as $check) {
            $oldStatus = $check->status;
            $check->syncStatus();
            $check->refresh();
            if ($oldStatus !== $check->status) {
                $this->line("WC ID: {$check->id} | Status: {$oldStatus} -> {$check->status}");
                $count++;
            }
        }

        $this->info("Selesai. Total {$count} dokumen Pengecekan Gudang diperbarui statusnya.");
    }
}
