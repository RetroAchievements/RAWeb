<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Actions\ProcessUploadedImageAction;
use App\Filament\Enums\ImageUploadType;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\EventAchievementResource\Pages;
use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;

class EventAchievementResource extends Resource
{
    protected static ?string $model = EventAchievement::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isGloballySearchable = false;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\TextEntry::make('source_achievement_id')
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
                    ])
                    ->columns(['xl' => 4, 'md' => 2]),

                Infolists\Components\Section::make('Source Achievement')
                    ->relationship('sourceAchievement')
                    ->columns(['xl' => 2, '2xl' => 3])
                    ->schema([
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\ImageEntry::make('badge_url')
                                    ->label('Badge')
                                    ->size(config('media.icon.lg.width')),
                                Infolists\Components\ImageEntry::make('badge_locked_url')
                                    ->label('Badge (locked)')
                                    ->size(config('media.icon.lg.width')),
                            ]),

                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('Title'),

                                Infolists\Components\TextEntry::make('Description'),

                                Infolists\Components\TextEntry::make('game')
                                    ->label('Game')
                                    ->formatStateUsing(fn (Game $state) => '[' . $state->id . '] ' . $state->title)
                                    ->url(fn (EventAchievement $record): string => $record->sourceAchievement->game->getCanonicalUrlAttribute()),

                                Infolists\Components\TextEntry::make('developer')
                                    ->label('Author')
                                    ->formatStateUsing(fn (User $state) => $state->display_name),
                            ]),

                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('canonical_url')
                                    ->label('Canonical URL')
                                    ->url(fn (EventAchievement $record): string => $record->sourceAchievement->getCanonicalUrlAttribute()),
                                Infolists\Components\TextEntry::make('permalink')
                                    ->url(fn (EventAchievement $record): string => $record->sourceAchievement->getPermalinkAttribute()),
                            ]),
                    ])
                    ->hidden(fn ($record) => !$record->sourceAchievement),

                Infolists\Components\Section::make()
                    ->relationship('achievement')
                    ->columns(['xl' => 2, '2xl' => 3])
                    ->schema([
                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\ImageEntry::make('badge_url')
                                    ->label('Badge')
                                    ->size(config('media.icon.lg.width')),
                                Infolists\Components\ImageEntry::make('badge_locked_url')
                                    ->label('Badge (locked)')
                                    ->size(config('media.icon.lg.width')),
                            ]),

                        Infolists\Components\Group::make()
                            ->schema([
                                Infolists\Components\TextEntry::make('Title'),

                                Infolists\Components\TextEntry::make('Description'),
                            ]),
                    ])
                    ->hidden(fn ($record) => $record->sourceAchievement),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Section::make()
                    ->columns(['xl' => 4, 'md' => 2])
                    ->schema([
                        Forms\Components\Select::make('source_achievement_id')
                            ->label('Source Achievement')
                            ->columnSpan(2)
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                return Achievement::where('Title', 'like', "%{$search}%")
                                    ->orWhere('ID', 'like', "%{$search}%")
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
                    ]),

                Forms\Components\Section::make()
                    ->relationship('achievement')
                    ->columns(['xl' => 2, '2xl' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('Title')
                            ->required()
                            ->maxLength(64),

                        Forms\Components\TextInput::make('Description')
                            ->required()
                            ->maxLength(255),

                        // Store a temporary file on disk until the user submits.
                        // When the user submits, put in storage.
                        Forms\Components\FileUpload::make('BadgeName')
                            ->label('Badge')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpg', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1)
                            ->previewable(true),
                    ])
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                        if (isset($data['BadgeName'])) {
                            $data['BadgeName'] = (new ProcessUploadedImageAction())->execute($data['BadgeName'], ImageUploadType::AchievementBadge);
                        } else {
                            unset($data['BadgeName']); // prevent clearing out existing value
                        }

                        return $data;
                    })
                    ->hidden(fn ($record) => $record->sourceAchievement)
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
