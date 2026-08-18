<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Project;
use App\Models\QuoteRequest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenuePipelineWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -19;

    protected function getStats(): array
    {
        $totalValue = Project::sum('value');
        $activeProjects = Project::where('status', 'active')->count();
        $totalProjects = Project::count();
        $avgProjectValue = $totalProjects > 0 ? $totalValue / $totalProjects : 0;
        $pendingQuotes = QuoteRequest::where('status', 'new')->count();

        return [
            Stat::make('Total Project Value', '₱' . number_format($totalValue, 2))
                ->description('Across all tracked projects')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Active Projects', $activeProjects)
                ->description($totalProjects . ' total projects')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Avg Project Value', '₱' . number_format($avgProjectValue, 2))
                ->description('Per project average')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('warning'),

            Stat::make('Pending Quotes', $pendingQuotes)
                ->description('Awaiting first response')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}
