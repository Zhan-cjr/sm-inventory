<?php

namespace App\Filament\Resources\Suppliers\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use App\Models\SupplierDivision;
use Illuminate\Support\Facades\Auth;

class DivisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'divisions';

    protected static ?string $title = 'Sub-Divisi Supplier';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Divisi')
                    ->placeholder('Contoh: Divisi Food, Divisi Snack, Divisi Minuman')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_person')
                    ->label('Kontak Person / Salesman')
                    ->placeholder('Nama Salesman / KAM')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('No. Telepon / WA')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\TextInput::make('email')
                    ->label('Email Divisi')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan Operasional')
                    ->placeholder('Misal: Jadwal kunjungan, ketentuan khusus divisi')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordAction('view_products')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Divisi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Total Produk')
                    ->state(fn ($record) => $record->products()->count())
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Cabang Pemilik')
                    ->placeholder('Pusat (Semua)')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Kontak Person / Sales')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telepon / WA')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Sub-Divisi')
                    ->modalHeading('Tambah Sub-Divisi Supplier')
                    ->modalWidth('lg')
                    ->mutateFormDataUsing(function (array $data): array {
                        $user = Auth::user();
                        if ($user) {
                            $data['created_by'] = $user->id;
                            $data['branch_id'] = $user->branch_id;
                        }
                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('view_products')
                    ->label('Lihat Produk')
                    ->icon('heroicon-o-shopping-bag')
                    ->color('primary')
                    ->modalWidth('7xl')
                    ->modalHeading(fn (SupplierDivision $record) => "Daftar Produk Sub-Divisi: {$record->name}")
                    ->modalDescription(fn (SupplierDivision $record) => "Supplier: {$record->supplier?->name}")
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn (SupplierDivision $record) => view('filament.suppliers.division-products-modal', [
                        'division' => $record,
                    ])),

                EditAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        if ($user->branch_id === null) return true; // Superadmin / Pusat boleh edit
                        return !$record->branch_id || $record->branch_id === $user->branch_id;
                    }),

                DeleteAction::make()
                    ->visible(function ($record) {
                        $user = Auth::user();
                        if (!$user) return false;
                        // Tidak bisa dihapus jika masih ada barang
                        if ($record->products()->exists()) {
                            return false;
                        }
                        // Jika user cabang, tidak boleh hapus sub-divisi cabang lain
                        if ($user->branch_id !== null && $record->branch_id && $record->branch_id !== $user->branch_id) {
                            return false;
                        }
                        return true;
                    })
                    ->disabled(fn ($record) => $record->products()->exists()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records, DeleteBulkAction $action) {
                            $user = Auth::user();
                            foreach ($records as $record) {
                                if ($record->products()->exists()) {
                                    Notification::make()
                                        ->title('Gagal Hapus Sub-Divisi')
                                        ->body("Sub-divisi '{$record->name}' tidak dapat dihapus karena masih memiliki barang terdaftar.")
                                        ->danger()
                                        ->send();
                                    $action->cancel();
                                    return;
                                }
                                if ($user && $user->branch_id !== null && $record->branch_id && $record->branch_id !== $user->branch_id) {
                                    Notification::make()
                                        ->title('Akses Ditolak')
                                        ->body("Sub-divisi '{$record->name}' dibuat oleh cabang lain dan tidak dapat dihapus.")
                                        ->danger()
                                        ->send();
                                    $action->cancel();
                                    return;
                                }
                            }
                        }),
                ]),
            ]);
    }
}
