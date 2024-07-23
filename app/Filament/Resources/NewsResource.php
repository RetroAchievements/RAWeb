<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Community';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'Title';

    protected static int $globalSearchResultsLimit = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('Title')
                            ->required()
                            ->minLength(2)
                            ->maxLength(60),

                        Forms\Components\TextInput::make('Link')
                            ->label('URL')
                            ->required()
                            ->activeUrl(),
                    ]),

                Forms\Components\Section::make('Leading Text')
                    ->icon('heroicon-m-chat-bubble-oval-left-ellipsis')
                    ->description("
                        This short description will appear on the site's home page and in any news feed features we support.
                        This should be a VERY short plain-text summary of what the user can find at the URL. Anticipate that
                        users will only spend 5 seconds or less to read this, so be very concise!
                    ")
                    ->schema([
                        Forms\Components\Textarea::make('Payload')
                            ->label('Lead')
                            ->maxLength(238) // Have some guardrails so people don't go crazy with these.
                            ->helperText(function (?string $state, Forms\Components\Textarea $component) {
                                $maxLength = $component->getMaxLength();

                                return new HtmlString('
                                    <div class="flex w-full justify-between">' .
                                        '<p>HTML is not supported.</p>' .

                                        '<p>' .
                                            strlen($state ?? '') . '/' . $maxLength .
                                        '</p>' .
                                    '</div>'
                                );
                            })
                            ->reactive()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Image')
                    ->schema([
                        // Store a temporary file on disk until the user submits.
                        // When the user submits, upload to S3.
                        Forms\Components\FileUpload::make('Image')
                            ->label('Upload an image')
                            ->disk('local')
                            ->directory('temp')
                            ->visibility('private')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1),

                        Forms\Components\Placeholder::make('ImagePreview')
                            ->content(function (News $news) {
                                return new HtmlString("<img src='{$news->Image}' style='width:700px; height:270px;' class='rounded object-cover'>");
                            })
                            ->visible(fn (News $news) => !is_null($news->Image)),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('Timestamp')
                    ->label('Created at')
                    ->dateTime(),

                Tables\Columns\TextColumn::make('Link')
                    ->label('URL'),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Author'),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('Timestamp', '>=', '2024-01-01');
            })
            ->defaultSort('Timestamp', 'desc')
            ->searchPlaceholder('Search (Title)')
            ->filters([

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([

            ]);
    }

    public static function getRelations(): array
    {
        return [

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
}
