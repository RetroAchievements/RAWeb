<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\RelationManager;

use App\Filament\Resources\UserResource;
use App\Site\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class UserRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'User';

    public function table(Table $table): Table
    {
        // TODO using the resource's table inherits all the actions which open in empty modals
        // return UserResource::table($table)
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('User')
                    ->url(fn (User $record) => UserResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultSort('User', 'asc')
            ->headerActions([
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label(__('Remove'))
                    ->authorize(fn (User $record) => auth()->user()->can('detachRole', [$record, $this->getOwnerRecord()])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }
}
