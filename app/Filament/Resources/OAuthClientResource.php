<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Community\Actions\DeactivateOAuthClientAction;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\OAuthClientResource\Pages;
use App\Models\OAuthClient;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class OAuthClientResource extends Resource
{
    protected static ?string $model = OAuthClient::class;
    protected static ?string $label = 'OAuth Application';
    protected static ?string $navigationLabel = 'OAuth Apps';
    protected static ?string $slug = 'oauth-applications';
    protected static string|BackedEnum|null $navigationIcon = 'fas-plug';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'name';

    public static function canView(Model $record): bool
    {
        return static::can('manage');
    }

    public static function infolist(Schemas\Schema $schema): Schemas\Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Flex::make([
                    Schemas\Components\Section::make('Details')
                        ->schema([
                            Infolists\Components\TextEntry::make('name')
                                ->label('Name'),

                            Infolists\Components\TextEntry::make('owner.display_name')
                                ->label('Owner')
                                ->placeholder('none'),

                            Infolists\Components\TextEntry::make('is_confidential')
                                ->label('Client type')
                                ->badge()
                                ->formatStateUsing(fn (mixed $state): string => $state ? 'Confidential' : 'Public')
                                ->color(fn (mixed $state): string => $state ? 'info' : 'gray'),

                            Infolists\Components\TextEntry::make('redirect_uris')
                                ->label('Redirect URIs')
                                ->badge()
                                ->placeholder('none'),
                        ]),

                    Schemas\Components\Section::make([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\IconEntry::make('revoked')
                            ->label('Active')
                            ->state(fn (OAuthClient $record): bool => !$record->revoked)
                            ->boolean(),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created at')
                            ->dateTime(),

                        Infolists\Components\TextEntry::make('active_grants_count')
                            ->label('Users with active grants')
                            ->state(fn (OAuthClient $record): int => $record->grants()
                                ->whereNull('revoked_at')
                                ->count()
                            ),

                        Infolists\Components\TextEntry::make('active_tokens_count')
                            ->label('Active access tokens')
                            ->state(fn (OAuthClient $record): int => $record->tokens()
                                ->where('revoked', false)
                                ->where('expires_at', '>', now())
                                ->count()
                            ),
                    ])->grow(false),
                ])->from('md'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->grow(true),

                Tables\Columns\TextColumn::make('owner.display_name')
                    ->label('Owner')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereHasMorph('owner', [User::class], fn (Builder $ownerQuery) => $ownerQuery
                            ->where('display_name', 'like', "%{$search}%")
                        )
                    )
                    ->url(fn (OAuthClient $record): ?string => $record->owner instanceof User
                        ? UserResource::getUrl('view', ['record' => $record->owner])
                        : null
                    ),

                Tables\Columns\TextColumn::make('is_confidential')
                    ->label('Client type')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state ? 'Confidential' : 'Public')
                    ->color(fn (mixed $state): string => $state ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('redirect_uris')
                    ->label('Redirect URIs')
                    ->badge(),

                Tables\Columns\TextColumn::make('grants_count')
                    ->label('Users')
                    ->counts(['grants' => fn (Builder $query): Builder => $query->whereNull('revoked_at')])
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('revoked')
                    ->label('Active')
                    ->state(fn (OAuthClient $record): bool => !$record->revoked)
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Active')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('revoked', false),
                        false: fn (Builder $query): Builder => $query->where('revoked', true),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->defaultPaginationPageOption(50)
            ->paginationPageOptions([50])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ActionGroup::make([
                        Actions\ViewAction::make(),
                    ])->dropdown(false),

                    Actions\Action::make('audit-log')
                        ->url(fn (OAuthClient $record) => static::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),

                    static::makeRevokeAction(),
                ]),
            ]);
    }

    public static function makeRevokeAction(): Actions\Action
    {
        return Actions\Action::make('revoke')
            ->label('Revoke')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->requiresConfirmation()
            ->modalDescription(function (OAuthClient $record): string {
                $userCount = $record->grants()->whereNull('revoked_at')->count();

                return "This is a kill switch. {$userCount} "
                    . ($userCount === 1 ? 'user' : 'users')
                    . ' will lose access, every token issued to this application stops working, and every user grant is severed. This cannot be undone.';
            })
            ->authorize(fn (): bool => Auth::user()?->can('manage', OAuthClient::class) ?? false)
            ->hidden(fn (OAuthClient $record): bool => (bool) $record->revoked)
            ->action(function (OAuthClient $record): void {
                $client = OAuthClient::query()->findOrFail($record->getKey());

                (new DeactivateOAuthClientAction())->execute($client);

                $record->revoked = true;

                Notification::make()
                    ->success()
                    ->title('Success')
                    ->body("Revoked {$client->name}.")
                    ->send();
            });
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\Details::class,
            Pages\AuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'view' => Pages\Details::route('/{record}'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<OAuthClient>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<OAuthClient> $query */
        $query = parent::getEloquentQuery()
            ->select([
                'id',
                'numeric_id',
                'owner_type',
                'owner_id',
                'name',
                'provider',
                'redirect_uris',
                'grant_types',
                'revoked',
                'created_at',
                'updated_at',
            ])
            ->selectRaw("(secret is not null and secret != '') as is_confidential")
            ->with('owner');

        return $query;
    }

    /**
     * @return OAuthClient|null
     */
    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $query = static::getRecordRouteBindingEloquentQuery();

        if ($modifyQuery) {
            $query = $modifyQuery($query) ?? $query;
        }

        return $query->whereKey($key)->first();
    }
}
