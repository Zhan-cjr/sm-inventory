<?php

namespace App\Filament\Resources\Vouchers\Pages;

use App\Filament\Resources\Vouchers\VoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVouchers extends ManageRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('bulk_generate')
                ->label('Generate Bulk Voucher')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\TextInput::make('prefix')
                        ->label('Prefix Kode')
                        ->default('VCH-')
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Nama Voucher (Opsional)')
                        ->placeholder('Contoh: Voucher Belanja Lebaran'),
                    \Filament\Forms\Components\TextInput::make('nominal_value')
                        ->label('Nilai Nominal')
                        ->rupiah()
                        ->required(),
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label('Jumlah Voucher yang dibuat')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(500),
                    \Filament\Forms\Components\DateTimePicker::make('valid_until')
                        ->label('Berlaku Hingga'),
                ])
                ->action(function (array $data): void {
                    $vouchers = [];
                    for ($i = 0; $i < $data['amount']; $i++) {
                        $code = $data['prefix'] . strtoupper(\Illuminate\Support\Str::random(8));
                        $vouchers[] = [
                            'code' => $code,
                            'name' => $data['name'] ?? null,
                            'nominal_value' => (float) str_replace('.', '', $data['nominal_value']),
                            'valid_until' => $data['valid_until'],
                            'is_used' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    \App\Models\Voucher::insert($vouchers);
                    \Filament\Notifications\Notification::make()
                        ->title('Voucher berhasil dibuat!')
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
