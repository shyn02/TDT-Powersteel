<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\QuoteRequests\QuoteRequestResource;
use App\Filament\Admin\Resources\Referrals\ReferralResource;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\Referral;
use Filament\Widgets\Widget;

/**
 * Port of the Django dashboard's "Quote Requests by Status" progress bars
 * plus the three "Recent …" columns (quotes / referrals / contact
 * messages) — the part of dashboard_callback() that isn't a simple KPI
 * number.
 */
class DashboardOverviewWidget extends Widget
{
    protected static ?int $sort = -10;

    protected string $view = 'filament.admin.widgets.dashboard-overview-widget';

    protected int|string|array $columnSpan = 'full';

    public function quoteStatusBreakdown(): array
    {
        $newCount = QuoteRequest::where('status', 'new')->count();
        $contactedCount = QuoteRequest::where('status', 'contacted')->count();
        $closedCount = QuoteRequest::where('status', 'closed')->count();
        $total = $newCount + $contactedCount + $closedCount;

        $pct = fn (int $count) => $total ? round(($count / $total) * 100) : 0;

        return [
            ['label' => 'New', 'count' => $newCount, 'percent' => $pct($newCount), 'color' => 'bg-warning-500'],
            ['label' => 'Contacted', 'count' => $contactedCount, 'percent' => $pct($contactedCount), 'color' => 'bg-info-500'],
            ['label' => 'Closed', 'count' => $closedCount, 'percent' => $pct($closedCount), 'color' => 'bg-success-500'],
        ];
    }

    public function recentQuotes()
    {
        return QuoteRequest::with('category')->latest('created_at')->limit(5)->get();
    }

    public function recentReferrals()
    {
        return Referral::latest('created_at')->limit(5)->get();
    }

    public function recentContactMessages()
    {
        return ContactMessage::latest('created_at')->limit(5)->get();
    }

    public function quoteRequestUrl($record): string
    {
        return QuoteRequestResource::getUrl('view', ['record' => $record]);
    }

    public function referralUrl($record): string
    {
        return ReferralResource::getUrl('view', ['record' => $record]);
    }

    public function contactMessageUrl($record): string
    {
        return ContactMessageResource::getUrl('view', ['record' => $record]);
    }
}
