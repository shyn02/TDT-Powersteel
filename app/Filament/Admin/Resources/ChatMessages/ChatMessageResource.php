<?php

namespace App\Filament\Admin\Resources\ChatMessages;

use App\Filament\Admin\Concerns\AdminOnlyResource;
use App\Filament\Admin\Resources\ChatMessages\Pages\CreateChatMessage;
use App\Filament\Admin\Resources\ChatMessages\Pages\EditChatMessage;
use App\Filament\Admin\Resources\ChatMessages\Pages\ListChatMessages;
use App\Filament\Admin\Resources\ChatMessages\Schemas\ChatMessageForm;
use App\Filament\Admin\Resources\ChatMessages\Tables\ChatMessagesTable;
use App\Models\ChatMessage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ChatMessageResource extends Resource
{
    use AdminOnlyResource;

    protected static ?string $model = ChatMessage::class;

    // Keep hidden from sidebar (original behavior) but still enforce AdminOnlyResource gates for direct URLs
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'message';

    protected static ?string $navigationLabel = 'Chat Messages';

    public static function form(Schema $schema): Schema
    {
        return ChatMessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChatMessagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatMessages::route('/'),
            'create' => CreateChatMessage::route('/create'),
            'edit' => EditChatMessage::route('/{record}/edit'),
        ];
    }
}
