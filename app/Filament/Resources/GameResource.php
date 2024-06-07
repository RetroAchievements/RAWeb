<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\GameResource\Pages;
use App\Filament\Rules\ExistsInForumTopics;
use App\Filament\Rules\IsAllowedGuideUrl;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'fas-gamepad';

    protected static ?string $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 5;

    /**
     * @param Game $record
     */
    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    /**
     * @param Game $record
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'ID' => $record->id,
            'System' => $record->system->name,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ID', 'Title'];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ImageEntry::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.lg.width')),

                Infolists\Components\Section::make('Metadata')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('title'),

                        Infolists\Components\TextEntry::make('forumTopic.id')
                            ->label('Forum Topic')
                            ->url(fn (?int $state) => url("viewtopic.php?t={$state}")),

                        Infolists\Components\TextEntry::make('system')
                            ->formatStateUsing(fn (System $state) => "[{$state->id}] {$state->name}")
                            ->url(fn (System $state) => SystemResource::getUrl('view', ['record' => $state->id])),

                        Infolists\Components\TextEntry::make('Developer')
                            ->placeholder('None'),

                        Infolists\Components\TextEntry::make('Publisher')
                            ->placeholder('None'),

                        Infolists\Components\TextEntry::make('Genre')
                            ->placeholder('None'),

                        Infolists\Components\TextEntry::make('GuideURL')
                            ->label('RAGuide URL')
                            ->placeholder('None'),
                    ]),

                Infolists\Components\Section::make('Metrics')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\Fieldset::make('Players')
                            ->schema([
                                Infolists\Components\TextEntry::make('players_total')
                                    ->label('Total')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('players_hardcore')
                                    ->label('Hardcore')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),

                        Infolists\Components\Fieldset::make('Achievements')
                            ->schema([
                                Infolists\Components\TextEntry::make('achievements_published')
                                    ->label('Published')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('achievements_unpublished')
                                    ->label('Unofficial')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),

                        Infolists\Components\Fieldset::make('Score')
                            ->schema([
                                Infolists\Components\TextEntry::make('points_total')
                                    ->label('Points')
                                    ->numeric(),

                                Infolists\Components\TextEntry::make('TotalTruePoints')
                                    ->label('RetroPoints')
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->columnSpan(['md' => 2, 'xl' => 1, '2xl' => 1]),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        /** @var User $user */
        $user = Auth::user();

        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('Title')
                            ->required()
                            ->maxLength(80)
                            ->disabled(!$user->can('updateField', [$form->model, 'Title'])),

                        // TODO Support these three when we also support managing related games / hubs.
                        // Forms\Components\TextInput::make('Developer')
                        //     ->maxLength(50),

                        // Forms\Components\TextInput::make('Publisher')
                        //     ->maxLength(50),

                        // Forms\Components\TextInput::make('Genre')
                        //     ->maxLength(50),

                        Forms\Components\TextInput::make('ForumTopicID')
                            ->label('Forum Topic ID')
                            ->numeric()
                            ->rules([new ExistsInForumTopics()])
                            ->disabled(!$user->can('updateField', [$form->model, 'ForumTopicID'])),

                        Forms\Components\TextInput::make('GuideURL')
                            ->label('RAGuide URL')
                            ->url()
                            ->rules([new IsAllowedGuideUrl()])
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->disabled(!$user->can('updateField', [$form->model, 'GuideURL'])),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('ID')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('Title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('system')
                    ->label('System')
                    ->formatStateUsing(fn (System $state) => "[{$state->id}] {$state->name}")
                    ->url(function (System $state) {
                        if (request()->user()->can('manage', System::class)) {
                            return SystemResource::getUrl('view', ['record' => $state->id]);
                        }

                        return null;
                    }),

                Tables\Columns\TextColumn::make('forumTopic.id')
                    ->label('Forum Topic')
                    ->url(fn (?int $state) => url("viewtopic.php?t={$state}"))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('Publisher')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('Developer')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('Genre')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('players_total')
                    ->label('Players (Total)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('players_hardcore')
                    ->label('Players (Hardcore)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('achievements_published')
                    ->label('Achievements (Published)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('achievements_unpublished')
                    ->label('Achievements (Unofficial)')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('points_total')
                    ->label('Points')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('TotalTruePoints')
                    ->label('RetroPoints')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('system')
                    ->relationship('system', 'name'),

                Tables\Filters\TernaryFilter::make('achievements_published')
                    ->label('Has core set')
                    ->placeholder('Any')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->where('achievements_published', '>=', 6),
                        false: fn (Builder $query): Builder => $query->where('achievements_published', '<', 6),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                    ])->dropdown(false),
                    Tables\Actions\Action::make('audit-log')
                        ->url(fn ($record) => GameResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
        return $page->generateNavigationitems([
            Pages\Details::class,
            Pages\AuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'create' => Pages\Create::route('/create'),
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<Game>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with(['system', 'forumTopic']);
    }
}
