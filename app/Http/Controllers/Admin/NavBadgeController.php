<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Admin\Resources\ChatSessions\ChatSessionResource;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\QuoteRequests\QuoteRequestResource;
use App\Filament\Admin\Resources\Referrals\ReferralResource;
use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\Referral;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

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
 *
 * SEC-09: Gates each badge by the underlying policy (viewAny) so a user
 * who cannot view a resource does not learn its count via this endpoint.
 */
class NavBadgeController extends Controller
{
    public function index(): JsonResponse
    {
        $data = [];

        // SEC-09: Only return badges the current user is authorized to view
        if (Gate::allows('viewAny', ChatSession::class)) {
            $data['chat-sessions'] = (int) (ChatSessionResource::getNavigationBadge() ?? 0);
        }
        if (Gate::allows('viewAny', ContactMessage::class)) {
            $data['contact-messages'] = (int) (ContactMessageResource::getNavigationBadge() ?? 0);
        }
        if (Gate::allows('viewAny', QuoteRequest::class)) {
            $data['quote-requests'] = (int) (QuoteRequestResource::getNavigationBadge() ?? 0);
        }
        if (Gate::allows('viewAny', Referral::class)) {
            $data['referrals'] = (int) (ReferralResource::getNavigationBadge() ?? 0);
        }

        return response()->json($data);
    }
}
