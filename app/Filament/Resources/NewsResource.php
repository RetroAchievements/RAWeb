<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Extensions\Resources\Resource;
use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationGroup = 'Community';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    protected static int $globalSearchResultsLimit = 2;

    public static function form(Form $form): Form
    {
        /** @var User $user */
        $user = Auth::user();

        return $form
            ->schema([
                Forms\Components\Section::make('Primary Details')
                    ->icon('heroicon-m-key')
                    ->columns(['md' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->minLength(2)
                            ->maxLength(60),

                        Forms\Components\TextInput::make('link')
                            ->label('URL')
                            ->required()
                            ->activeUrl(),

                        Forms\Components\Toggle::make('pinned_at')
                            ->label('Pinned')
                            ->helperText('If enabled, this will be sorted to the top of the news until unpinned.')
                            ->columnSpanFull()
                            ->disabled(fn (?News $record) => !$record || !$user->can('pin', $record))
                            ->dehydrated()
                            ->afterStateHydrated(function (?News $record, $component) {
                                if (!$record) {
                                    $component->state(false);

                                    return;
                                }

                                $component->state(!is_null($record->pinned_at));
                            })
                            ->dehydrateStateUsing(function (?News $record, bool $state) {
                                return $state ? now()->toDateTimeString() : null;
                            })
                            ->live(),
                    ]),

                Forms\Components\Section::make('Leading Text')
                    ->icon('heroicon-m-chat-bubble-oval-left-ellipsis')
                    ->description("
                        This short description will appear on the site's home page and in any news feed features we support.
                        This should be a VERY short plain-text summary of what the user can find at the URL. Anticipate that
                        users will only spend 5 seconds or less to read this, so be very concise!
                    ")
                    ->schema([
                        Forms\Components\Textarea::make('body') // TODO use `lead` field instead
                            ->label('Lead')
                            ->maxLength(238) // We have some guardrails so people don't go crazy with these.
                            ->helperText(function (?string $state, Forms\Components\Textarea $component) {
                                $maxLength = $component->getMaxLength();

                                return new HtmlString('
                                    <div class="flex w-full justify-between">' .
                                        '<p>Summaries should be a single sentence/paragraph.</p>' .

                                        '<p>' .
                                            strlen($state ?? '') . '/' . $maxLength .
                                        '</p>' .
                                    '</div>'
                                );
                            })
                            ->live(debounce: 1000) // add debounce to mitigate users getting rate limited while typing
                            ->rows(4)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Image')
                    ->schema([
                        // Store a temporary file on disk until the user submits.
                        // When the user submits, upload to S3.
                        Forms\Components\FileUpload::make('image_asset_path')
                            ->label('Upload an image')
                            ->disk('livewire-tmp') // Use Livewire's self-cleaning temporary disk
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                            ->maxSize(1024)
                            ->maxFiles(1),

                        Forms\Components\Placeholder::make('ImagePreview')
                            ->label('Image preview')
                            ->content(function (News $news) {
                                return new HtmlString("<img src='{$news->image_asset_path}' style='width:197px; height:112px;' class='rounded object-cover'>");
                            })
                            ->visible(fn (News $news) => !is_null($news->image_asset_path)),
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

                Tables\Columns\TextColumn::make('link')
                    ->label('URL'),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Author'),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                return $query->where('created_at', '>=', '2024-01-01');
            })
            ->defaultSort('created_at', 'desc')
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
