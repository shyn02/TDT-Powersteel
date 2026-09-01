<x-filament-panels::page>
    <x-filament::section heading="Current record counts">
        <div class="tdt-data-grid">
            @foreach ($this->counts() as $entry)
                <div class="tdt-stat-card">
                    <p class="tdt-stat-label">{{ $entry['label'] }}</p>
                    <p class="tdt-stat-number">{{ $entry['count'] }}</p>
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
        <div class="tdt-data-grid">
            @foreach ($this->archivedCounts() as $entry)
                <div class="tdt-stat-card tdt-stat-card-amber">
                    <p class="tdt-stat-label">{{ $entry['label'] }} — archived</p>
                    <p class="tdt-stat-number">{{ $entry['count'] }}</p>
                </div>
            @endforeach
        </div>
        <p class="text-sm text-gray-500 mb-3">Restores all archived records of the selected types that are still within the 30-day window. Permanently deleted records cannot be restored.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
            @foreach (collect($this->clearableMap())->map(fn($e) => $e['label']) as $key => $label)
                <label class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 hover:bg-gray-50 cursor-pointer transition">
                    <input type="checkbox" wire:model.live="restoreData.restore_types" value="{{ $key }}" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500 h-4 w-4">
                    <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
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