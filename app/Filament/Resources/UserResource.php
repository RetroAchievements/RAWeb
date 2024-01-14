<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\UserResource\Pages;
use App\Site\Enums\Permissions;
use App\Site\Models\Role;
use App\Site\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Filters;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'fas-users';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordRouteKeyName = 'User';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: function (Builder $query) {
                            return $query->whereIn('name', auth()->user()->assignableRoles);
                        },
                    )
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (\Spatie\Permission\Models\Role $record) => __('permission.role.' . $record->name))
                    ->hidden(fn () => auth()->user()->assignableRoles->isEmpty()),
                Forms\Components\Select::make('Permissions')
                    ->label('Legacy permissions')
                    ->options(
                        collect(Permissions::cases())
                            ->mapWithKeys(fn ($value) => [$value => __(Permissions::toString($value))])
                    )
                    ->required()
                    ->hidden(fn () => auth()->user()->assignableRoles->isEmpty()),
                Forms\Components\TextInput::make('Motto')
                    ->maxLength(50),
                Forms\Components\Toggle::make('ManuallyVerified')
                    ->label('Forum verified'),
                Forms\Components\Toggle::make('Untracked'),
                // Forms\Components\DateTimePicker::make('muted_until'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('ID', 'desc')
            ->columns([
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
                Tables\Columns\TextColumn::make('LastLogin')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('ManuallyVerified')
                    ->label('Forum verified')
                    ->alignCenter(),
                // Tables\Columns\TextColumn::make('forum_verified_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ToggleColumn::make('Untracked')
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
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('DeleteRequested')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('Deleted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filters\SelectFilter::make('Permissions')
                    ->multiple()
                    ->options(
                        collect(Permissions::cases())
                            ->mapWithKeys(fn ($value) => [$value => __(Permissions::toString($value))])
                    ),
                Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditUser::class,
            // TODO Pages\ManageX::class,
            // TODO Permissions,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
