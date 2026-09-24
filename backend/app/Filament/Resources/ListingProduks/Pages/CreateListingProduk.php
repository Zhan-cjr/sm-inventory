<?php

namespace App\Filament\Resources\ListingProduks\Pages;

use App\Filament\Resources\ListingProduks\ListingProdukResource;
use App\Models\Stock;
use App\Models\SupplierDeduction;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;

class CreateListingProduk extends CreateRecord
{
    protected static string $resource = ListingProdukResource::class;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.cancel.label'))
            ->url($this->getResource()::getUrl('index'))
            ->color('gray');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = 'TRIAL';
        if (isset($data['allowed_branch_ids']) && is_array($data['allowed_branch_ids'])) {
            $data['allowed_branch_ids'] = array_values(array_filter($data['allowed_branch_ids']));
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $branchIds = (array) ($record->allowed_branch_ids ?? []);
        $now = now();

        // 1. Sinkronkan data ke setiap produk yang ada di perjanjian ini
        foreach ($record->products as $product) {
            $product->update([
                'organization_id' => $record->organization_id,
                'supplier_id' => $record->supplier_id,
                'supplier_division_id' => $record->supplier_division_id,
                'listing_status' => 'TRIAL',
                'trial_start_date' => $record->trial_start_date,
                'trial_end_date' => $record->trial_end_date,
                'allowed_branch_ids' => $branchIds,
            ]);

            // Buat record Stock untuk setiap cabang pilot
            foreach ($branchIds as $branchId) {
                Stock::firstOrCreate(
                    [
                        'branch_id' => $branchId,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity_on_hand' => 0,
                        'quantity_reserved' => 0,
                        'cost_price' => $product->cost_price,
                        'cost_price_tax' => $product->cost_price_tax,
                        'selling_price' => $product->selling_price,
                        'margin_gol_1' => $product->margin_gol_1,
                        'harga_jual_1' => $product->harga_jual_1,
                        'qty_min_gol_1' => $product->qty_min_gol_1 ?? 1,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        // 2. Jika ada biaya listing > 0, otomatis buat potongan tunggal untuk perjanjian ini
        if ((float) $record->listing_fee > 0 && !empty($record->supplier_id)) {
            SupplierDeduction::create([
                'supplier_id' => $record->supplier_id,
                'branch_id' => null,
                'deduction_type' => 'LISTING_FEE',
                'reference_id' => $record->id,
                'amount' => $record->listing_fee,
                'claimed_amount' => 0,
                'status' => 'OPEN',
                'notes' => "Listing Fee Perjanjian: {$record->listing_number} - Masa Uji Coba 3 Bulan",
            ]);
        }

        // 3. Bersihkan cache
        Cache::forget('ecommerce_products_all');
        foreach ($branchIds as $branchId) {
            Cache::forget('ecommerce_products_' . $branchId);
            Cache::forget('pos_products_json_gz_branch_' . $branchId);
        }
    }
}
