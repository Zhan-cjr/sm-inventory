<?php

namespace App\Filament\Resources\PosDevices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

class PosDevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Perangkat')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('device_uuid')
                    ->label('Device UUID')
                    ->searchable()
                    ->copyable()
                    ->description(fn ($record) => $record->user_agent, position: 'below')
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('branch.name')
                    ->label('Cabang Terkunci')
                    ->placeholder('Belum Dikunci')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('terminal.name')
                    ->label('Terminal POS')
                    ->placeholder('Belum Dikunci')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'APPROVED' => 'success',
                        'BLOCKED' => 'danger',
                        'PENDING' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'APPROVED' => 'Disetujui',
                        'BLOCKED' => 'Diblokir',
                        'PENDING' => 'Menunggu Otorisasi',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('approved_at')
                    ->label('Disetujui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('blocked_at')
                    ->label('Diblokir')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Tanggal Daftar')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Otorisasi')
                    ->options([
                        'PENDING' => 'Menunggu Persetujuan',
                        'APPROVED' => 'Disetujui',
                        'BLOCKED' => 'Diblokir',
                    ]),

                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name'),
            ])
            ->actions([
                // Quick Action: Approve
                Action::make('approve_device')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->hidden(fn ($record) => $record->status === 'APPROVED')
                    ->form([
                        Select::make('branch_id')
                            ->label('Cabang (Branch)')
                            ->relationship('branch', 'name')
                            ->placeholder('Pilih Cabang Terkunci')
                            ->required()
                            ->live(),

                        Select::make('terminal_id')
                            ->label('Terminal POS')
                            ->placeholder('Pilih Terminal POS Terkunci')
                            ->options(function ($get) {
                                $branchId = $get('branch_id');
                                if (!$branchId) {
                                    return [];
                                }
                                return \App\Models\Terminal::where('branch_id', $branchId)->pluck('name', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'APPROVED',
                            'branch_id' => $data['branch_id'],
                            'terminal_id' => $data['terminal_id'],
                            'approved_at' => now(),
                            'blocked_at' => null,
                        ]);

                        Notification::make()
                            ->title('Perangkat Berhasil Disetujui!')
                            ->body("Perangkat '{$record->name}' telah dikunci pada Cabang & Terminal terpilih.")
                            ->success()
                            ->send();
                    }),

                // Quick Action: Block
                Action::make('block_device')
                    ->label('Blokir')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->hidden(fn ($record) => $record->status === 'BLOCKED')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'BLOCKED',
                            'blocked_at' => now(),
                            'approved_at' => null,
                        ]);

                        Notification::make()
                            ->title('Perangkat Berhasil Diblokir!')
                            ->body("Akses untuk perangkat '{$record->name}' telah diputus.")
                            ->danger()
                            ->send();
                    }),

                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
