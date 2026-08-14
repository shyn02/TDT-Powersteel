<?php

namespace App\Filament\Admin\Resources\ChatSessions;

use App\Filament\Admin\Resources\ChatSessions\Pages\CreateChatSession;
use App\Filament\Admin\Resources\ChatSessions\Pages\EditChatSession;
use App\Filament\Admin\Resources\ChatSessions\Pages\ListChatSessions;
use App\Filament\Admin\Resources\ChatSessions\Pages\ViewChatSession;
use App\Filament\Admin\Resources\ChatSessions\Schemas\ChatSessionForm;
use App\Filament\Admin\Resources\ChatSessions\Schemas\ChatSessionInfolist;
use App\Filament\Admin\Resources\ChatSessions\Tables\ChatSessionsTable;
use App\Models\ChatSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChatSessionResource extends Resource
{
    protected static ?string $model = ChatSession::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Store Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'client_name';

    // Django's ChatSessionAdmin: has_add_permission=False, has_delete_permission=False
    // (sessions are created by the client-side widget, not staff).
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Live Chat';
    }

    // Sidebar badge — mirrors Django's live_chat_badge().
    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\ChatMessage::where('sender', 'client')->where('is_read', false)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return ChatSessionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChatSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChatSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Admin\Resources\ChatSessions\RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatSessions::route('/'),
            'create' => CreateChatSession::route('/create'),
            'view' => ViewChatSession::route('/{record}'),
            'edit' => EditChatSession::route('/{record}/edit'),
        ];
    }
}
