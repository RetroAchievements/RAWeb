<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\IntegrationReleaseResource\Pages;
use App\Filament\Rules\DisallowHtml;
use App\Models\IntegrationRelease;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IntegrationReleaseResource extends Resource
{
    protected static ?string $model = IntegrationRelease::class;

    protected static ?string $label = 'Integration';

    protected static ?string $navigationIcon = 'fas-puzzle-piece';

    protected static ?string $navigationGroup = 'Releases';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'version';

    protected static int $globalSearchResultsLimit = 5;

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->columns(1)
            ->schema([
                Infolists\Components\Split::make([
                    Infolists\Components\Section::make('Details')
                        ->schema([
                            Infolists\Components\Group::make()
                                ->columns(['xl' => 2, '2xl' => 2])
                                ->schema([
                                    Infolists\Components\TextEntry::make('version')
                                        ->label('Version'),

                                    Infolists\Components\TextEntry::make('notes')
                                        ->label('Notes')
                                        ->placeholder('none'),
                                ]),
                        ]),

                    Infolists\Components\Section::make([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),

                        Infolists\Components\TextEntry::make('Released')
                            ->label('Released at')
                            ->dateTime(),

                        Infolists\Components\IconEntry::make('stable')
                            ->label('An official release')
                            ->boolean(),

                        Infolists\Components\IconEntry::make('minimum')
                            ->label('Oldest version supported by the server')
                            ->boolean(),
                    ])->grow(false),
                ])->from('md'),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Split::make([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\TextInput::make('version'),

                            Forms\Components\DatePicker::make('created_at')
                                ->label('Release Date')
                                ->native(false)
                                ->default(now())
                                ->date(),

                            Forms\Components\Textarea::make('notes')
                                ->label('Notes')
                                ->rules([new DisallowHtml()])
                                ->rows(10),
                        ]),

                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Toggle::make('stable')
                                ->label('An official release'),

                            Forms\Components\Toggle::make('minimum')
                                ->label('Oldest version supported by the server'),
                        ]),
                ])->from('md'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->searchable()
                    ->sortable()
                    ->grow(true),

                Tables\Columns\IconColumn::make('stable')
                    ->label('Official')
                    ->boolean()
                    ->default(false)
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('minimum')
                    ->boolean()
                    ->default(false)
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Released')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('version', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->deferFilters()
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ActionGroup::make([
                        Tables\Actions\ViewAction::make(),
                        Tables\Actions\EditAction::make(),
                        Tables\Actions\DeleteAction::make(),
                        Tables\Actions\RestoreAction::make(),
                    ])->dropdown(false),

                    Tables\Actions\Action::make('audit-log')
                        ->url(fn ($record) => IntegrationReleaseResource::getUrl('audit-log', ['record' => $record]))
                        ->icon('fas-clock-rotate-left'),
                ]),
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
            'create' => Pages\Create::route('/create'),
            'view' => Pages\Details::route('/{record}'),
            'edit' => Pages\Edit::route('/{record}/edit'),
            'audit-log' => Pages\AuditLog::route('/{record}/audit-log'),
        ];
    }

    /**
     * @return Builder<IntegrationRelease>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
