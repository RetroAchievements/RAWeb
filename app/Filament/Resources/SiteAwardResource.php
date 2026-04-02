<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Community\Enums\AwardType;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\SiteAwardResource\Pages;
use App\Filament\Resources\SiteAwardResource\RelationManagers\AwardedUsersRelationManager;
use App\Models\Role;
use App\Models\SiteAward;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SiteAwardResource extends Resource
{
    protected static ?string $model = SiteAward::class;

    protected static ?string $modelLabel = 'Site Award';
    protected static ?string $pluralModelLabel = 'Site Awards';
    protected static ?string $breadcrumb = 'Site Awards';
    protected static string|BackedEnum|null $navigationIcon = 'fas-flask-vial';
    protected static string|UnitEnum|null $navigationGroup = 'Platform';
    protected static ?string $navigationLabel = 'Site Awards';
    protected static ?int $navigationSort = 56;

    public static function form(Schema $schema): Schema
    {
        /** @var User $user */
        $user = Auth::user();

        $isPlaytestManagerOnly = $user->hasRole(Role::PLAYTEST_MANAGER) && !$user->hasRole(Role::ADMINISTRATOR);

        return $schema
            ->components([
                Forms\Components\TextInput::make('label')
                    ->minLength(2)
                    ->maxLength(40)
                    ->required(),

                Forms\Components\Select::make('award_type')
                    ->label('Type')
                    ->options([
                        AwardType::Playtest->value => AwardType::Playtest->label(),
                    ])
                    ->default(AwardType::Playtest->value)
                    ->disabled($isPlaytestManagerOnly)
                    ->dehydrated()
                    ->required(),

                Schemas\Components\Section::make('Media')
                    ->icon('heroicon-s-photo')
                    ->schema([
                        Forms\Components\FileUpload::make('image_asset_path')
                            ->label('Badge')
                            ->disk('livewire-tmp')
                            ->image()
                            ->rules([
                                'dimensions:width=96,height=96',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->required(fn (?SiteAward $record): bool => $record === null)
                            ->previewable(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badgeUrl')
                    ->label('Badge')
                    ->size(config('media.icon.md.width')),

                Tables\Columns\TextColumn::make('label')
                    ->label('Label'),

                Tables\Columns\TextColumn::make('award_type')
                    ->label('Type')
                    ->formatStateUsing(fn (AwardType $state): string => $state->label()),

                Tables\Columns\TextColumn::make('player_badges_count')
                    ->label('Awarded'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AwardedUsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'create' => Pages\Create::route('/create'),
            'edit' => Pages\Edit::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<SiteAward>
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withCount('playerBadges');

        /** @var User $user */
        $user = Auth::user();

        // Playtest Managers who aren't also admins only see playtest awards.
        if ($user->hasRole(Role::PLAYTEST_MANAGER) && !$user->hasRole(Role::ADMINISTRATOR)) {
            $query->where('award_type', AwardType::Playtest->value);
        }

        /** @var Builder<SiteAward> $query */
        return $query;
    }
}
