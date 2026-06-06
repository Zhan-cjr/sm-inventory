<?php

namespace App\Filament\Resources\StockAdjustments;

use App\Models\StockAdjustment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Schemas\Components\Html;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use App\Traits\HasBranchScope;
use Filament\Tables\Table;
use App\Filament\Filters\DateFilterHelper;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\Facades\Auth;

class StockAdjustmentResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = StockAdjustment::class;

    protected static \UnitEnum|string|null $navigationGroup = 'TRANSAKSI';

    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Koreksi Stok';
    protected static ?string $modelLabel = 'Koreksi Stok';
    protected static ?string $pluralModelLabel = 'Koreksi Stok';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $recordTitleAttribute = 'adjustment_number';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Transaksi')
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('adjustment_number')
                            ->label('No Transaksi')
                            ->required()
                            ->default(fn () => 'ADJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2))))
                            ->readOnly(),
                        DatePicker::make('adjustment_date')
                            ->label('Tgl Transaksi')
                            ->required()
                            ->default(now()),
                        Select::make('branch_id')
                            ->label('Cabang')
                            ->relationship('branch', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->default(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id ?? \App\Models\Branch::first()?->id)
                            ->disabled(fn() => \Illuminate\Support\Facades\Auth::user()->branch_id !== null)
                            ->dehydrated(),
                        Select::make('adjustment_reason_id')
                            ->label('Alasan / Sifat Transaksi')
                            ->relationship('adjustmentReason', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->columnSpan(2),
                        TextInput::make('notes')
                            ->label('Catatan')
                            ->columnSpan(1),
                        Checkbox::make('cetak_nota')
                            ->label('Cetak Nota setelah simpan')
                            ->dehydrated(false)
                            ->default(false),
                    ]),

                Section::make('Cari & Scan Barang')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'search-section'])
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(12)
                            ->schema([
                                \Filament\Schemas\Components\Html::make('enter-item-label')
                                    ->content('<div class="enter-item-label">ENTER ITEM</div>')
                                    ->columnSpan(2),
                                \Filament\Forms\Components\TextInput::make('search_product')
                                    ->hiddenLabel()
                                    ->placeholder('< SCAN BARCODE ATAU KETIK NAMA BARANG, TEKAN ENTER >')
                                    ->columnSpan(10)
                                    ->extraAttributes([
                                        'id'           => 'search-product-input',
                                        'autocomplete' => 'off',
                                        'data-role'    => 'barcode-input',
                                    ]),
                            ]),
                    ]),

                Section::make('Keranjang Koreksi')
                    ->columnSpanFull()
                    ->schema([
                        Html::make('focus-script')
                            ->content(view('filament.components.stock-focus-script')),
                        Repeater::make('items')
                            ->relationship()
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'cart-repeater'])
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('row_num')
                                    ->hiddenLabel()
                                    ->content('')
                                    ->extraAttributes(['class' => 'row-no']),
                                TextInput::make('sku')
                                    ->hiddenLabel()
                                    ->readOnly(),
                                TextInput::make('barcode')
                                    ->hiddenLabel()
                                    ->readOnly(),
                                TextInput::make('name')
                                    ->hiddenLabel()
                                    ->readOnly(),
                                TextInput::make('previous_quantity')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->readOnly(),
                                TextInput::make('adjustment_quantity')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                        $reasonId = $get('../../adjustment_reason_id');
                                        $reason = \App\Models\AdjustmentReason::find($reasonId);
                                        $multiplier = ($reason && $reason->type === 'MINUS') ? -1 : 1;
                                        $set('new_quantity', (float)$get('previous_quantity') + ((float)$state * $multiplier));
                                    })
                                    ->extraAttributes(['class' => 'qty-input']),
                                TextInput::make('new_quantity')
                                    ->hiddenLabel()
                                    ->numeric()
                                    ->readOnly(),
                                \Filament\Forms\Components\Hidden::make('product_id'),
                            ])
                            ->columns(7)
                            ->defaultItems(0)
                            ->addable(false)
                            ->reorderable(false)
                            ->deletable(true)
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('ID'),
                TextEntry::make('adjustment_number'),
                TextEntry::make('adjustment_date')
                    ->date(),
                TextEntry::make('branch_id'),
                TextEntry::make('notes')
                    ->placeholder('-'),
                TextEntry::make('recorded_by'),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('adjustment_number')
            ->columns([
                TextColumn::make('adjustment_number')
                    ->label('No Transaksi')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('adjustment_date')
                    ->label('Tgl')
                    ->date()
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable(),
                TextColumn::make('adjustmentReason.name')
                    ->label('Sifat')
                    ->searchable(),
                TextColumn::make('total_value_plus')
                    ->label('Nominal (+)')
                    ->state(function ($record) {
                        $type = $record->adjustmentReason ? $record->adjustmentReason->type : '';
                        return strtoupper($type) === 'PLUS' ? $record->total_value : null;
                    })
                    ->numeric()
                    ->sortable(['total_value']),
                TextColumn::make('total_value_minus')
                    ->label('Nominal (-)')
                    ->state(function ($record) {
                        $type = $record->adjustmentReason ? $record->adjustmentReason->type : '';
                        return strtoupper($type) === 'MINUS' ? $record->total_value : null;
                    })
                    ->numeric()
                    ->sortable(['total_value']),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'completed', 'approved' => 'success',
                        'pending', 'draft' => 'gray',
                        'pending_approval' => 'warning',
                        'pending_approval' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('computed_approver_name')
                    ->label('Diperiksa Oleh')
                    ->state(function ($record) {
                        $latestApproval = $record->approvals()->latest()->first();
                        return $latestApproval && $latestApproval->status !== 'pending' 
                            ? $latestApproval->user?->name 
                            : '-';
                    }),
                TextColumn::make('computed_approval_notes')
                    ->label('Catatan Approval')
                    ->state(function ($record) {
                        $latestApproval = $record->approvals()->latest()->first();
                        return $latestApproval && $latestApproval->status !== 'pending' 
                            ? $latestApproval->notes 
                            : '-';
                    })
                    ->limit(50),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateFilterHelper::make('adjustment_date'),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->recordActions([
                Action::make('cetak_nota')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\App\Models\StockAdjustment $record) => route('print.document', ['type' => 'adjustment', 'ids' => [$record->id]]))
                    ->openUrlInNewTab(),
                Action::make('request_approval')
                    ->label('Request Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\StockAdjustment $record) => in_array(strtolower($record->status), ['pending', 'draft', 'rejected']))
                    ->action(fn (\App\Models\StockAdjustment $record) => $record->requestApproval()),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\StockAdjustment $record) => strtolower($record->status) === 'pending_approval' && auth()->user()->hasCustomAuthorization('APPROVE_STOCK_ADJUSTMENT'))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('notes')->label('Catatan (Opsional)')
                    ])
                    ->action(fn (\App\Models\StockAdjustment $record, array $data) => $record->approve(auth()->id(), $data['notes'] ?? null)),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (\App\Models\StockAdjustment $record) => strtolower($record->status) === 'pending_approval' && auth()->user()->hasCustomAuthorization('APPROVE_STOCK_ADJUSTMENT'))
                    ->form([
                        \Filament\Forms\Components\Textarea::make('notes')->label('Alasan Penolakan')->required()
                    ])
                    ->action(fn (\App\Models\StockAdjustment $record, array $data) => $record->reject(auth()->id(), $data['notes'])),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('cetak_nota_massal')
                        ->label('Cetak Nota')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->action(function (Collection $records) {
                            $ids = $records->pluck('id')->toArray();
                            if (empty($ids)) return;
                        })
                        ->url(fn (Collection $records) => route('print.document', ['type' => 'adjustment', 'ids' => $records->pluck('id')->toArray()]))
                        ->openUrlInNewTab()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('cetak_daftar')
                    ->label('Cetak Daftar')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (\Filament\Tables\Contracts\HasTable $livewire) => route('print.report', [
                        'type' => 'koreksi-stok',
                        'tableFilters' => $livewire->tableFilters
                    ]), true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockAdjustments::route('/'),
            'create' => Pages\CreateStockAdjustment::route('/create'),
            'edit' => Pages\EditStockAdjustment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['branch']);
        
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }
}
