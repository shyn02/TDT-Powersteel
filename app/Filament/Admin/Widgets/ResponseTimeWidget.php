<?php

namespace App\Filament\Admin\Widgets;

use App\Models\QuoteRequest;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Filament\Widgets\Widget;

class ResponseTimeWidget extends Widget
{
    protected static ?int $sort = -5;

    protected string $view = 'filament.admin.widgets.response-time-widget';

    protected int|string|array $columnSpan = 'full';

    public function getQuoteAgingData(): array
    {
        $now = now();

        $newQuotes = QuoteRequest::where('status', 'new')->get();

        $buckets = [
            ['label' => '< 24 hours', 'count' => 0, 'color' => 'bg-emerald-500'],
            ['label' => '1-3 days', 'count' => 0, 'color' => 'bg-yellow-500'],
            ['label' => '3-7 days', 'count' => 0, 'color' => 'bg-orange-500'],
            ['label' => '7+ days', 'count' => 0, 'color' => 'bg-red-500'],
        ];

        foreach ($newQuotes as $quote) {
            $hours = $quote->created_at->diffInHours($now);
            if ($hours < 24) {
                $buckets[0]['count']++;
            } elseif ($hours < 72) {
                $buckets[1]['count']++;
            } elseif ($hours < 168) {
                $buckets[2]['count']++;
            } else {
                $buckets[3]['count']++;
            }
        }

        $total = max(1, count($newQuotes));
        foreach ($buckets as &$bucket) {
            $bucket['percent'] = round(($bucket['count'] / $total) * 100);
        }

        return $buckets;
    }

    public function getAvgChatResponseTime(): string
    {
        $sessions = ChatSession::where('assigned_to', '!=', null)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        if ($sessions->isEmpty()) {
            return 'N/A';
        }

        $totalMinutes = 0;
        $count = 0;

        foreach ($sessions as $session) {
            $firstStaffMsg = ChatMessage::where('session_id', $session->id)
                ->where('sender', 'staff')
                ->orderBy('created_at')
                ->first();

            if ($firstStaffMsg) {
                $diff = $session->created_at->diffInMinutes($firstStaffMsg->created_at);
                $totalMinutes += $diff;
                $count++;
            }
        }

        if ($count === 0) {
            return 'N/A';
        }

        $avgMinutes = $totalMinutes / $count;

        if ($avgMinutes < 60) {
            return round($avgMinutes) . ' min';
        }

        $hours = floor($avgMinutes / 60);
        $mins = round($avgMinutes % 60);
        return $hours . 'h ' . $mins . 'm';
    }

    public function getUnresolvedTickets(): int
    {
        return QuoteRequest::where('status', 'new')
            ->where('created_at', '<', now()->subDays(7))
            ->count();
    }
}
