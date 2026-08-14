<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('profile.position')
                    ->label('Position')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? str($state)->replace('_', ' ')->title() : '—')
                    ->color(fn (?string $state) => $state === 'admin' ? 'primary' : 'gray'),
                ToggleColumn::make('is_active'),
                TextColumn::make('created_at')
                    ->label('Date joined')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('position')
                    ->relationship('profile', 'position')
                    ->options([
                        'admin' => 'Admin',
                        'sales_rep' => 'Sales Rep',
                        'warehouse_staff' => 'Warehouse Staff',
                        'manager' => 'Manager',
                        'support' => 'Support',
                    ]),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    // Django's generate_temporary_password action — a secure
                    // alternative to "view password" since hashes can't be
                    // reversed. Shows the new password once, on screen only.
                    BulkAction::make('generate_temporary_password')
                        ->label('Generate temporary password')
                        ->icon('heroicon-o-key')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $lines = [];

                            foreach ($records as $user) {
                                $temp = Str::password(12);
                                $user->forceFill(['password' => bcrypt($temp)])->save();
                                $lines[] = "{$user->email}: {$temp}";
                            }

                            Notification::make()
                                ->title('New temporary password(s) generated — copy these now, they will not be shown again')
                                ->body(implode("\n", $lines))
                                ->warning()
                                ->persistent()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
