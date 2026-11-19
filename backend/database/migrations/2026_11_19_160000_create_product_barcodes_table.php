<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('product_barcodes')) {
            Schema::create('product_barcodes', function (Blueprint $table) {
                $table->id();
                $table->uuid('product_id')->index();
                $table->uuid('organization_id')->nullable()->index();
                $table->string('barcode', 100)->index();
                $table->timestamps();

                $table->unique(['product_id', 'barcode'], 'pb_product_barcode_unique');
                $table->index(['organization_id', 'barcode'], 'pb_org_barcode_idx');
            });

            // Backfill existing metadata->additional_barcodes in chunks
            $now = now();
            DB::table('products')
                ->select(['id', 'organization_id', 'metadata'])
                ->whereNotNull('metadata')
                ->orderBy('id')
                ->chunk(500, function ($products) use ($now) {
                    $rows = [];
                    foreach ($products as $product) {
                        $meta = is_string($product->metadata) ? json_decode($product->metadata, true) : $product->metadata;
                        if (!is_array($meta) || empty($meta['additional_barcodes'])) {
                            continue;
                        }

                        $codes = is_array($meta['additional_barcodes'])
                            ? $meta['additional_barcodes']
                            : array_map('trim', explode(',', (string) $meta['additional_barcodes']));

                        $seen = [];
                        foreach ($codes as $code) {
                            $code = trim((string) $code);
                            if ($code === '' || mb_strlen($code) > 100) {
                                continue;
                            }
                            $lower = strtolower($code);
                            if (isset($seen[$lower])) {
                                continue;
                            }
                            $seen[$lower] = true;

                            $rows[] = [
                                'product_id' => $product->id,
                                'organization_id' => $product->organization_id,
                                'barcode' => $code,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    if (!empty($rows)) {
                        foreach (array_chunk($rows, 500) as $chunk) {
                            DB::table('product_barcodes')->insertOrIgnore($chunk);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_barcodes');
    }
};
