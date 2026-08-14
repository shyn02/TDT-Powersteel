<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\ChatSessions\ChatSessionResource;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\Products\ProductResource;
use App\Filament\Admin\Resources\QuoteRequests\QuoteRequestResource;
use App\Filament\Admin\Resources\Referrals\ReferralResource;
use App\Models\ChatMessage;
use App\Models\ContactMessage;
use App\Models\Product;
use App\Models\QuoteRequest;
use App\Models\Referral;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

/**
 * Port of Django's dashboard_callback() KPI cards. One aggregated query
 * per model rather than a .count() per status, same reasoning as the
 * Django version — keeps this cheap as the tables grow.
 */
class DashboardKpiWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -20;

    protected function getStats(): array
    {
        $newQuotes = QuoteRequest::where('status', 'new')->count();
        $newReferrals = Referral::where('status', 'new')->count();
        $unreadContactMessages = ContactMessage::where('is_seen', false)->count();
        $unreadChats = ChatMessage::where('sender', 'client')->where('is_read', false)->count();

        $stats = [
            Stat::make('New Quote Requests', $newQuotes)
                ->description('Pending quote inquiries')
                ->color('warning')
                ->url(QuoteRequestResource::getUrl('index')),

            Stat::make('New Referrals', $newReferrals)
                ->description('Submissions requiring follow-up')
                ->color('info')
                ->url(ReferralResource::getUrl('index')),
        ];

        // Product Categories/Products are admin-only, so this card is
        // dropped for anyone who isn't an actual Admin.
        if (Auth::user()?->isAdminPosition()) {
            $stats[] = Stat::make('Active Products', Product::where('is_active', true)->count())
                ->description('Products listed on website')
                ->color('success')
                ->url(ProductResource::getUrl('index'));
        }

        $stats[] = Stat::make('Unread Contact Messages', $unreadContactMessages)
            ->description('Waiting for a reply')
            ->color('danger')
            ->url(ContactMessageResource::getUrl('index'));

        $stats[] = Stat::make('Unread Chat Messages', $unreadChats)
            ->description('Waiting for a reply')
            ->color('gray')
            ->url(ChatSessionResource::getUrl('index'));

        return $stats;
    }
}
