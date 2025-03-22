<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Community\Actions\ApproveNewDisplayNameAction;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\UserUsernameResource\Pages;
use App\Mail\DisplayNameChangeDeclinedMail;
use App\Models\User;
use App\Models\UserUsername;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

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
            ->modifyQueryUsing(fn (Builder $query) => $query->where(function ($query) {
                $query->whereNotNull('approved_at')
                    ->orWhereNotNull('denied_at')
                    ->orWhere('created_at', '>', now()->subDays(30));
            }))
            ->columns([
                Tables\Columns\TextColumn::make('user.username')
                    ->label('Original Username')
                    ->url(fn (UserUsername $record) => UserResource::getUrl('view', ['record' => $record->user->display_name]))
                    ->extraAttributes(['class' => 'underline'])
                    ->openUrlInNewTab()
                    ->searchable()
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('previous_usages')
                    ->label('Previously Used By')
                    ->state(fn (UserUsername $record): string => empty($record->previous_usages) ? '-' : 'has_users')
                    ->description(fn (UserUsername $record): ?string => count($record->previous_usages) > 1
                            ? "Used by " . count($record->previous_usages) . " different users"
                            : null
                    )
                    ->formatStateUsing(function (string $state, UserUsername $record): string {
                        if ($state === '-') {
                            return '-';
                        }

                        return collect($record->previous_usages)
                            ->map(fn ($usage) => "<a href='" . route('user.show', $usage['user']) . "' 
                                    class='underline text-warning-600' 
                                    target='_blank'>" . $usage['user']->display_name . "</a>"
                            )
                            ->implode(', ');
                    })
                    ->html(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (UserUsername $record): string => match (true) {
                        $record->is_approved => 'Approved',
                        $record->is_denied => 'Denied',
                        default => 'Pending',
                    })
                    ->icon(fn (UserUsername $record): string => match (true) {
                        $record->is_approved => 'heroicon-o-check-circle',
                        $record->is_denied => 'heroicon-o-x-circle',
                        default => 'heroicon-o-clock',
                    })
                    ->color(fn (UserUsername $record): string => match (true) {
                        $record->is_approved => 'success',
                        $record->is_denied => 'danger',
                        default => 'warning',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'denied' => 'Denied',
                    ])
                    ->query(function ($query, $state) {
                        if (!isset($state['value'])) {
                            return $query;
                        }

                        return match ($state['value']) {
                            'pending' => $query->pending(),
                            'approved' => $query->approved(),
                            'denied' => $query->denied(),
                            default => $query,
                        };
                    })
                    ->default('pending'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->action(function (UserUsername $record) {
                        /** @var User $user */
                        $user = $record->user;
                        $originalDisplayName = $user->display_name;

                        (new ApproveNewDisplayNameAction())->execute($user, $record);

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body("Approved {$originalDisplayName}'s username change request.")
                            ->send();
                    })
                    ->visible(fn (UserUsername $record) => !$record->is_approved && !$record->is_denied)
                    ->requiresConfirmation()
                    ->modalDescription("Are you sure you'd like to do this? The username change will go into effect immediately.")
                    ->color('success')
                    ->icon('heroicon-o-check'),

                Tables\Actions\Action::make('deny')
                    ->action(function (UserUsername $record) {
                        $record->update(['denied_at' => now()]);

                        /** @var User $user */
                        $user = $record->user;

                        Mail::to($user)->queue(new DisplayNameChangeDeclinedMail($user, $record->username));

                        Notification::make()
                            ->success()
                            ->title('Success')
                            ->body("Denied {$record->user->display_name}'s username change request.")
                            ->send();
                    })
                    ->visible(fn (UserUsername $record) => !$record->is_approved && !$record->is_denied)
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to deny this username change request?')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark'),
            ])
            ->bulkActions([

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
