<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Role;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;

class Roles extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'roles';

    protected static ?string $navigationIcon = 'fas-lock';

    public function table(Table $table): Table
    {
        // TODO using the resource's table inherits all the actions which open in empty modals
        // see https://github.com/filamentphp/filament/issues/9492
        // return RoleResource::table($table)
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('permission.role.' . $state))
                    ->color(fn (string $state): string => Role::toFilamentColor($state)),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label(__('Add'))
                    ->color('primary')
                    ->authorize(fn () => auth()->user()->can('updateRoles', $this->getRecord()))
                    ->recordTitle(fn (Model $record) => __('permission.role.' . $record->name))
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereIn('name', auth()->user()->assignableRoles)),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label(__('Remove'))
                    ->authorize(fn (SpatieRole $record) => auth()->user()->can('detachRole', [$this->getRecord(), $record])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }
}
