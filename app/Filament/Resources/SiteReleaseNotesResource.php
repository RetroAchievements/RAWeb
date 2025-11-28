<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Community\Enums\NewsCategory;
use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\SiteReleaseNotesResource\Pages;
use App\Models\News;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SiteReleaseNotesResource extends Resource
{
    protected static ?string $model = News::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|UnitEnum|null $navigationGroup = 'Releases';
    protected static ?int $navigationSort = 2;
    protected static ?string $recordTitleAttribute = 'title';
    protected static ?string $navigationLabel = 'Site Release Notes';
    protected static ?string $modelLabel = 'Site Release Note';
    protected static ?string $pluralModelLabel = 'Site Release Notes';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Schemas\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->minLength(2)
                            ->maxLength(60),

                        Forms\Components\TextInput::make('link')
                            ->label('URL')
                            ->activeUrl()
                            ->nullable()
                            ->helperText('Optional link to GitHub release, docs, etc.'),
                    ]),

                Schemas\Components\Section::make('Release Notes Content')
                    ->icon('heroicon-m-document-text')
                    ->description('Write your release notes in Markdown. This content will appear in the "Latest Site Updates" dialog on the home page.')
                    ->schema([
                        Forms\Components\MarkdownEditor::make('body')
                            ->label('Content')
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Author'),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('category', NewsCategory::SiteReleaseNotes);
            })
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Search (Title)')
            ->filters([

            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function canViewAny(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user?->can('manageSiteReleaseNotes', News::class) ?? false;
    }

    public static function canCreate(): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user?->can('manageSiteReleaseNotes', News::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user?->can('manageSiteReleaseNotes', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var User $user */
        $user = Auth::user();

        return $user?->can('manageSiteReleaseNotes', $record) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\Index::route('/'),
            'create' => Pages\Create::route('/create'),
            'edit' => Pages\Edit::route('/{record}/edit'),
        ];
    }
}
