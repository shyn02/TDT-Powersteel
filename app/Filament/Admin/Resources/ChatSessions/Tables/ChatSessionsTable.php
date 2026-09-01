<?php

namespace App\Filament\Admin\Resources\ChatSessions\Tables;

use App\Models\ChatSession;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class ChatSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Django's live_chat.html polls the JSON API for updates;
            // this is the Filament-native equivalent for the table view.
            ->poll('10s')
            ->defaultSort('last_message_at', 'desc')
            ->columns([
                TextColumn::make('client_name')
                    ->label('Client')
                    ->searchable()
                    ->weight(fn (ChatSession $record) => $record->unread_count > 0 ? 'bold' : 'normal'),
                TextColumn::make('page')
                    ->toggleable(),
                TextColumn::make('unread_count')
                    ->label('Unread')
                    ->badge()
                    ->state(fn (ChatSession $record) => $record->unread_count)
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'unassigned' => 'warning',
                        'active' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                IconColumn::make('is_priority')
                    ->label('Priority')
                    ->boolean(),
                TextColumn::make('assignedRep.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned'),
                TextColumn::make('last_message_at')
                    ->label('Last Message')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Archived (30-day)'),
                SelectFilter::make('status')
                    ->options([
                        'unassigned' => 'Unassigned',
                        'active' => 'Active',
                        'closed' => 'Closed',
                    ]),
                SelectFilter::make('assigned_to')
                    ->label('Assigned Rep')
                    ->relationship('assignedRep', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Open thread'),

                // Admin Priority Override — bypasses the claim queue and
                // hand-assigns a chat directly to a rep. Same
                // select_for_update()-guarded assign used by the claim
                // flow; here it's just a direct admin action.
                Action::make('priorityAssign')
                    ->label('Priority Assign')
                    ->icon('heroicon-o-bolt')
                    ->color('warning')
                    ->schema([
                        Select::make('rep_id')
                            ->label('Sales Rep')
                            ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (ChatSession $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $session = ChatSession::query()->lockForUpdate()->findOrFail($record->id);
                            $session->update([
                                'assigned_to' => $data['rep_id'],
                                'status' => 'active',
                                'is_priority' => true,
                            ]);
                        });

                        $rep = User::find($data['rep_id']);

                        Notification::make()
                            ->title("Chat with {$record->client_name} priority-assigned to {$rep?->name}")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
