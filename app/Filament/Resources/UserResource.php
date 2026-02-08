<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Permissions;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use BackedEnum;
use Closure;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Pages\Page;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'fas-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static int $globalSearchResultsLimit = 5;

    public static function resolveRecordRouteBinding(int|string $key, ?Closure $modifyQuery = null): ?Model
    {
        $query = User::whereName($key);

        if ($modifyQuery) {
            $query = $modifyQuery($query);
        }

        return $query->first();
    }

    /**
     * @param User $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->display_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->id,
            'Display name' => $record->display_name,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['id', 'username', 'display_name'];
    }

    /**
     * @param Builder<User> $query
     */
    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $query->orderByDesc('points_hardcore');
    }

    public static function infolist(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Flex::make([
                    Schemas\Components\Section::make()
                        ->columns(['xl' => 2, '2xl' => 3])
                        ->schema([
                            Schemas\Components\Group::make()
                                ->schema([
                                    Infolists\Components\ImageEntry::make('avatar_url')
                                        ->label('Avatar')
                                        ->size(config('media.icon.lg.width')),

                                    Infolists\Components\TextEntry::make('motto'),
                                ]),

                            Schemas\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('roles.name')
                                        ->badge()
                                        ->wrap()
                                        ->formatStateUsing(fn (string $state): string => __('permission.role.' . $state))
                                        ->color(fn (string $state): string => Role::toFilamentColor($state))
                                        ->hidden(fn ($record) => $record->roles->isEmpty()),

                                    Infolists\Components\TextEntry::make('Permissions')
                                        ->label('Permissions (legacy)')
                                        ->badge()
                                        ->wrap()
                                        ->formatStateUsing(fn (int $state): string => Permissions::toString($state))
                                        ->color(fn (int $state): string => match ($state) {
                                            Permissions::Spam => 'danger',
                                            Permissions::Banned => 'danger',
                                            Permissions::JuniorDeveloper => 'success',
                                            Permissions::Developer => 'success',
                                            Permissions::Moderator => 'warning',
                                            default => 'gray',
                                        }),
                                ]),

                            Schemas\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('username')
                                        ->label('Original Username')
                                        ->hidden(fn (User $record) => $record->display_name === $record->username),

                                    Infolists\Components\TextEntry::make('canonical_url')
                                        ->label('Canonical URL')
                                        ->url(fn (User $record): string => $record->getCanonicalUrlAttribute())
                                        ->openUrlInNewTab(),
                                ]),
                        ]),

                    Schemas\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            Infolists\Components\TextEntry::make('id')
                                ->label('ID'),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Joined')
                                ->dateTime(),

                            Infolists\Components\TextEntry::make('last_activity_at')
                                ->label('Last login at')
                                ->dateTime(),

                            Infolists\Components\TextEntry::make('deleted_requested_at')
                                ->label('Deleted requested at')
                                ->dateTime()
                                ->hidden(fn ($state) => !$state)
                                ->color('warning'),

                            Infolists\Components\TextEntry::make('deleted_at')
                                ->label('Deleted at')
                                ->dateTime()
                                ->hidden(fn ($state) => !$state)
                                ->color('danger'),

                            Infolists\Components\IconEntry::make('unranked_at')
                                ->label('Ranked')
                                ->boolean()
                                ->getStateUsing(fn ($record) => $record->unranked_at !== null)
                                ->trueColor('danger')
                                ->trueIcon('heroicon-o-x-circle')
                                ->falseColor('success')
                                ->falseIcon('heroicon-o-check-circle'),

                            Infolists\Components\IconEntry::make('ManuallyVerified')
                                ->label('Forum verified')
                                ->boolean(),

                            Infolists\Components\TextEntry::make('muted_until')
                                ->hidden(function ($state) {
                                    if (!$state) {
                                        return true;
                                    }

                                    return $state->isPast();
                                })
                                ->helperText('Community interactions not allowed.')
                                ->color('warning')
                                ->dateTime(),
                        ]),
                ])->from('md'),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Flex::make([
                    Schemas\Components\Section::make()
                        ->columns(['xl' => 2, '2xl' => 2])
                        ->schema([
                            Forms\Components\TextInput::make('motto')
                                ->maxLength(50),
                        ]),

                    Schemas\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            Forms\Components\DatePicker::make('muted_until')
                                ->suffix('at midnight')
                                ->default(now())
                                ->suffix('at midnight')
                                ->native(false)
                                ->maxDate('2038-01-18')
                                ->displayFormat('Y-m-d')
                                ->date()
                                ->afterStateHydrated(function (Forms\Components\DatePicker $component, ?string $state) use ($schema) {
                                    if (!$state) {
                                        /** @var User $user */
                                        $user = $schema->model;

                                        $utcMutedUntil = $user->muted_until?->setTimezone('UTC');
                                        $formattedDate = $utcMutedUntil?->format('Y-m-d');
                                        $component->state($formattedDate);
                                    }
                                }),

                            Forms\Components\Toggle::make('ManuallyVerified')
                                ->label('Forum verified'),

                            Forms\Components\Toggle::make('is_unranked')
                                ->label('Untracked')
                                ->afterStateHydrated(function (Forms\Components\Toggle $component, $record) {
                                    $component->state($record?->unranked_at !== null);
                                }),
                        ]),
                ])->from('md'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->description(fn (User $record): string => $record->username !== $record->display_name ? $record->username : '')
                    ->label('Username')
                    ->searchable(),

                // Tables\Columns\TextColumn::make('email_verified_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->wrap()
                    ->formatStateUsing(fn (string $state): string => __('permission.role.' . $state))
                    ->color(fn (string $state): string => Role::toFilamentColor($state)),

                Tables\Columns\TextColumn::make('Permissions')
                    ->label('Legacy permissions')
                    ->badge()
                    ->wrap()
                    ->formatStateUsing(fn (int $state): string => Permissions::toString($state))
                    ->color(fn (int $state): string => match ($state) {
                        Permissions::Spam => 'danger',
                        Permissions::Banned => 'danger',
                        Permissions::JuniorDeveloper => 'success',
                        Permissions::Developer => 'success',
                        Permissions::Moderator => 'warning',
                        default => 'gray',
                    }),

                // Tables\Columns\TextColumn::make('country'),
                // Tables\Columns\TextColumn::make('timezone'),
                // Tables\Columns\TextColumn::make('locale'),

                Tables\Columns\IconColumn::make('ManuallyVerified')
                    ->label('Forum verified')
                    ->boolean()
                    ->alignCenter(),

                // Tables\Columns\TextColumn::make('forum_verified_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('unranked_at')
                    ->label('Ranked')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->unranked_at !== null)
                    ->trueColor('danger')
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseColor('success')
                    ->falseIcon('heroicon-o-check-circle')
                    ->alignCenter(),

                // Tables\Columns\TextColumn::make('banned_at')
                //     ->dateTime()
                //     ->sortable(),

                // Tables\Columns\TextColumn::make('muted_until')
                //     ->dateTime()
                //     ->sortable(),

                Tables\Columns\IconColumn::make('is_user_wall_active')
                    ->label('Wall active')
                    ->boolean()
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label('Last login at')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('delete_requested_at')
                    ->label('Deleted requested at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('last_activity_at', 'desc')
            ->filters([
                Filters\SelectFilter::make('Permissions')
                    ->multiple()
                    ->options(
                        collect(Permissions::cases())
                            ->mapWithKeys(fn ($value) => [$value => __(Permissions::toString($value))])
                    ),

                Filters\TrashedFilter::make(),
            ])
            ->deferFilters()
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ActionGroup::make([
                        Actions\ViewAction::make(),
                        Actions\EditAction::make(),
                    ])->dropdown(false),

                    Actions\Action::make('roles')
                        ->url(fn ($record) => UserResource::getUrl('roles', ['record' => $record]))
                        ->icon('fas-lock'),

                    Actions\Action::make('audit-log')
                        ->url(fn ($record) => UserResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\Details::class,
            Pages\Roles::class,
            Pages\AuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'roles' => Pages\Roles::route('/{record}/roles'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<User>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
