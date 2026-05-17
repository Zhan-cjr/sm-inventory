<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Tren Penjualan (7 Hari Terakhir)';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = [];
        $labels = [];
        $branchId = auth()->user()->branch_id ?? $this->filters['branch_id'] ?? null;

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d M');
            $data[] = Transaction::whereDate('created_at', $date->toDateString())
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->sum('total_amount');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Penjualan (Rp)',
                    'data' => $data,
                    'fill' => 'start',
                    'borderColor' => 'rgb(79, 70, 229)', // Updated to match indigo
                    'backgroundColor' => 'rgba(79, 70, 229, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
