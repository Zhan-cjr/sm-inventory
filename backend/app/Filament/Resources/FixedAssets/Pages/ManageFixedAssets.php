<?php

namespace App\Filament\Resources\FixedAssets\Pages;

use App\Filament\Resources\FixedAssets\FixedAssetResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFixedAssets extends ManageRecords
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['purchase_price'] = (float) str_replace('.', '', (string) $data['purchase_price']);
                    $data['salvage_value'] = (float) str_replace('.', '', (string) $data['salvage_value']);
                    return $data;
                })
                ->after(function (\App\Models\FixedAsset $record) {
                    if ($record->payment_account_id) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $journal = \App\Models\JournalEntry::create([
                                'organization_id' => $record->organization_id,
                                'branch_id' => $record->branch_id,
                                'reference_number' => 'AST-BUY-' . strtoupper(\Illuminate\Support\Str::random(6)),
                                'entry_date' => $record->purchase_date,
                                'description' => 'Pembelian Aset Tetap: ' . $record->name,
                                'status' => 'posted',
                                'created_by' => auth()->id(),
                            ]);
                            
                            \App\Models\JournalEntryLine::create([
                                'journal_entry_id' => $journal->id,
                                'account_id' => $record->asset_account_id,
                                'description' => 'Aset Bertambah',
                                'debit' => $record->purchase_price,
                                'credit' => 0,
                            ]);
                            
                            \App\Models\JournalEntryLine::create([
                                'journal_entry_id' => $journal->id,
                                'account_id' => $record->payment_account_id,
                                'description' => 'Pembayaran Aset',
                                'debit' => 0,
                                'credit' => $record->purchase_price,
                            ]);
                        });
                    }
                }),
        ];
    }
}
