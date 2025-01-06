<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers\AchievementsRelationManager;
use App\Filament\Rules\ExistsInForumTopics;
use App\Filament\Rules\IsAllowedGuideUrl;
use App\Models\Event;
use App\Models\Game;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\ReleasedAtGranularity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $modelLabel = 'Event';
    protected static ?string $pluralModelLabel = 'Events';
    protected static ?string $breadcrumb = 'Events';
    protected static ?string $navigationIcon = 'fas-calendar-days';
    protected static ?string $navigationGroup = 'Platform';
    protected static ?string $navigationLabel = 'Events';
    protected static ?int $navigationSort = 55;
    protected static ?string $recordTitleAttribute = 'title';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\ImageEntry::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.lg.width')),

                Infolists\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('permalink')
                            ->url(fn (Event $record): string => $record->getPermalinkAttribute())
                            ->extraAttributes(['class' => 'underline'])
                            ->openUrlInNewTab(),

                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('slug')
                            ->label('Slug'),

                        Infolists\Components\TextEntry::make('game.title')
                            ->label('Title'),

                        Infolists\Components\TextEntry::make('game.sort_title')
                            ->label('Sort Title'),

                        Infolists\Components\TextEntry::make('game.forumTopic.id')
                            ->label('Forum Topic ID')
                            ->url(fn (?int $state) => url("viewtopic.php?t={$state}"))
                            ->extraAttributes(['class' => 'underline']),
                    ]),

                Infolists\Components\Section::make('Metrics')
                    ->icon('heroicon-s-arrow-trending-up')
                    ->description("
                        Statistics regarding the game's players and achievements can be found here.
                    ")
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('game.players_total')
                            ->label('Players')
                            ->numeric(),

                        Infolists\Components\TextEntry::make('game.achievements_published')
                            ->label('Achievements')
                            ->numeric(),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->relationship('game')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('Title')
                            ->required()
                            ->label('Title')
                            ->minLength(2)
                            ->maxLength(80),

                        Forms\Components\TextInput::make('sort_title')
                            ->required()
                            ->label('Sort Title')
                            ->minLength(2)
                            ->helperText('Normalized title for sorting purposes. For example, "The Goonies II" would sort as "goonies 02". DON\'T CHANGE THIS UNLESS YOU KNOW WHAT YOU\'RE DOING.')
                            ->reactive()
                            ->afterStateHydrated(function (callable $set, callable $get, ?string $state) {
                                $set('original_sort_title', $state ?? '');
                            }),

                        Forms\Components\TextInput::make('ForumTopicID')
                            ->label('Forum Topic ID')
                            ->numeric()
                            ->rules([new ExistsInForumTopics()]),
                    ]),

                Forms\Components\Section::make()
                    ->icon('fas-calendar-days')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->label('URL alias')
                            ->rules(['alpha_dash'])
                            ->minLength(4)
                            ->maxLength(20),
                    ]),

                Forms\Components\Section::make('Media')
                    ->icon('heroicon-s-photo')
                    ->schema([
                        // Store a temporary file on disk until the user submits.
                        // When the user submits, put in storage.
                        Forms\Components\FileUpload::make('image_asset_path')
                            ->label('Badge')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->rules([
                                'dimensions:width=96,height=96',
                            ])
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),
                    ])
                    ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('game.title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('game.forumTopic.id')
                    ->label('Forum Topic')
                    ->url(fn (?int $state) => url("viewtopic.php?t={$state}"))
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('game.players_hardcore')
                    ->label('Players')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('game.achievements_published')
                    ->label('Achievements')
                    ->numeric()
                    ->sortable()
                    ->alignEnd(),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                    ])->dropdown(false),
                ]),
            ])
            ->bulkActions([

            ]);
    }


    public static function getRelations(): array
    {
        return [
            AchievementsRelationManager::class,
        ];
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
            'create' => Pages\Create::route('/create'),
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }
}
