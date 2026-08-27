<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\ChatSessions\ChatSessionResource;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\QuoteRequests\QuoteRequestResource;
use App\Filament\Admin\Resources\Referrals\ReferralResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Powers the admin sidebar's "no manual refresh needed" badge counts.
 *
 * Filament computes each resource's getNavigationBadge() on page render
 * only — it doesn't poll on its own. This endpoint is hit every few
 * seconds by a small script (see public/static/admin_live_badges.js,
 * loaded via AdminPanelProvider's render hook) so a staff member sitting
 * on one screen still sees new Quote Requests / Referrals / unread chat
 * messages / Contact Messages show up without hitting F5.
 *
 * Reuses each resource's own getNavigationBadge() rather than
 * re-writing the counting query here, so this can never silently drift
 * out of sync with what the sidebar itself would show on a real reload.
 */
class NavBadgeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'chat-sessions' => (int) (ChatSessionResource::getNavigationBadge() ?? 0),
            'contact-messages' => (int) (ContactMessageResource::getNavigationBadge() ?? 0),
            'quote-requests' => (int) (QuoteRequestResource::getNavigationBadge() ?? 0),
            'referrals' => (int) (ReferralResource::getNavigationBadge() ?? 0),
        ]);
    }
}
