<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Actions\ProcessUploadedImageAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\EventResource\Pages;
use App\Filament\Resources\EventResource\RelationManagers\AchievementsRelationManager;
use App\Filament\Resources\EventResource\RelationManagers\EventAwardsRelationManager;
use App\Filament\Resources\EventResource\RelationManagers\HubsRelationManager;
use App\Filament\Rules\ExistsInForumTopics;
use App\Models\Event;
use App\Models\EventAchievement;
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
    protected static ?string $recordTitleAttribute = 'legacyGame.title';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record->legacyGame->title ?? '';
    }

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
                        Infolists\Components\TextEntry::make('legacyGame.title')
                            ->label('Title'),

                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('permalink')
                            ->formatStateUsing(fn () => 'Here')
                            ->url(fn (Event $record): string => $record->getPermalinkAttribute())
                            ->extraAttributes(['class' => 'underline'])
                            ->openUrlInNewTab(),

                        Infolists\Components\TextEntry::make('legacyGame.forumTopic.id')
                            ->label('Forum Topic ID')
                            ->url(fn (?int $state) => $state ? route('forum-topic.show', ['topic' => $state]) : null)
                            ->extraAttributes(['class' => 'underline']),

                        Infolists\Components\TextEntry::make('active_from')
                            ->label('Active From')
                            ->date(),

                        Infolists\Components\TextEntry::make('active_through')
                        ->label('Active Through')
                        ->date(),
                    ]),

                Infolists\Components\Section::make('Metrics')
                    ->icon('heroicon-s-arrow-trending-up')
                    ->description("
                        Statistics regarding the game's players and achievements can be found here.
                    ")
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Infolists\Components\TextEntry::make('legacyGame.players_total')
                            ->label('Players')
                            ->numeric(),

                        Infolists\Components\TextEntry::make('legacyGame.achievements_published')
                            ->label('Achievements')
                            ->numeric(),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        $isNew = !is_a($form->model, Event::class);

        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->relationship('legacyGame')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('Title')
                            ->label('Title')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->minLength(2)
                            ->maxLength(80),

                        Forms\Components\TextInput::make('ForumTopicID')
                            ->label('Forum Topic ID')
                            ->numeric()
                            ->rules([new ExistsInForumTopics()]),
                    ]),

                Forms\Components\Section::make()
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\DatePicker::make('active_from')
                            ->label('Active From')
                            ->native(false)
                            ->date(),

                        Forms\Components\DatePicker::make('active_through')
                            ->label('Active Through')
                            ->native(false)
                            ->date(),
                    ]),

                Forms\Components\Section::make('Achievements')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('numberOfAchievements')
                            ->label('Number of achievements')
                            ->numeric()
                            ->default(6)
                            ->required(),

                        Forms\Components\Select::make('user_id')
                            ->label('Username to use as author of new achievements')
                            ->options([
                                EventAchievement::RAEVENTS_USER_ID => "RAEvents",
                                EventAchievement::DEVQUEST_USER_ID => "DevQuest",
                                EventAchievement::QATEAM_USER_ID => "QATeam",
                            ])
                            ->default(EventAchievement::RAEVENTS_USER_ID)
                            ->required(),
                    ])
                    ->visible($isNew),

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
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->required($isNew)
                            ->previewable(true),
                    ])
                    ->columns(2),

                // TODO: move these to events table with better names
                //       apparently, some events actually desire to have them:
                //       https://discord.com/channels/310192285306454017/758865736072167474/1326712584623099927
                Forms\Components\Section::make('Media from Game Record')
                    ->icon('heroicon-s-photo')
                    ->relationship('legacyGame')
                    ->schema([
                        // Store a temporary file on disk until the user submits.
                        // When the user submits, put in storage.
                        Forms\Components\FileUpload::make('ImageTitle')
                            ->label('Title')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),

                        Forms\Components\FileUpload::make('ImageIngame')
                            ->label('In Game')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),

                        Forms\Components\FileUpload::make('ImageBoxArt')
                            ->label('Box Art')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),
                    ])
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                        if (isset($data['ImageBoxArt'])) {
                            $data['ImageBoxArt'] = (new ProcessUploadedImageAction())->execute($data['ImageBoxArt'], ImageUploadType::GameBoxArt);
                        } else {
                            unset($data['ImageBoxArt']); // prevent clearing out existing value
                        }

                        if (isset($data['ImageTitle'])) {
                            $data['ImageTitle'] = (new ProcessUploadedImageAction())->execute($data['ImageTitle'], ImageUploadType::GameTitle);
                        } else {
                            unset($data['ImageTitle']); // prevent clearing out existing value
                        }

                        if (isset($data['ImageIngame'])) {
                            $data['ImageIngame'] = (new ProcessUploadedImageAction())->execute($data['ImageIngame'], ImageUploadType::GameInGame);
                        } else {
                            unset($data['ImageIngame']); // prevent clearing out existing value
                        }

                        return $data;
                    })
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query) {
                $query->orderBy('active_until', 'desc')
                    ->join('GameData', 'events.legacy_game_id', '=', 'GameData.ID') // TODO: should be 'title' once it's moved out of GameData
                    ->orderBy('GameData.title');
            })
            ->columns([
                Tables\Columns\ImageColumn::make('badge_url')
                    ->label('')
                    ->size(config('media.icon.sm.width')),

                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where('events.id', 'like', "%{$search}%");
                    }),

                Tables\Columns\TextColumn::make('legacyGame.title')
                    ->label('Title')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('active_from')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('active_through')
                    ->date()
                    ->sortable(['active_until'])
                    ->toggleable(),

                Tables\Columns\TextColumn::make('legacyGame.forumTopic.id')
                    ->label('Forum Topic')
                    ->url(fn (?int $state) => $state ? route('forum-topic.show', ['topic' => $state]) : null)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('legacyGame.players_hardcore')
                    ->label('Players')
                    ->numeric()
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('legacyGame.achievements_published')
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
            EventAwardsRelationManager::class,
            HubsRelationManager::class,
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

    /**
     * @return Builder<Event>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['legacyGame']);
    }
}
