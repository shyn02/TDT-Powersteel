<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\QuoteRequests\QuoteRequestResource;
use App\Filament\Admin\Resources\Referrals\ReferralResource;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\Referral;
use Filament\Actions\Action;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static ?int $sort = -1;

    protected string $view = 'filament.admin.widgets.quick-actions-widget';

    protected int|string|array $columnSpan = 'full';

    public function getActionCounts(): array
    {
        return [
            'new_quotes' => QuoteRequest::where('status', 'new')->count(),
            'new_referrals' => Referral::where('status', 'new')->count(),
            'unread_contacts' => ContactMessage::where('is_seen', false)->count(),
        ];
    }

    public function getQuoteUrl(): string
    {
        return QuoteRequestResource::getUrl('index');
    }

    public function getReferralUrl(): string
    {
        return ReferralResource::getUrl('index');
    }

    public function getContactUrl(): string
    {
        return ContactMessageResource::getUrl('index');
    }
}
