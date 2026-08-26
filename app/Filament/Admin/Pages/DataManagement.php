<?php

namespace App\Filament\Admin\Pages;

use App\Models\ActivityLog;
use App\Models\ChatSession;
use App\Models\QuoteRequest;
use App\Models\Referral;
use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

/**
 * Port of core/data_admin.py — exclusive to genuine Admin accounts
 * (is_data_admin() in Django: superuser AND profile.position == 'admin').
 * Two jobs: full JSON backup download, and selective "clear old data".
 */
class DataManagement extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Backup & Clear Data';

    protected string $view = 'filament.admin.pages.data-management';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdminPosition() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(['older_than' => 'all']);
    }

    /** Mirrors Django's _clearable_map(). */
    protected function clearableMap(): array
    {
        return [
            'contact' => ['label' => 'Contact Us Submissions', 'query' => fn ($cutoff) => QuoteRequest::query()->where('source', 'contact')->when($cutoff, fn ($q) => $q->where('created_at', '<', $cutoff))],
            'referral' => ['label' => 'Referral Submissions', 'query' => fn ($cutoff) => Referral::query()->when($cutoff, fn ($q) => $q->where('created_at', '<', $cutoff))],
            'quote' => ['label' => 'Request-a-Quote Submissions (home / product / quote)', 'query' => fn ($cutoff) => QuoteRequest::query()->whereIn('source', ['quote', 'home', 'product'])->when($cutoff, fn ($q) => $q->where('created_at', '<', $cutoff))],
            'quickchat' => ['label' => 'Quick Chat Conversations (active and closed)', 'query' => fn ($cutoff) => ChatSession::query()->when($cutoff, fn ($q) => $q->where('created_at', '<', $cutoff))],
            'activitylog' => ['label' => 'Activity Log Entries', 'query' => fn ($cutoff) => ActivityLog::query()->when($cutoff, fn ($q) => $q->where('created_at', '<', $cutoff))],
        ];
    }

    public function counts(): array
    {
        return collect($this->clearableMap())
            ->map(fn ($entry) => [
                'label' => $entry['label'],
                'count' => $entry['query'](null)->count(),
            ])
            ->all();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                CheckboxList::make('data_types')
                    ->label('Data types to clear')
                    ->options(collect($this->clearableMap())->map(fn ($e) => $e['label'])->all())
                    ->columns(2),

                Select::make('older_than')
                    ->label('Clear records older than')
                    ->options([
                        'all' => 'All (regardless of age)',
                        '30' => '30 days',
                        '90' => '90 days',
                        '365' => '365 days',
                        'custom' => 'Custom',
                    ])
                    ->default('all')
                    ->live()
                    ->required(),

                TextInput::make('custom_days')
                    ->label('Custom (days)')
                    ->numeric()
                    ->minValue(1)
                    ->visible(fn ($get) => $get('older_than') === 'custom')
                    ->required(fn ($get) => $get('older_than') === 'custom'),
            ]);
    }

    public function downloadBackup()
    {
        $now = now();

        $quoteRequests = QuoteRequest::query()->whereIn('source', ['quote', 'home', 'product'])->with(['category', 'product'])->get()
            ->map(fn ($q) => $q->only(['id', 'full_name', 'company_name', 'email', 'phone', 'address', 'how_heard', 'estimated_qty', 'status', 'created_at']) + [
                'category' => $q->category?->name,
                'product' => $q->product?->name,
            ]);

        $contactMessages = QuoteRequest::query()->where('source', 'contact')->get()
            ->map(fn ($q) => $q->only(['id', 'full_name', 'company_name', 'email', 'phone', 'address', 'how_heard', 'estimated_qty', 'status', 'created_at']));

        $referrals = Referral::all();

        $activityLog = ActivityLog::with('actor')->get()
            ->map(fn ($log) => [
                'id' => $log->id,
                'actor' => $log->actor?->name,
                'action' => $log->action,
                'created_at' => $log->created_at,
            ]);

        $quickChats = ChatSession::with('messages.staffUser')->get()
            ->map(fn ($session) => [
                'session_token' => $session->session_token,
                'client_name' => $session->client_name,
                'page' => $session->page,
                'is_active' => $session->is_active,
                'created_at' => $session->created_at,
                'last_message_at' => $session->last_message_at,
                'messages' => $session->messages->map(fn ($m) => [
                    'sender' => $m->sender,
                    'staff_user' => $m->staffUser?->name,
                    'message' => $m->message,
                    'is_read' => $m->is_read,
                    'created_at' => $m->created_at,
                ]),
            ]);

        $payload = [
            'generated_at' => $now->toIso8601String(),
            'generated_by' => Auth::user()?->name,
            'quote_requests' => $quoteRequests,
            'contact_messages' => $contactMessages,
            'referrals' => $referrals,
            'quick_chats' => $quickChats,
            'activity_log' => $activityLog,
        ];

        ActivityLog::log(Auth::user(), 'Downloaded full data backup');

        $filename = 'tdt_powersteel_backup_'.$now->format('Ymd_His').'.json';

        return response()->streamDownload(
            fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            $filename,
            ['Content-Type' => 'application/json']
        );
    }

    public function clearData(): void
    {
        $state = $this->form->getState();
        $selected = $state['data_types'] ?? [];

        if (empty($selected)) {
            Notification::make()->title('No data types were selected to clear.')->warning()->send();

            return;
        }

        $olderThan = $state['older_than'] ?? 'all';
        $cutoff = null;

        if ($olderThan === 'custom') {
            $days = (int) ($state['custom_days'] ?? 0);
            if ($days <= 0) {
                Notification::make()->title("Please enter a valid number of days for 'Custom'.")->danger()->send();

                return;
            }
            $cutoff = Carbon::now()->subDays($days);
        } elseif ($olderThan !== 'all') {
            $cutoff = Carbon::now()->subDays((int) $olderThan);
        }

        $map = $this->clearableMap();
        $summary = [];

        foreach ($selected as $key) {
            if (! isset($map[$key])) {
                continue;
            }

            $query = $map[$key]['query']($cutoff);
            $count = $query->count();
            $query->delete();
            $summary[] = "{$map[$key]['label']}: {$count}";
        }

        $ageLabel = $cutoff === null ? 'all (regardless of age)' : "older than {$olderThan} days";
        ActivityLog::log(Auth::user(), "Cleared old data ({$ageLabel}) — ".implode('; ', $summary));

        Notification::make()->title('Successfully cleared: '.implode('; ', $summary))->success()->send();
    }

    public function getTitle(): string
    {
        return 'Backup & Clear Data';
    }
}
