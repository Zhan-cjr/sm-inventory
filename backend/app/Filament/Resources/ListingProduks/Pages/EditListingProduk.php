<?php

namespace App\Filament\Resources\ListingProduks\Pages;

use App\Filament\Resources\ListingProduks\ListingProdukResource;
use App\Models\Stock;
use App\Models\SupplierDeduction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Cache;

class EditListingProduk extends EditRecord
{
    protected static string $resource = ListingProdukResource::class;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCancelFormAction(): Action
    {
        return Action::make('cancel')
            ->label(__('filament-panels::resources/pages/edit-record.form.actions.cancel.label'))
            ->url($this->getResource()::getUrl('index'))
            ->color('gray');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['allowed_branch_ids']) && is_array($data['allowed_branch_ids'])) {
            $data['allowed_branch_ids'] = array_values(array_filter($data['allowed_branch_ids']));
        }
        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $branchIds = (array) ($record->allowed_branch_ids ?? []);
        $now = now();

        // 1. Sinkronkan cabang ke semua produk dalam listing ini
        foreach ($record->products as $product) {
            $product->update([
                'organization_id' => $record->organization_id,
                'supplier_id' => $record->supplier_id,
                'supplier_division_id' => $record->supplier_division_id,
                'trial_start_date' => $record->trial_start_date,
                'trial_end_date' => $record->trial_end_date,
                'allowed_branch_ids' => $branchIds,
            ]);

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

        // 2. Sinkronkan nilai Listing Fee di SupplierDeductions jika ada
        $deduction = SupplierDeduction::where('reference_id', $record->id)
            ->where('deduction_type', 'LISTING_FEE')
            ->first();

        if ($deduction) {
            if ((float) $record->listing_fee > 0) {
                $deduction->amount = $record->listing_fee;
                $deduction->supplier_id = $record->supplier_id;
                $deduction->save();
            } else {
                if ($deduction->claimed_amount <= 0 && $deduction->status === 'OPEN') {
                    $deduction->delete();
                }
            }
        } elseif ((float) $record->listing_fee > 0 && !empty($record->supplier_id)) {
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
