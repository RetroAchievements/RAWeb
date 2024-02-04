<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\RelationManager;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class Users extends RelationManager
{
    protected static string $relationship = 'users';

    public function table(Table $table): Table
    {
        // TODO using the resource's table inherits all the actions which open in empty modals
        // see https://github.com/filamentphp/filament/issues/9492
        // return UserResource::table($table)
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('')
                    ->size(config('media.icon.sm.width'))
                    ->url(fn (User $record) => UserResource::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('User')
                    ->url(fn (User $record) => UserResource::getUrl('view', ['record' => $record]))
                    ->grow(true),
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
