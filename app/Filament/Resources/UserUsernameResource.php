<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\UserUsernameResource\Pages;
use App\Models\User;
use App\Models\UserUsername;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;

class UserUsernameResource extends Resource
{
    protected static ?string $model = UserUsername::class;

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationIcon = 'heroicon-s-wrench';

    protected static ?string $navigationGroup = 'Tools';

    protected static ?string $navigationLabel = 'Username Change Requests';

    protected static ?string $modelLabel = 'Username Change Request';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::pending()->count();

        return "{$count}";
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'warning' : null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Current Username')
                    ->url(fn (UserUsername $record) => UserResource::getUrl('view', ['record' => $record->user->display_name]))
                    ->extraAttributes(['class' => 'underline'])
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Requested New Username')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('approved_at'),
                        false: fn ($query) => $query->whereNull('approved_at'),
                    )
                    ->default(false),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->action(function (UserUsername $record) {
                        $record->update(['approved_at' => now()]);

                        /** @var User $user */
                        $user = $record->user;

                        $originalDisplayName = $user->display_name;

                        $user->display_name = $record->username;
                        $user->save();

                        sendDisplayNameChangeConfirmationEmail($user, $record->username);

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body("Approved {$originalDisplayName}'s username change request.")
                            ->send();
                    })
                    ->visible(fn (UserUsername $record) => !$record->is_approved)
                    ->requiresConfirmation()
                    ->modalDescription("Are you sure you'd like to do this? The username change will go into effect immediately.")
                    ->color('success')
                    ->icon('heroicon-o-check'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
        ];
    }
}
