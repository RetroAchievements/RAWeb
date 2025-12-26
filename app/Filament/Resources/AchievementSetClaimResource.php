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
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class AchievementSetClaimResource extends Resource
{
    protected static ?string $model = AchievementSetClaim::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-flag';

    protected static string|UnitEnum|null $navigationGroup = 'Platform';

    protected static ?string $navigationLabel = 'Claims';

    protected static ?int $navigationSort = 70;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

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
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->url(function (AchievementSetClaim $record) {
                        if (request()->user()->can('manage', User::class)) {
                            return UserResource::getUrl('view', ['record' => $record->user]);
                        }

                        if ($record->user && is_null($record->user->Deleted)) {
                            return route('user.show', $record->user);
                        }

                        return null;
                    })
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->join('UserAccounts', 'achievement_set_claims.user_id', '=', 'UserAccounts.id')
                            ->orderByRaw('COALESCE(UserAccounts.display_name, "") ' . $direction) // Sort by display_name, treating null as empty string.
                            ->orderBy('UserAccounts.User', $direction);
                    }),

                Tables\Columns\TextColumn::make('claim_type')
                    ->label('Claim Type')
                    ->formatStateUsing(fn ($record) => $record->claim_type->label()),

                Tables\Columns\TextColumn::make('status')
                    ->formatStateUsing(fn ($record) => $record->status->label()),

                Tables\Columns\TextColumn::make('set_type')
                    ->label('Set Type')
                    ->formatStateUsing(fn ($record) => $record->set_type->label()),

                Tables\Columns\TextColumn::make('special_type')
                    ->label('Special')
                    ->formatStateUsing(fn ($record) => $record->special_type->label()),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Claimed At')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Expires At')
                    ->dateTime('d M Y')
                    ->description(function ($record) {
                        $diffForHumansLabel = $record->finished_at->diffForHumans();
                        $isExpired = $record->finished_at->isPast() && $record->status === ClaimStatus::Active;

                        $label = $isExpired ? "EXPIRED " : '';
                        $label .= $diffForHumansLabel;

                        return $label;
                    })
                    ->color(fn ($record) => $record->finished_at->isPast() && $record->status === ClaimStatus::Active ? 'danger' : null)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(
                        collect(ClaimStatus::cases())
                            ->mapWithKeys(fn (ClaimStatus $status) => [$status->value => $status->label()])
                    )
                    ->multiple()
                    ->default([ClaimStatus::Active->value, ClaimStatus::InReview->value]),

                Tables\Filters\SelectFilter::make('claim_type')
                    ->label('Claim Type')
                    ->options(
                        collect(ClaimType::cases())
                            ->mapWithKeys(fn (ClaimType $type) => [$type->value => $type->label()])
                    ),

                Tables\Filters\SelectFilter::make('set_type')
                    ->label('Set Type')
                    ->options(
                        collect(ClaimSetType::cases())
                            ->mapWithKeys(fn (ClaimSetType $type) => [$type->value => $type->label()])
                    ),

                Tables\Filters\SelectFilter::make('special_type')
                    ->label('Special')
                    ->options(
                        collect(ClaimSpecial::cases())
                            ->mapWithKeys(fn (ClaimSpecial $special) => [$special->value => $special->label()])
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
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([

            ])
            ->paginated([50, 100, 150])
            ->defaultSort('created_at', 'desc');
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
