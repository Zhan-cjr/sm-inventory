<?php

namespace App\Filament\Filters;

use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;


class DateFilterHelper
{
    public static function make(string $column = 'transaction_date', string $filterName = 'date_filter'): Filter
    {
        return Filter::make($filterName)
            ->form([
                Select::make('period')
                    ->label('Periode Tanggal')
                    ->options([
                        'today' => 'Hari Ini',
                        'yesterday' => 'Kemarin',
                        'this_week' => 'Minggu Ini',
                        'last_week' => 'Minggu Kemarin',
                        'this_month' => 'Bulan Ini',
                        'last_month' => 'Bulan Kemarin',
                        'custom' => 'Custom Pilih Tanggal',
                    ])
                    ->default('today')
                    ->live(),
                DatePicker::make('created_from')
                    ->label('Dari Tanggal')
                    ->visible(fn ($get) => $get('period') === 'custom'),
                DatePicker::make('created_until')
                    ->label('Sampai Tanggal')
                    ->visible(fn ($get) => $get('period') === 'custom'),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                $period = $data['period'] ?? null;

                $applyClause = function($q, $period, $col, $data) {
                    if ($period === 'today') {
                        return $q->whereDate($col, Carbon::today());
                    } elseif ($period === 'yesterday') {
                        return $q->whereDate($col, Carbon::yesterday());
                    } elseif ($period === 'this_week') {
                        return $q->whereBetween($col, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    } elseif ($period === 'last_week') {
                        return $q->whereBetween($col, [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()]);
                    } elseif ($period === 'this_month') {
                        return $q->whereMonth($col, Carbon::now()->month)->whereYear($col, Carbon::now()->year);
                    } elseif ($period === 'last_month') {
                        return $q->whereMonth($col, Carbon::now()->subMonth()->month)->whereYear($col, Carbon::now()->subMonth()->year);
                    } elseif ($period === 'custom') {
                        return $q
                            ->when(
                                $data['created_from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate($col, '>=', $date),
                            )
                            ->when(
                                $data['created_until'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate($col, '<=', $date),
                            );
                    }
                    return $q;
                };

                if (str_contains($column, '.')) {
                    $parts = explode('.', $column);
                    $relColumn = array_pop($parts);
                    $relation = implode('.', $parts);
                    return $query->whereHas($relation, function($q) use ($applyClause, $period, $relColumn, $data) {
                        return $applyClause($q, $period, $relColumn, $data);
                    });
                } else {
                    return $applyClause($query, $period, $column, $data);
                }
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['period'] ?? null) {
                    $labels = [
                        'today' => 'Hari Ini',
                        'yesterday' => 'Kemarin',
                        'this_week' => 'Minggu Ini',
                        'last_week' => 'Minggu Kemarin',
                        'this_month' => 'Bulan Ini',
                        'last_month' => 'Bulan Kemarin',
                        'custom' => 'Custom Tanggal',
                    ];
                    
                    if ($data['period'] !== 'custom') {
                        $indicators[] = 'Periode: ' . ($labels[$data['period']] ?? '');
                    } else {
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Dari: ' . Carbon::parse($data['created_from'])->format('d M Y');
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Sampai: ' . Carbon::parse($data['created_until'])->format('d M Y');
                        }
                    }
                }
                return $indicators;
            });
    }
}
