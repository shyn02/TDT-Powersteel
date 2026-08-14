<?php

namespace App\Filament\Admin\Resources\ChatSessions\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

// The "thread" — replaces Django's bespoke live_chat.html template.
// The Create form here doubles as the reply box (session + message
// only); sender/staff_user are force-set on save, same as Django's
// ChatMessageAdmin.save_model() override.
class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $recordTitleAttribute = 'message';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('message')
                    ->label('Reply')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->defaultSort('created_at', 'asc')
            ->poll('10s')
            ->columns([
                TextColumn::make('sender')
                    ->badge()
                    ->color(fn (string $state) => $state === 'client' ? 'info' : 'success'),
                TextColumn::make('message')
                    ->wrap()
                    ->limit(200),
                IconColumn::make('is_read')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Send reply')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['sender'] = 'staff';
                        $data['staff_user_id'] = Auth::id();
                        $data['is_read'] = true;
                        $data['created_at'] = now();

                        return $data;
                    })
                    ->after(function (Model $ownerRecord) {
                        // Mirrors ChatMessageAdmin.save_model(): sending a
                        // reply marks the session active and bumps last_message_at.
                        $ownerRecord->update([
                            'is_active' => true,
                            'last_message_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Reply sent')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
