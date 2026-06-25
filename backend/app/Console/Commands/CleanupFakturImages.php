<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\GoodsReceipt;
use Illuminate\Support\Facades\Storage;

#[Signature('app:cleanup-faktur-images')]
#[Description('Deletes goods receipt faktur images older than 3 months')]
class CleanupFakturImages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of old faktur images...');
        
        $oldReceipts = GoodsReceipt::whereNotNull('faktur_image')
            ->where('created_at', '<=', now()->subMonths(3))
            ->get();
            
        $count = 0;
        foreach ($oldReceipts as $receipt) {
            $images = is_array($receipt->faktur_image) ? $receipt->faktur_image : [$receipt->faktur_image];
            foreach ($images as $image) {
                if ($image && Storage::disk('public')->exists($image)) {
                    Storage::disk('public')->delete($image);
                }
            }
            $receipt->update(['faktur_image' => null]);
            $count++;
        }
        
        $this->info("Successfully deleted {$count} old faktur images.");
    }
}
