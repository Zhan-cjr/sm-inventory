<?php

namespace App\Filament\Resources\StockOpname;

use App\Models\Branch;
use App\Models\Stock;
use App\Models\StockOpnameRack;
use App\Models\StockOpnameRackSession;
use App\Models\StockOpnameSession;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Traits\HasBranchScope;

class StockOpnameSessionResource extends Resource
{
    use HasBranchScope;

    protected static ?string $model = StockOpnameSession::class;

    protected static \UnitEnum|string|null $navigationGroup = 'STOK OPNAME';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Sesi Opname';
    protected static ?string $modelLabel = 'Sesi Opname';
    protected static ?string $pluralModelLabel = 'Sesi Stok Opname';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Sesi Opname')->columns(2)->schema([
                Select::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->default(fn () => Auth::user()->branch_id)
                    ->disabled(fn () => Auth::user()->branch_id !== null)
                    ->dehydrated(),

                DatePicker::make('opname_date')
                    ->label('Tanggal Opname')
                    ->required()
                    ->default(now()),

                \Filament\Forms\Components\Radio::make('opname_mode')
                    ->label('Mode Opname')
                    ->options([
                        'by_rack' => 'Berdasarkan Rak (Pilih Rak)',
                        'all_items' => 'Semua Barang (Tanpa Rak)'
                    ])
                    ->default('by_rack')
                    ->inline()
                    ->required()
                    ->reactive()
                    ->dehydrated(false)
                    ->columnSpanFull(),

                Select::make('rack_ids')
                    ->label('Rak yang Diikutkan')
                    ->multiple()
                    ->required(fn (callable $get) => $get('opname_mode') === 'by_rack')
                    ->visible(fn (callable $get) => $get('opname_mode') === 'by_rack')
                    ->columnSpanFull()
                    ->options(function (callable $get) {
                        $branchId = $get('branch_id') ?? Auth::user()->branch_id;
                        if (!$branchId) return [];
                        return StockOpnameRack::where('branch_id', $branchId)
                            ->where('is_active', true)
                            ->pluck('rack_name', 'id')
                            ->map(fn ($name, $id) => $name . ' (' . StockOpnameRack::find($id)?->rack_code . ')')
                            ->toArray();
                    })
                    ->helperText('Pilih semua rak yang akan dihitung dalam sesi ini')
                    ->dehydrated(false), // Ditangani manual di CreateRecord

                Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull()
                    ->placeholder('Catatan tambahan untuk sesi ini'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('session_number')
                    ->label('No Sesi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('branch.name')
                    ->label('Cabang')
                    ->searchable(),

                TextColumn::make('opname_date')
                    ->label('Tgl Opname')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DRAFT'       => 'gray',
                        'COUNTING'    => 'info',
                        'CHECKING'    => 'warning',
                        'FINAL_CHECK' => 'danger',
                        'COMPLETED'   => 'success',
                        'CANCELLED'   => 'gray',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'DRAFT'       => 'Draft',
                        'COUNTING'    => 'Sedang Dihitung',
                        'CHECKING'    => 'Sedang Dicek',
                        'FINAL_CHECK' => 'Final Check SPV',
                        'COMPLETED'   => 'Selesai',
                        'CANCELLED'   => 'Dibatalkan',
                        default       => $state,
                    }),

                TextColumn::make('rack_sessions_count')
                    ->counts('rackSessions')
                    ->label('Jml Rak'),

                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'DRAFT'       => 'Draft',
                        'COUNTING'    => 'Sedang Dihitung',
                        'CHECKING'    => 'Sedang Dicek',
                        'FINAL_CHECK' => 'Final Check SPV',
                        'COMPLETED'   => 'Selesai',
                    ]),
                SelectFilter::make('branch_id')
                    ->label('Cabang')
                    ->relationship('branch', 'name')
                    ->searchable()
                    ->preload()
                    ->hidden(fn () => Auth::user()->branch_id !== null),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make()
                    ->visible(fn (StockOpnameSession $record) => $record->status !== 'COMPLETED'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'       => Pages\ListStockOpnameSessions::route('/'),
            'create'      => Pages\CreateStockOpnameSession::route('/create'),
            'view'        => Pages\ViewStockOpnameSession::route('/{record}'),
            'final-check' => Pages\FinalCheckStockOpname::route('/{record}/final-check'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['branch', 'creator', 'rackSessions']);
        $user  = Auth::user();
        if ($user && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        return $query;
    }
}
