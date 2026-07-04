<?php

namespace App\Filament\Resources\StockOpname\Pages;

use App\Filament\Resources\StockOpname\StockOpnameSessionResource;
use App\Models\Stock;
use App\Models\StockOpnameItem;
use App\Models\StockOpnameRack;
use App\Models\StockOpnameRackSession;
use App\Models\StockOpnameSession;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateStockOpnameSession extends CreateRecord
{
    protected static string $resource = StockOpnameSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        $data['organization_id'] = $user->organization_id;
        $data['created_by']      = $user->id;
        $data['status']          = 'DRAFT';
        $data['session_number']  = 'OP-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        $data['session_token']   = Str::random(48); // QR untuk pengecek 2

        return $data;
    }

    protected function afterCreate(): void
    {
        $session  = $this->record;
        $opnameMode = $this->data['opname_mode'] ?? 'by_rack';
        $rackIds  = $this->data['rack_ids'] ?? [];

        DB::transaction(function () use ($session, $opnameMode, $rackIds) {
            if ($opnameMode === 'all_items') {
                $virtualRack = StockOpnameRack::firstOrCreate(
                    [
                        'branch_id' => $session->branch_id,
                        'rack_code' => 'ALL-RACK',
                    ],
                    [
                        'rack_name' => 'Semua Barang (Tanpa Rak)',
                        'location_description' => 'Rak virtual untuk menampung seluruh barang',
                        'is_active' => true,
                    ]
                );

                $rackSession = StockOpnameRackSession::create([
                    'session_id'    => $session->id,
                    'rack_id'       => $virtualRack->id,
                    'rack_token'    => Str::random(48),
                    'count1_status' => 'PENDING',
                    'count2_status' => 'PENDING',
                ]);

                $stocks = Stock::with('product')
                    ->where('branch_id', $session->branch_id)
                    ->where('quantity_on_hand', '>=', 0)
                    ->get();

                $itemsData = [];
                $now = now();
                foreach ($stocks as $stock) {
                    if (!$stock->product || !$stock->product->is_active) continue;

                    $itemsData[] = [
                        'id'              => Str::uuid()->toString(),
                        'session_id'      => $session->id,
                        'rack_session_id' => $rackSession->id,
                        'product_id'      => $stock->product_id,
                        'system_quantity' => $stock->quantity_on_hand,
                        'status'          => 'PENDING',
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }

                foreach (array_chunk($itemsData, 500) as $chunk) {
                    StockOpnameItem::insert($chunk);
                }

            } else {
                foreach ($rackIds as $rackId) {
                    $rack = StockOpnameRack::find($rackId);
                    if (!$rack) continue;

                    $rackSession = StockOpnameRackSession::create([
                        'session_id'    => $session->id,
                        'rack_id'       => $rackId,
                        'rack_token'    => Str::random(48),
                        'count1_status' => 'PENDING',
                        'count2_status' => 'PENDING',
                    ]);

                    $stocks = Stock::with('product')
                        ->where('branch_id', $session->branch_id)
                        ->where('quantity_on_hand', '>=', 0)
                        ->whereHas('racks', function ($q) use ($rackId) {
                            $q->where('stock_opname_racks.id', $rackId);
                        })
                        ->get();

                    foreach ($stocks as $stock) {
                        if (!$stock->product || !$stock->product->is_active) continue;

                        StockOpnameItem::firstOrCreate(
                            [
                                'rack_session_id' => $rackSession->id,
                                'product_id'      => $stock->product_id,
                            ],
                            [
                                'session_id'      => $session->id,
                                'system_quantity'  => $stock->quantity_on_hand,
                                'status'          => 'PENDING',
                            ]
                        );
                    }
                }
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return StockOpnameSessionResource::getUrl('view', ['record' => $this->record]);
    }
}
