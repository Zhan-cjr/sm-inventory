<?php

namespace App\Filament\Resources\JournalEntries\Pages;

use App\Filament\Resources\JournalEntries\JournalEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJournalEntries extends ListRecords
{
    protected static string $resource = JournalEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('cetak_jurnal')
                ->label('Cetak Jurnal Umum')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('start_date')
                        ->label('Dari Tanggal')
                        ->required()
                        ->default(now()->startOfMonth()),
                    \Filament\Forms\Components\DatePicker::make('end_date')
                        ->label('Sampai Tanggal')
                        ->required()
                        ->default(now()->endOfMonth()),
                    \Filament\Forms\Components\Select::make('branch_id')
                        ->label('Cabang (Opsional)')
                        ->options(\App\Models\Branch::pluck('name', 'id')),
                ])
                ->action(function (array $data, \Filament\Actions\Action $action) {
                    $url = route('print.report', [
                        'type' => 'jurnal_umum',
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'branch_id' => $data['branch_id'],
                    ]);
                    $action->livewire->js("window.open('{$url}', '_blank');");
                }),
            CreateAction::make(),
        ];
    }
}
