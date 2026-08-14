<x-filament-panels::page>
    <x-filament::section heading="Current record counts">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
            @foreach ($this->counts() as $entry)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500">{{ $entry['label'] }}</p>
                    <p class="text-2xl font-semibold">{{ $entry['count'] }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section heading="Backup" class="mt-6">
        <x-filament::button wire:click="downloadBackup" icon="heroicon-o-arrow-down-tray">
            Download Full Backup (JSON)
        </x-filament::button>
    </x-filament::section>

    <x-filament::section heading="Clear old data" class="mt-6">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button wire:click="clearData" color="danger">
                Clear Selected Data
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
