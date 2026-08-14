<x-filament-widgets::widget>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Quote Requests by Status --}}
        <x-filament::section heading="Quote Requests by Status">
            <div class="space-y-4">
                @foreach ($this->quoteStatusBreakdown() as $row)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-950 dark:text-white">{{ $row['label'] }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ $row['count'] }} ({{ $row['percent'] }}%)</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-2 rounded-full {{ $row['color'] }}" style="width: {{ $row['percent'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Recent Quote Requests --}}
        <x-filament::section heading="Recent Quote Requests">
            <div class="space-y-3">
                @forelse ($this->recentQuotes() as $quote)
                    <a href="{{ $this->quoteRequestUrl($quote) }}" class="block rounded-lg px-2 py-1.5 -mx-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $quote->full_name }}@if($quote->company_name) ({{ $quote->company_name }})@endif</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $quote->category?->name ?? '—' }} &middot; {{ $quote->created_at?->diffForHumans() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No quote requests yet.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Recent Referrals --}}
        <x-filament::section heading="Recent Referrals">
            <div class="space-y-3">
                @forelse ($this->recentReferrals() as $referral)
                    <a href="{{ $this->referralUrl($referral) }}" class="block rounded-lg px-2 py-1.5 -mx-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $referral->referrer_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $referral->referred_company ?: '—' }} &middot; {{ $referral->created_at?->diffForHumans() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No referrals yet.</p>
                @endforelse
            </div>
        </x-filament::section>

    </div>

    {{-- Recent Contact Messages --}}
    <x-filament::section heading="Recent Contact Messages" class="mt-4">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->recentContactMessages() as $message)
                <a href="{{ $this->contactMessageUrl($message) }}" class="block rounded-lg border border-gray-100 px-3 py-2 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800">
                    <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $message->full_name }}</p>
                    <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $message->message }}</p>
                </a>
            @empty
                <p class="text-sm text-gray-400">No contact messages yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
