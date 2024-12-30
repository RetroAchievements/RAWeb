<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Components\BreadcrumbPreview;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\HubResource\Pages;
use App\Filament\Resources\HubResource\RelationManagers\GamesRelationManager;
use App\Filament\Resources\HubResource\RelationManagers\ParentHubsRelationManager;
use App\Filament\Rules\ExistsInForumTopics;
use App\Models\GameSet;
use App\Models\User;
use App\Platform\Enums\GameSetType;
use App\Support\Rules\NoEmoji;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HubResource extends Resource
{
    protected static ?string $model = GameSet::class;

    protected static ?string $modelLabel = 'Hub';
    protected static ?string $pluralModelLabel = 'Hubs';
    protected static ?string $breadcrumb = 'Hubs';
    protected static ?string $navigationIcon = 'fas-sitemap';
    protected static ?string $navigationGroup = 'Platform';
    protected static ?string $navigationLabel = 'Hubs';
    protected static ?int $navigationSort = 51;
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
                            ->url(fn (GameSet $record): string => $record->getPermalinkAttribute())
                            ->extraAttributes(['class' => 'underline'])
                            ->openUrlInNewTab(),

                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('title')
                            ->label('Title'),

                        Infolists\Components\TextEntry::make('forumTopic.id')
                            ->label('Forum Topic ID')
                            ->url(fn (?int $state) => url("viewtopic.php?t={$state}"))
                            ->placeholder('none')
                            ->extraAttributes(fn (?int $state) => $state ? ['class' => 'underline'] : []),
                            
                        Infolists\Components\TextEntry::make('has_mature_content')
                            ->label('Has Mature Content')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Yes' : 'No')
                            ->color(fn (bool $state): string => $state ? 'danger' : ''),

                        BreadcrumbPreview::make('breadcrumbs')
                            ->label('Breadcrumb Preview')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Internal Notes')
                    ->icon('heroicon-c-chat-bubble-bottom-center-text')
                    ->schema([
                        Infolists\Components\TextEntry::make('internal_notes')
                            ->hiddenLabel()
                            ->placeholder('none'),
                    ]),
            ]);
    }

    public static function form(Form $form): Form
    {
        /** @var User $user */
        $user = Auth::user();

        return $form
            ->schema([
                Forms\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2, 'xl' => 3, '2xl' => 4])
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->minLength(2)
                            ->maxLength(80)
                            ->rules([new NoEmoji()]),

                        Forms\Components\TextInput::make('forum_topic_id')
                            ->label('Forum Topic ID')
                            ->numeric()
                            ->rules([new ExistsInForumTopics()])
                            ->helperText('Before connecting a topic, be ABSOLUTELY SURE the internal notes field below is not sufficient.'),
                            
                        Forms\Components\Toggle::make('has_mature_content')
                            ->label('Has Mature Content')
                            ->helperText('CAUTION: If this is enabled, players will get a warning when opening any game in the hub!')
                            ->default(false)
                            ->visible(fn ($record) => $user->can('toggleHasMatureContent', $record)),
                    ]),

                Forms\Components\Section::make('Internal Notes')
                    ->icon('heroicon-c-chat-bubble-bottom-center-text')
                    ->description('Use this field to document the purpose of the hub. Documentation might include the purpose of the hub, or what games should/shouldn\'t be added to the hub.')
                    ->schema([
                        Forms\Components\Textarea::make('internal_notes')
                            ->hiddenLabel(),
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
                            ->previewable(false),
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

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('games_count')
                    ->label('Related Games')
                    ->sortable(),

                Tables\Columns\TextColumn::make('parents_count')
                    ->label('Related Hubs')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('visit')
                        ->label('View on Site')
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->url(fn (GameSet $record): string => route('hub.show', $record))
                        ->openUrlInNewTab(),

                    Tables\Actions\ViewAction::make()
                        ->label('Manage'),

                    Tables\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([

            ])
            ->paginated([50, 100, 150]);
    }

    public static function getRelations(): array
    {
        return [
            GamesRelationManager::class,
            ParentHubsRelationManager::class,
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
     * @return Builder<GameSet>
     */
    public static function getEloquentQuery(): Builder
    {
        /** @var Builder<GameSet> $query */
        $query = parent::getEloquentQuery();

        return $query->whereType(GameSetType::Hub)
            ->withCount(['games', 'parents']);
    }
}
