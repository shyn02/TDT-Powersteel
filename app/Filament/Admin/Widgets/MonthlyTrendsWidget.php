<?php

namespace App\Filament\Admin\Widgets;

use App\Models\QuoteRequest;
use App\Models\Referral;
use App\Models\ContactMessage;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class MonthlyTrendsWidget extends Widget
{
    protected static ?int $sort = -15;

    protected string $view = 'filament.admin.widgets.monthly-trends-widget';

    protected int|string|array $columnSpan = 'full';

    public function getMonthlyData(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months->push([
                'label' => $date->format('M'),
                'quotes' => QuoteRequest::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'referrals' => Referral::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'contacts' => ContactMessage::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ]);
        }

        return $months->toArray();
    }

    public function getMaxCount(): int
    {
        $data = $this->getMonthlyData();
        $max = 1;
        foreach ($data as $month) {
            $max = max($max, $month['quotes'], $month['referrals'], $month['contacts']);
        }
        return $max;
    }
}
