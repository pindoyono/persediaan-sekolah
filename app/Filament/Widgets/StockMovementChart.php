<?php

namespace App\Filament\Widgets;

use App\Models\StockMovement;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class StockMovementChart extends ChartWidget
{
    protected ?string $heading = 'Pergerakan Stok Harian (IN vs OUT)';
    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $data = Cache::remember('chart_stock_movement_7days', 300, function () {
            $days = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->toDateString());

            $movements = StockMovement::query()
                ->selectRaw('DATE(created_at) as date, type, SUM(qty) as total')
                ->whereDate('created_at', '>=', now()->subDays(6))
                ->groupBy('date', 'type')
                ->get()
                ->groupBy('date');

            $inData  = [];
            $outData = [];
            $labels  = [];

            foreach ($days as $day) {
                $labels[]  = \Carbon\Carbon::parse($day)->format('d/m');
                $dayData   = $movements->get($day, collect());
                $inData[]  = (int) $dayData->where('type', 'IN')->sum('total');
                $outData[] = (int) $dayData->where('type', 'OUT')->sum('total');
            }

            return compact('labels', 'inData', 'outData');
        });

        return [
            'datasets' => [
                [
                    'label'           => 'Masuk (IN)',
                    'data'            => $data['inData'],
                    'borderColor'     => '#16a34a',
                    'backgroundColor' => 'rgba(22,163,74,0.15)',
                    'fill'            => true,
                ],
                [
                    'label'           => 'Keluar (OUT)',
                    'data'            => $data['outData'],
                    'borderColor'     => '#dc2626',
                    'backgroundColor' => 'rgba(220,38,38,0.15)',
                    'fill'            => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
