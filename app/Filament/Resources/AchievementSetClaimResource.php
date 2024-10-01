<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\AchievementSetClaimResource\Pages;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AchievementSetClaimResource extends Resource
{
    protected static ?string $model = AchievementSetClaim::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Claims';

    protected static ?int $navigationSort = 70;

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
                Tables\Columns\ImageColumn::make('game.badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('game.title')
                    ->description(fn ($record) => $record->game->system->name)
                    ->url(function (AchievementSetClaim $record) {
                        if (request()->user()->can('manage', Game::class)) {
                            return GameResource::getUrl('view', ['record' => $record->game]);
                        }

                        return route('game.show', ['game' => $record->game]);
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->url(function (AchievementSetClaim $record) {
                        if (request()->user()->can('manage', User::class)) {
                            return UserResource::getUrl('view', ['record' => $record->user]);
                        }

                        if ($record->user && is_null($record->user->deleted_at)) {
                            return route('user.show', $record->user);
                        }

                        return null;
                    })
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->join('UserAccounts', 'SetClaim.user_id', '=', 'UserAccounts.id')
                            ->orderByRaw('COALESCE(UserAccounts.display_name, "") ' . $direction) // Sort by display_name, treating null as empty string.
                            ->orderBy('UserAccounts.User', $direction);
                    }),

                Tables\Columns\TextColumn::make('ClaimType')
                    ->label('Claim Type')
                    ->formatStateUsing(fn ($record) => ClaimType::toString($record->ClaimType)),

                Tables\Columns\TextColumn::make('Status')
                    ->formatStateUsing(fn ($record) => ClaimStatus::toString($record->Status)),

                Tables\Columns\TextColumn::make('SetType')
                    ->label('Set Type')
                    ->formatStateUsing(fn ($record) => ClaimSetType::toString($record->SetType)),

                Tables\Columns\TextColumn::make('Special')
                    ->formatStateUsing(fn ($record) => ClaimSpecial::toString($record->Special)),

                Tables\Columns\TextColumn::make('Created')
                    ->label('Claimed At')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('Finished')
                    ->label('Expires At')
                    ->dateTime('d M Y')
                    ->description(function ($record) {
                        $diffForHumansLabel = $record->finished_at->diffForHumans();
                        $isExpired = $record->finished_at->isPast() && $record->Status === ClaimStatus::Active;

                        $label = $isExpired ? "EXPIRED " : '';
                        $label .= $diffForHumansLabel;

                        return $label;
                    })
                    ->color(fn ($record) => $record->finished_at->isPast() && $record->Status === ClaimStatus::Active ? 'danger' : null)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('Status')
                    ->label('Status')
                    ->options(
                        collect(ClaimStatus::cases())
                            ->mapWithKeys(fn ($value) => [$value => ClaimStatus::toString($value)])
                    )
                    ->multiple()
                    ->default([ClaimStatus::Active, ClaimStatus::InReview]),

                Tables\Filters\SelectFilter::make('ClaimType')
                    ->label('Claim Type')
                    ->options(
                        collect(ClaimType::cases())
                            ->mapWithKeys(fn ($value) => [$value => ClaimType::toString($value)])
                    ),

                Tables\Filters\SelectFilter::make('SetType')
                    ->label('Set Type')
                    ->options(
                        collect(ClaimSetType::cases())
                            ->mapWithKeys(fn ($value) => [$value => ClaimSetType::toString($value)])
                    ),

                Tables\Filters\SelectFilter::make('Special')
                    ->options(
                        collect(ClaimSpecial::cases())
                            ->mapWithKeys(fn ($value) => [$value => ClaimSpecial::toString($value)])
                    ),

                Tables\Filters\SelectFilter::make('system')
                    ->relationship('game.system', 'Name'),

                Tables\Filters\SelectFilter::make('user')
                    ->label('Developer Type')
                    ->options([
                        Role::DEVELOPER => 'Developer',
                        Role::DEVELOPER_JUNIOR => 'Junior Developer',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'];

                        return $query->when($value, function (Builder $query) use ($value) {
                            return $query->whereHas('user', function (Builder $query) use ($value) {
                                $query->whereHas('roles', function (Builder $query) use ($value) {
                                    $query->where('name', $value);
                                });
                            });
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

            ])
            ->paginated([50, 100, 150])
            ->defaultSort('Created', 'desc');
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

    /**
     * @return Builder<AchievementSetClaim>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['game.system']);
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
