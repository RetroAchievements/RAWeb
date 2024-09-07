<?php

declare(strict_types=1);

namespace App\Filament\Resources\AchievementResource\RelationManagers;

use App\Filament\Resources\AchievementAuthorshipCreditFormSchema;
use App\Models\AchievementAuthor;
use App\Platform\Enums\AchievementAuthorTask;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AuthorshipCreditsRelationManager extends RelationManager
{
    protected static string $relationship = 'authorshipCredits';

    protected static ?string $title = 'Credits';

    public function form(Form $form): Form
    {
        return $form
            ->schema(AchievementAuthorshipCreditFormSchema::getSchema());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.display_name')
                    ->label('User')
                    ->url(fn (AchievementAuthor $record): ?string => $record->user ? route('user.show', ['user' => $record->user]) : null),

                Tables\Columns\TextColumn::make('task')
                    ->label('Task')
                    ->formatStateUsing(fn ($state) => AchievementAuthorTask::tryFrom($state)?->label() ?? ucfirst($state)),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date Credited'),
            ])
            ->filters([

            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add achievement credit')
                    ->modalHeading('Add achievement credit'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit achievement credit'),

                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete achievement credit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
