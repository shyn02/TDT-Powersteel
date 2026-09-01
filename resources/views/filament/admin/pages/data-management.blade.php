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
        <p class="text-sm text-gray-500 mb-3">Selected records will be <strong>archived</strong> (soft-deleted) and hide from normal lists. Find them via each table’s <em>Archived (30-day)</em> filter → <em>Only Trashed</em>. They auto-delete after 30 days at 02:00.</p>
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button wire:click="clearData" color="danger">
                Archive Selected Data
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section heading="Restore archived data (within 30 days)" class="mt-6">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 mb-4">
            @foreach ($this->archivedCounts() as $entry)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                    <p class="text-sm text-gray-500">{{ $entry['label'] }} — archived</p>
                    <p class="text-2xl font-semibold">{{ $entry['count'] }}</p>
                </div>
            @endforeach
        </div>
        <p class="text-sm text-gray-500 mb-3">Restores all archived records of the selected types that are still within the 30-day window. Permanently deleted records cannot be restored.</p>
        <div class="grid grid-cols-2 gap-3 mb-3">
            @foreach (collect($this->clearableMap())->map(fn($e) => $e['label']) as $key => $label)
                <label class="flex items-center gap-2 rounded-lg border p-2 hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" wire:model="restoreState.restore_types" value="{{ $key }}" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm">{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mb-2">Only records archived in the last 30 days can be restored. After 30 days they are permanently deleted by the daily 02:00 prune.</p>

        <div class="mt-4 flex gap-2">
            <x-filament::button wire:click="restoreData" color="success" icon="heroicon-o-arrow-path">
                Restore Selected Data
            </x-filament::button>
            <x-filament::button wire:click="restoreAll" color="gray" icon="heroicon-o-arrow-path">
                Restore All Archived
            </x-filament::button>
            <span class="text-xs text-gray-400 self-center">Or use each table’s <em>Archived → Only Trashed → Restore</em> for per-record restore.</span>
        </div>
    </x-filament::section>
</x-filament-panels::page>