<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\ModerationWatchlistTermResource\Pages;
use App\Models\ModerationWatchlistTerm;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ModerationWatchlistTermResource extends Resource
{
    protected static ?string $model = ModerationWatchlistTerm::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-eye';
    protected static string|UnitEnum|null $navigationGroup = 'Community';
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationLabel = 'Watched Terms';
    protected static ?string $modelLabel = 'Watched Term';
    protected static ?string $pluralModelLabel = 'Watched Terms';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('term')
                    ->label('Term')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
                        'term',
                        mb_strtolower(trim((string) $state)),
                    ))
                    ->minLength(ModerationWatchlistTerm::MINIMUM_TERM_LENGTH)
                    ->maxLength(80)
                    ->unique(ignoreRecord: true)
                    ->helperText(
                        "We'll catch it in any casing, even inside a longer word or a link. "
                        . ModerationWatchlistTerm::MINIMUM_TERM_LENGTH
                        . ' characters minimum, since shorter ones turn up in everyday writing.'
                    ),

                Forms\Components\TextInput::make('note')
                    ->label('Note')
                    ->maxLength(255)
                    ->helperText('Optional. Why you started watching this, for whoever reads the list next.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('term')
                    ->label('Term')
                    ->sortable(),

                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->placeholder('-')
                    ->limit(80),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('term')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),

                    Action::make('audit-log')
                        ->url(fn ($record) => self::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\Edit::class,
            Pages\AuditLog::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'create' => Pages\Create::route('/create'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }
}
