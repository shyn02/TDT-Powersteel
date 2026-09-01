<?php

namespace App\Filament\Admin\Resources\PasswordResetRequests\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('requested_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('resolved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('resolver.name')
                    ->label('Resolved by')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve & Reset')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending' && ! $record->isExpired())
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => "Approve reset for {$record->email}?")
                    ->modalDescription('This will generate a temporary password and force the user to change it on next login. Copy the password to share securely.')
                    ->action(function ($record) {
                        $user = $record->user ?? \App\Models\User::where('email', $record->email)->first();
                        if (! $user) {
                            Notification::make()->title('User not found for '.$record->email)->danger()->send();
                            return;
                        }
                        $temp = Str::random(10).'!A1';
                        $user->forceFill([
                            'password' => Hash::make($temp),
                            'must_change_password' => true,
                            'password_expires_at' => now()->addHours(24),
                        ])->save();
                        $record->update([
                            'status' => 'approved',
                            'resolved_at' => now(),
                            'resolved_by' => auth()->id(),
                        ]);
                        try { \App\Models\ActivityLog::log(auth()->user(), "Approved password reset for {$record->email} (temp: {$temp})"); } catch (\Throwable $e) {}
                        Notification::make()
                            ->title('Approved — Temporary password: '.$temp)
                            ->body('Share this securely. User must change it on next login within 24h.')
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'rejected',
                            'resolved_at' => now(),
                            'resolved_by' => auth()->id(),
                        ]);
                        Notification::make()->title('Request rejected')->warning()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
