<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Actions\ApplyUploadedImageToDataAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\EventAchievementResource\Pages;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\User;
use Filament\Forms;
use Filament\Infolists;
use Filament\Pages\Page;
use Filament\Schemas;
use Filament\Schemas\Schema;

class EventAchievementResource extends Resource
{
    protected static ?string $model = EventAchievement::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('source_achievement_id')
                            ->columnSpan(2)
                            ->label('Source Achievement')
                            ->formatStateUsing(function (int $state): string {
                                $achievement = Achievement::find($state);

                                return "[{$achievement->id}] {$achievement->title}";
                            }),

                        Infolists\Components\TextEntry::make('active_from')
                            ->label('Active From')
                            ->date(),

                        Infolists\Components\TextEntry::make('active_through')
                            ->label('Active Through')
                            ->date(),

                        Infolists\Components\TextEntry::make('decorator')
                            ->columnSpan(2)
                            ->label('Decorator'),

                        Infolists\Components\TextEntry::make('achievement.points')
                            ->label('Points'),
                    ])
                    ->columns(['xl' => 4, 'md' => 2]),

                Schemas\Components\Section::make('Source Achievement')
                    ->relationship('sourceAchievement')
                    ->columns(['xl' => 2, '2xl' => 3])
                    ->schema([
                        Schemas\Components\Group::make()
                            ->schema([
                                Infolists\Components\ImageEntry::make('badge_url')
                                    ->label('Badge')
                                    ->size(config('media.icon.lg.width')),
                                Infolists\Components\ImageEntry::make('badge_locked_url')
                                    ->label('Badge (locked)')
                                    ->size(config('media.icon.lg.width')),
                            ]),

                        Schemas\Components\Group::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('title'),

                                Infolists\Components\TextEntry::make('description'),

                                Infolists\Components\TextEntry::make('game')
                                    ->label('Game')
                                    ->formatStateUsing(fn (Game $state) => '[' . $state->id . '] ' . $state->title)
                                    ->url(fn (Achievement $record): string => $record->game->getCanonicalUrlAttribute()),

                                Infolists\Components\TextEntry::make('developer')
                                    ->label('Author')
                                    ->formatStateUsing(fn (User $state) => $state->display_name),
                            ]),

                        Schemas\Components\Group::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('canonical_url')
                                    ->label('Canonical URL')
                                    ->url(fn (Achievement $record): string => $record->getCanonicalUrlAttribute()),

                                Infolists\Components\TextEntry::make('permalink')
                                    ->url(fn (Achievement $record): string => $record->getPermalinkAttribute()),
                            ]),
                    ])
                    ->hidden(fn ($record) => !$record->sourceAchievement),

                Schemas\Components\Section::make()
                    ->relationship('achievement')
                    ->columns(['xl' => 2, '2xl' => 3])
                    ->schema([
                        Schemas\Components\Group::make()
                            ->schema([
                                Infolists\Components\ImageEntry::make('badge_url')
                                    ->label('Badge')
                                    ->size(config('media.icon.lg.width')),

                                Infolists\Components\ImageEntry::make('badge_locked_url')
                                    ->label('Badge (locked)')
                                    ->size(config('media.icon.lg.width')),
                            ]),

                        Schemas\Components\Group::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('title'),

                                Infolists\Components\TextEntry::make('description'),
                            ]),
                    ])
                    ->hidden(fn ($record) => $record->sourceAchievement),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Schemas\Components\Section::make()
                    ->columns(['xl' => 4, 'md' => 2])
                    ->schema([
                        Forms\Components\Select::make('source_achievement_id')
                            ->label('Source Achievement')
                            ->columnSpan(2)
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                // TODO use scout
                                return Achievement::where('title', 'like', "%{$search}%")
                                    ->orWhere('id', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($achievement) {
                                        return [$achievement->id => "[{$achievement->id}] {$achievement->title}"];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function (int $value): string {
                                $achievement = Achievement::find($value);

                                return "[{$achievement->id}] {$achievement->title}";
                            }),

                        Forms\Components\DatePicker::make('active_from')
                            ->label('Active From')
                            ->native(false)
                            ->date(),

                        Forms\Components\DatePicker::make('active_through')
                            ->label('Active Through')
                            ->native(false)
                            ->date(),

                        Forms\Components\TextInput::make('decorator')
                            ->helperText('Short text blurb to describe how the achievement fits into the event (i.e. "Week 1" or "Scott\'s Choice")')
                            ->columnSpan(2)
                            ->maxLength(40),

                        Schemas\Components\Group::make()
                            ->relationship('achievement')
                            ->schema([
                                Forms\Components\Select::make('points')
                                    ->options([
                                        1 => '1',
                                        2 => '2',
                                        3 => '3',
                                        4 => '4',
                                        5 => '5',
                                        10 => '10',
                                        25 => '25',
                                        50 => '50',
                                        100 => '100',
                                    ])
                                    ->default(1)
                                    ->required(),
                            ]),
                    ]),

                Schemas\Components\Section::make()
                    ->relationship('achievement')
                    ->columns(['xl' => 2, '2xl' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(64),

                        Forms\Components\TextInput::make('description')
                            ->required()
                            ->maxLength(255),

                        // Store a temporary file on disk until the user submits.
                        // When the user submits, put in storage.
                        Forms\Components\FileUpload::make('image_name')
                            ->label('Badge')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),
                    ])
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                        (new ApplyUploadedImageToDataAction())->execute($data, 'image_name', ImageUploadType::AchievementBadge);

                        return $data;
                    })
                    ->hidden(fn ($record) => $record?->sourceAchievement)
                    ->columns(2),
            ]);
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
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }
}
