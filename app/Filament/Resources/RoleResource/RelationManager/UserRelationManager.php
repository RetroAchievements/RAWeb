<?php

declare(strict_types=1);

namespace App\Filament\Resources\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UserRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'User';

    public function table(Table $table): Table
    {
        return $table
            ->heading(__res('user'))
            ->columns([
                Tables\Columns\TextColumn::make('User')
                    ->searchable(),
            ])
            ->paginationPageOptions([10, 25, 50])
            ->filters([

            ])
            ->deferFilters()
            ->headerActions([
                Tables\Actions\AttachAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DetachAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                ]),
            ]);
    }
}
