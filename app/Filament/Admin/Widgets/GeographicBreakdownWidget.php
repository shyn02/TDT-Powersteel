<?php

namespace App\Filament\Admin\Widgets;

use App\Models\QuoteRequest;
use App\Models\Referral;
use Filament\Widgets\Widget;

class GeographicBreakdownWidget extends Widget
{
    protected static ?int $sort = -8;

    protected string $view = 'filament.admin.widgets.geographic-breakdown-widget';

    protected int|string|array $columnSpan = 'full';

    public function getRegionalData(): array
    {
        $referralsByRegion = Referral::select('region', \DB::raw('count(*) as count'))
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->groupBy('region')
            ->orderByDesc('count')
            ->limit(8)
            ->pluck('count', 'region')
            ->toArray();

        $total = array_sum($referralsByRegion);
        $maxCount = max(1, max($referralsByRegion));

        $regions = [];
        foreach ($referralsByRegion as $region => $count) {
            $regions[] = [
                'name' => $region,
                'count' => $count,
                'percent' => $total > 0 ? round(($count / $total) * 100) : 0,
                'bar_width' => round(($count / $maxCount) * 100),
            ];
        }

        return $regions;
    }

    public function getTotalReferrals(): int
    {
        return Referral::whereNotNull('region')->where('region', '!=', '')->count();
    }

    public function getUniqueRegions(): int
    {
        return Referral::whereNotNull('region')->where('region', '!=', '')->distinct('region')->count('region');
    }
}
