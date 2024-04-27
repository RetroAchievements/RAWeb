<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\Permissions;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'fas-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordRouteKeyName = 'User';

    protected static ?string $recordTitleAttribute = 'username';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param User $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->User;
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
        return ['ID', 'User', 'display_name'];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                Infolists\Components\Split::make([
                    Infolists\Components\Section::make()
                        ->columns(['xl' => 2, '2xl' => 3])
                        ->schema([
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\ImageEntry::make('avatar_url')
                                        ->label('Avatar')
                                        ->size(config('media.icon.lg.width')),
                                    Infolists\Components\TextEntry::make('Motto'),
                                ]),
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('roles.name')
                                        ->badge()
                                        ->formatStateUsing(fn (string $state): string => __('permission.role.' . $state))
                                        ->color(fn (string $state): string => Role::toFilamentColor($state))
                                        ->hidden(fn ($record) => $record->roles->isEmpty()),
                                    Infolists\Components\TextEntry::make('Permissions')
                                        ->label('Permissions (legacy)')
                                        ->badge()
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
                            Infolists\Components\Group::make()
                                ->schema([
                                    Infolists\Components\TextEntry::make('canonical_url')
                                        ->label('Canonical URL')
                                        ->url(fn (User $record): string => $record->getCanonicalUrlAttribute()),
                                    Infolists\Components\TextEntry::make('permalink')
                                        ->url(fn (User $record): string => $record->getPermalinkAttribute()),
                                ]),
                        ]),
                    Infolists\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            Infolists\Components\TextEntry::make('id')
                                ->label('ID'),
                            Infolists\Components\TextEntry::make('Created')
                                ->label('Joined')
                                ->dateTime(),
                            Infolists\Components\TextEntry::make('LastLogin')
                                ->label('Last login at')
                                ->dateTime(),
                            Infolists\Components\TextEntry::make('DeleteRequested')
                                ->label('Deleted requested at')
                                ->dateTime()
                                ->hidden(fn ($state) => !$state)
                                ->color('warning'),
                            Infolists\Components\TextEntry::make('Deleted')
                                ->label('Deleted at')
                                ->dateTime()
                                ->hidden(fn ($state) => !$state)
                                ->color('danger'),
                            Infolists\Components\IconEntry::make('Untracked')
                                ->label('Ranked')
                                ->boolean()
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

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Split::make([
                    Forms\Components\Section::make()
                        ->columns(['xl' => 2, '2xl' => 2])
                        ->schema([
                            Forms\Components\TextInput::make('Motto')
                                ->maxLength(50),
                        ]),
                    Forms\Components\Section::make()
                        ->grow(false)
                        ->schema([
                            DateTimePicker::make('muted_until')
                                ->readOnly()
                                ->time(false)
                                ->suffix('at midnight')
                                ->native(false)
                                ->displayFormat('Y-m-d')
                                ->maxDate('2038-01-18')
                                ->afterStateHydrated(function (DateTimePicker $component, ?string $state) use ($form) {
                                    if (!$state) {
                                        /** @var User $user */
                                        $user = $form->model;

                                        $utcMutedUntil = $user->muted_until?->setTimezone('UTC');
                                        $formattedDate = $utcMutedUntil?->format('Y-m-d');
                                        $component->state($formattedDate);
                                    }
                                }),
                            Forms\Components\Toggle::make('ManuallyVerified')
                                ->label('Forum verified'),
                            Forms\Components\Toggle::make('Untracked'),
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
                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('User')
                    ->description(fn (User $record): string => $record->display_name)
                    ->label('Username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('display_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // Tables\Columns\TextColumn::make('email_verified_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('permission.role.' . $state))
                    ->color(fn (string $state): string => Role::toFilamentColor($state)),
                Tables\Columns\TextColumn::make('Permissions')
                    ->label('Legacy permissions')
                    ->badge()
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
                Tables\Columns\IconColumn::make('Untracked')
                    ->label('Ranked')
                    ->boolean()
                    ->trueColor('danger')
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseColor('success')
                    ->falseIcon('heroicon-o-check-circle')
                    ->alignCenter(),
                // Tables\Columns\TextColumn::make('unranked_at')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('banned_at')
                //     ->dateTime()
                //     ->sortable(),
                // Tables\Columns\TextColumn::make('muted_until')
                //     ->dateTime()
                //     ->sortable(),
                Tables\Columns\IconColumn::make('UserWallActive')
                    ->label('Wall active')
                    ->boolean()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('Created')
                    ->label('Created at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('LastLogin')
                    ->label('Last login at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('Updated')
                    ->label('Updated at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('DeleteRequested')
                    ->label('Deleted requested at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Deleted')
                    ->label('Deleted at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('LastLogin', 'desc')
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
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                    ])->dropdown(false),
                    Tables\Actions\Action::make('roles')
                        ->url(fn ($record) => UserResource::getUrl('roles', ['record' => $record]))
                        ->icon('fas-lock'),
                    Tables\Actions\Action::make('audit-log')
                        ->url(fn ($record) => UserResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
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
