<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Site\Models\Role;
use App\Site\Models\User;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

class ManageUserRoles extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'roles';

    protected static ?string $navigationIcon = 'fas-lock';

    public static function getNavigationLabel(): string
    {
        return __res('role');
    }

    public function getSubheading(): ?string
    {
        /** @var User $record */
        $record = $this->getRecord();

        return '[' . $record->ID . '] ' . $record->User;
    }

    public function table(Table $table): Table
    {
        // TODO using the resource's table inherits all the actions which open in empty modals
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
                    ->recordTitleAttribute('name')
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
