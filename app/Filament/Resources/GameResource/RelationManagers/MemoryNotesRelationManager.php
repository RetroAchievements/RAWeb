<?php

declare(strict_types=1);

namespace App\Filament\Resources\GameResource\RelationManagers;

use App\Models\MemoryNote;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MemoryNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'memoryNotes';

    protected static ?string $title = 'Code Notes';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        /** @var User $user */
        $user = auth()->user();

        return $user->can('manage', MemoryNote::class);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function table(Table $table): Table
    {
        $gameId = $this->ownerRecord->id;

        $associatedUsers = User::whereHas('authoredCodeNotes', function (Builder $query) use ($gameId) {
            $query->where('game_id', $gameId);
        })->get();

        return $table
            ->recordTitleAttribute('address')
            ->searchPlaceholder('Search (Body)')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('body', '!=', ''))
            ->columns([
                Tables\Columns\TextColumn::make('address_hex')
                    ->label('Address')
                    ->fontFamily(FontFamily::Mono)
                    ->color('info'), // Add some visual distinction between the address and the body.

                Tables\Columns\TextColumn::make('body')
                    ->label('Body')
                    ->fontFamily(FontFamily::Mono)
                    ->wrap()
                    ->html()
                    ->formatStateUsing(fn (string $state): string => nl2br(e($state)))
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('Author'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Date Updated')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Author')
                    ->options($associatedUsers->pluck('display_name', 'ID'))
                    ->searchable(),
            ])
            ->headerActions([

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->form([
                            Forms\Components\Textarea::make('body')
                                ->label('Body')
                                ->autosize()
                                ->required(),
                        ])
                        ->modalHeading(function (MemoryNote $memoryNote): string {
                            return "Edit Note at {$memoryNote->address_hex}";
                        })
                        ->modalDescription(function (MemoryNote $memoryNote): ?string {
                            /** @var User $user */
                            $user = auth()->user();

                            if ($user->is($memoryNote->user)) {
                                return null;
                            }

                            return "WARNING: By making changes to this note, you will become the author of the note.";
                        })
                        ->modalSubmitActionLabel(function (MemoryNote $memoryNote): string {
                            /** @var User $user */
                            $user = auth()->user();

                            if ($user->is($memoryNote->user)) {
                                return "Save changes";
                            }

                            return "Save changes and become note's author";
                        })
                        ->mutateFormDataUsing(function (array $data): array {
                            /** @var User $user */
                            $user = auth()->user();

                            // The code note will always be "owned" by whoever last edited it.
                            $data['user_id'] = $user->id;

                            return $data;
                        }),

                    Tables\Actions\Action::make('delete')
                        ->label('Delete')
                        ->icon('heroicon-s-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(function (MemoryNote $memoryNote): string {
                            return "Delete Note at {$memoryNote->address_hex}";
                        })
                        ->modalDescription('Are you sure you want to delete this note? It will be irreversibly lost.')
                        ->action(function (MemoryNote $memoryNote): void {
                            /** @var User $user */
                            $user = auth()->user();

                            if (!$user->can('delete', $memoryNote)) {
                                return;
                            }

                            $memoryNote->user_id = $user->id;
                            $memoryNote->forceFill(['body' => '']);
                            $memoryNote->save();
                        })
                        ->visible(function (MemoryNote $memoryNote): bool {
                            /** @var User $user */
                            $user = auth()->user();

                            return $user->can('delete', $memoryNote);
                        }),
                ]),
            ])
            ->paginated([50, 100])
            ->extremePaginationLinks()
            ->recordAction(null);
    }
}
