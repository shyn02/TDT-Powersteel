<?php

namespace App\Filament\Admin\Widgets;

use App\Models\QuoteRequest;
use Filament\Widgets\Widget;

class QuoteStatusWidget extends Widget
{
    protected static ?int $sort = -20;

    protected string $view = 'filament.admin.widgets.quote-status-widget';

    protected int|string|array $columnSpan = 'full';

    public function getData(): array
    {
        $new = QuoteRequest::where('status', 'new')->count();
        $contacted = QuoteRequest::where('status', 'contacted')->count();
        $closed = QuoteRequest::where('status', 'closed')->count();
        $total = $new + $contacted + $closed;

        $pct = fn (int $count) => $total ? round(($count / $total) * 100) : 0;

        return [
            'total' => $total,
            'segments' => [
                ['label' => 'New',        'count' => $new,        'pct' => $pct($new),        'color' => '#f59e0b'],
                ['label' => 'Contacted',  'count' => $contacted,  'pct' => $pct($contacted),  'color' => '#0ea5e9'],
                ['label' => 'Closed',     'count' => $closed,     'pct' => $pct($closed),     'color' => '#10b981'],
            ],
        ];
    }
}
