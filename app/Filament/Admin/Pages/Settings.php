<?php

namespace App\Filament\Admin\Pages;

use App\Models\ActivityLog;
use App\Models\ChatSession;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\Referral;
use App\Models\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Port of core/site_settings.py's settings_view() + the six tabs it
 * renders (General, Security, Notifications, Regional, System Info,
 * Tools). Admin-only, same as Django's @superadmin_required.
 */
class Settings extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected string $view = 'filament.admin.pages.settings';

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->isAdminPosition() ?? false;
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->isAdminPosition() ?? false;
    }

    /**
     * ActivityLog::log() expects our App\Models\User specifically, while
     * Auth::user()/auth()->user() are typed generically as Authenticatable
     * by Laravel — this centralizes the (safe, always-correct-at-runtime)
     * cast in one place instead of repeating a docblock at every call site.
     */
    protected function currentUser(): ?\App\Models\User
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user;
    }

    public function mount(): void
    {
        $this->form->fill(SiteSettings::current()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tab::make('General')
                            ->schema([
                                TextInput::make('site_name')->required(),
                                TextInput::make('support_email')->email(),
                                TextInput::make('support_phone'),
                                TextInput::make('company_address'),
                            ])
                            ->columns(2),

                        Tab::make('Security')
                            ->schema([
                                TextInput::make('session_timeout_minutes')->numeric()->minValue(1)->required(),
                                TextInput::make('max_login_attempts')->numeric()->minValue(1)->required(),
                                TextInput::make('lockout_minutes')->numeric()->minValue(1)->required(),
                                Toggle::make('require_strong_passwords'),
                            ])
                            ->columns(2),

                        Tab::make('Notifications')
                            ->schema([
                                Toggle::make('notify_new_quote')->label('Notify on new quote request'),
                                Toggle::make('notify_new_referral')->label('Notify on new referral'),
                                Toggle::make('notify_new_chat')->label('Notify on new chat message'),
                                TextInput::make('notification_email')->email(),
                            ])
                            ->columns(2),

                        Tab::make('Regional')
                            ->schema([
                                TextInput::make('timezone_name')->required(),
                                TextInput::make('currency')->required(),
                                TextInput::make('date_format')->required(),
                            ])
                            ->columns(3),

                        Tab::make('System Info')
                            ->schema(
                                collect($this->systemInfo())
                                    ->map(fn ($value, $label) => Placeholder::make("info_{$label}")
                                        ->label($label)
                                        ->content((string) $value))
                                    ->values()
                                    ->all()
                            )
                            ->columns(2),

                        Tab::make('Tools')
                            ->schema([
                                Toggle::make('maintenance_mode')
                                    ->label('Maintenance Mode')
                                    ->live(),
                                Textarea::make('maintenance_message')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Meet the Devs')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Placeholder::make('devs_intro')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString('<div style="text-align:center;padding:8px 0 12px;"><div style="font-weight:800;font-size:15px;letter-spacing:0.04em;color:#111010;">TDT Powersteel Dev Team</div><div style="font-size:12px;color:#717074;margin-top:4px;">Built with passion — thank you for supporting our work!</div></div>')),

                                Grid::make(2)
                                    ->schema([
                                        Placeholder::make('dev_james')
                                            ->label(new \Illuminate\Support\HtmlString('<span style="font-weight:700;">James Laurza</span>'))
                                            ->content(new \Illuminate\Support\HtmlString('<span style="color:#E67026;font-weight:600;">Developer</span>')),

                                        Placeholder::make('dev_angelo')
                                            ->label(new \Illuminate\Support\HtmlString('<span style="font-weight:700;">Angelo Tsin</span>'))
                                            ->content(new \Illuminate\Support\HtmlString('<span style="color:#E67026;font-weight:600;">Developer</span>')),

                                        Placeholder::make('dev_dhustin')
                                            ->label(new \Illuminate\Support\HtmlString('<span style="font-weight:700;">Dhustin Peñarubia</span>'))
                                            ->content(new \Illuminate\Support\HtmlString('<span style="color:#E67026;font-weight:600;">Developer</span>')),

                                        Placeholder::make('dev_shayne')
                                            ->label(new \Illuminate\Support\HtmlString('<span style="font-weight:700;">Shayne Anne Gadia</span>'))
                                            ->content(new \Illuminate\Support\HtmlString('<span style="color:#E67026;font-weight:600;">Developer</span>')),
                                    ]),

                                Placeholder::make('devs_footer')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString('<div style="text-align:center;margin-top:10px;padding-top:12px;border-top:1px solid rgba(113,112,116,0.12);font-size:11px;color:#A6A6A8;letter-spacing:0.06em;">© 2026 TDT Powersteel Corporation — Crafted by the Dev Team</div>')),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    /** Mirrors Django's get_system_info(). */
    protected function systemInfo(): array
    {
        return [
            'Laravel version' => app()->version(),
            'PHP version' => PHP_VERSION,
            'DB connection' => config('database.default'),
            'DB size' => $this->databaseSize(),
            'Debug mode' => config('app.debug') ? 'On' : 'Off',
            'Time zone' => config('app.timezone'),
            'Server time' => now()->toDayDateTimeString(),
            'Products' => Product::count(),
            'Product Categories' => ProductCategory::count(),
            'Quote Requests' => QuoteRequest::count(),
            'Referrals' => Referral::count(),
            'Live Chat Sessions' => ChatSession::count(),
            'Projects' => Project::count(),
        ];
    }

    protected function databaseSize(): string
    {
        $driver = config('database.default');

        if ($driver !== 'sqlite') {
            return 'N/A';
        }

        $path = config('database.connections.sqlite.database');

        if (! $path || ! file_exists($path)) {
            return 'N/A';
        }

        $bytes = filesize($path);

        foreach (['B', 'KB', 'MB', 'GB', 'TB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, 1)." {$unit}";
            }
            $bytes /= 1024;
        }

        return round($bytes, 1).' PB';
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $data['updated_by'] = Auth::id();
        $data['updated_at'] = now();

        $site = SiteSettings::current();
        $wasMaintenance = $site->maintenance_mode;
        $site->update($data);

        if (($data['maintenance_mode'] ?? false) !== $wasMaintenance) {
            $state = ($data['maintenance_mode'] ?? false) ? 'ON' : 'OFF';
            ActivityLog::log($this->currentUser(), "Turned Maintenance Mode {$state}");
        } else {
            ActivityLog::log($this->currentUser(), 'Updated settings');
        }

        Notification::make()->title('Settings saved')->success()->send();
    }

    /** Sanitize a table name to only contain safe characters (alphanumeric + underscore). */
    private function safeTableName(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }

    /**
     * Table names for the active connection — SQLite and MySQL expose
     * this completely differently, so branch on the driver rather than
     * assuming one or the other (this app ships on SQLite by default).
     */
    protected function listTables(): array
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => collect(DB::select("SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"))
                ->map(fn ($row) => $row->name)
                ->all(),
            'mysql' => collect(DB::select('SHOW TABLES'))
                ->map(fn ($row) => array_values((array) $row)[0])
                ->all(),
            'pgsql' => collect(DB::select("SELECT tablename AS name FROM pg_tables WHERE schemaname = 'public'"))
                ->map(fn ($row) => $row->name)
                ->all(),
            default => [],
        };
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Settings')
                ->action('save'),

            // The six actions below are maintenance/export tools rather than
            // everyday actions, and as separate top-level buttons they don't
            // wrap — the row just overflows past the edge of the screen on a
            // normal browser width. Grouping them into one dropdown keeps the
            // header to two buttons total, which always fits.
            ActionGroup::make([
                Action::make('exportFullDatabase')
                    ->label('Export Full Database')
                    ->icon('heroicon-o-circle-stack')
                    ->action(fn () => $this->exportDatabase(dataOnly: false)),

                Action::make('exportDataOnly')
                    ->label('Export Data Only')
                    ->icon('heroicon-o-table-cells')
                    ->action(fn () => $this->exportDatabase(dataOnly: true)),

                Action::make('clearCache')
                    ->label('Clear Cache')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('cache:clear');
                        ActivityLog::log($this->currentUser(), 'Cleared server cache');
                        Notification::make()->title('Cache cleared successfully')->success()->send();
                    }),

                Action::make('checkDatabase')
                    ->label('Check Database')
                    ->icon('heroicon-o-shield-check')
                    ->action(function () {
                        try {
                            $driver = DB::connection()->getDriverName();
                            $issues = [];

                            if ($driver === 'sqlite') {
                                // Direct equivalent of Django's PRAGMA integrity_check.
                                $result = DB::select('PRAGMA integrity_check');
                                $message = $result[0]->integrity_check ?? 'unknown';
                                if ($message !== 'ok') {
                                    $issues[] = $message;
                                }
                            } else {
                                foreach ($this->listTables() as $table) {
                                    $safe = $this->safeTableName($table);
                                    $result = DB::select("CHECK TABLE `{$safe}`");
                                    foreach ($result as $row) {
                                        if (($row->Msg_text ?? 'OK') !== 'OK') {
                                            $issues[] = "{$table}: {$row->Msg_text}";
                                        }
                                    }
                                }
                            }

                            $ok = empty($issues);
                            ActivityLog::log($this->currentUser(), 'Ran database integrity check ('.($ok ? 'OK' : 'ISSUES FOUND').')');

                            $ok
                                ? Notification::make()->title('Database check: OK, no issues found.')->success()->send()
                                : Notification::make()->title('Database check: issues found')->body(implode('; ', $issues))->danger()->send();
                        } catch (\Throwable $e) {
                            report($e);
                            $id = (string) \Illuminate\Support\Str::uuid();
                            \Illuminate\Support\Facades\Log::error('Database check failed', ['error_id' => $id, 'exception' => $e]);
                            Notification::make()->title('Database check unavailable')->body("An error occurred. Reference: {$id}")->warning()->send();
                        }
                    }),

                Action::make('optimizeTables')
                    ->label('Optimize Tables')
                    ->icon('heroicon-o-wrench')
                    ->requiresConfirmation()
                    ->action(function () {
                        try {
                            $driver = DB::connection()->getDriverName();

                            if ($driver === 'sqlite') {
                                // SQLite's direct equivalent — Django ran this exact command.
                                DB::statement('VACUUM');
                            } else {
                                foreach ($this->listTables() as $table) {
                                    $safe = $this->safeTableName($table);
                                    DB::statement("OPTIMIZE TABLE `{$safe}`");
                                }
                            }

                            ActivityLog::log($this->currentUser(), 'Optimized database tables');
                            Notification::make()->title('Database tables optimized successfully')->success()->send();
                        } catch (\Throwable $e) {
                            report($e);
                            $id = (string) \Illuminate\Support\Str::uuid();
                            \Illuminate\Support\Facades\Log::error('Optimize tables failed', ['error_id' => $id, 'exception' => $e]);
                            Notification::make()->title('Optimize unavailable')->body("An error occurred. Reference: {$id}")->warning()->send();
                        }
                    }),

                Action::make('refreshSystemInfo')
                    ->label('Refresh System Info')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function () {
                        ActivityLog::log($this->currentUser(), 'Refreshed System Info');
                        Notification::make()->title('System Info refreshed successfully')->success()->send();
                    }),
            ])
                ->label('Database & Tools')
                ->icon('heroicon-o-cog-6-tooth')
                ->button(),
        ];
    }

    protected function exportDatabase(bool $dataOnly)
    {
        $driver = DB::connection()->getDriverName();
        $tables = $this->listTables();

        $sql = "-- TDT Powersteel database export (".($dataOnly ? 'data only' : 'full').")\n";
        $sql .= '-- Generated '.now()->toDateTimeString()."\n\n";

        foreach ($tables as $table) {
            if (! $dataOnly) {
                if ($driver === 'sqlite') {
                    $create = DB::select("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table])[0] ?? null;
                    if ($create) {
                        $sql .= $create->sql.";\n\n";
                    }
                } else {
                    $safe = $this->safeTableName($table);
                    $create = DB::select("SHOW CREATE TABLE `{$safe}`")[0];
                    $sql .= array_values((array) $create)[1].";\n\n";
                }
            }

            $safeTable = $this->safeTableName($table);
            DB::table($table)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use (&$sql, $safeTable) {
                foreach ($rows as $row) {
                    $values = collect((array) $row)->map(fn ($v) => is_null($v) ? 'NULL' : DB::getPdo()->quote((string) $v))->implode(', ');
                    $columns = implode('`, `', array_keys((array) $row));
                    $sql .= "INSERT INTO `{$safeTable}` (`{$columns}`) VALUES ({$values});\n";
                }
            });

            $sql .= "\n";
        }

        ActivityLog::log($this->currentUser(), 'Exported '.($dataOnly ? 'data-only' : 'full').' database (.sql)');

        $filename = 'tdt_powersteel_'.($dataOnly ? 'data' : 'full').'_'.now()->format('Ymd_His').'.sql';

        return response()->streamDownload(fn () => print($sql), $filename, ['Content-Type' => 'application/sql']);
    }

    public function getTitle(): string
    {
        return 'Settings';
    }
}