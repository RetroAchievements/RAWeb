<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\Permissions;
use App\Filament\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role as SpatieRole;

class Roles extends ManageRelatedRecords
{
    protected static string $resource = UserResource::class;

    protected static string $relationship = 'roles';

    protected static ?string $navigationIcon = 'fas-lock';

    public function table(Table $table): Table
    {
        /** @var User $user */
        $user = Auth::user();

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
                    ->authorize(fn () => $user->can('updateRoles', $this->getRecord()))
                    ->recordTitle(fn (Model $record) => __('permission.role.' . $record->name))
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->whereIn('name', $user->assignableRoles))
                    ->after(function ($data) {
                        /** @var User $targetUser */
                        $targetUser = $this->getRecord();

                        $attachedRole = Role::findById((int) $data['recordId']);

                        if ($attachedRole->name === Role::DEVELOPER_JUNIOR) {
                            $targetUser->removeRole(Role::DEVELOPER);
                            $targetUser->removeRole(Role::DEVELOPER_STAFF);
                            $targetUser->removeRole(Role::DEVELOPER_RETIRED);

                            $newPermissions = Permissions::JuniorDeveloper;
                        } elseif ($attachedRole->name === Role::DEVELOPER) {
                            $targetUser->removeRole(Role::DEVELOPER_JUNIOR);
                            $targetUser->removeRole(Role::DEVELOPER_STAFF);
                            $targetUser->removeRole(Role::DEVELOPER_RETIRED);

                            $newPermissions = Permissions::Developer;
                        } elseif ($attachedRole->name === Role::DEVELOPER_STAFF) {
                            $targetUser->removeRole(Role::DEVELOPER_JUNIOR);
                            $targetUser->removeRole(Role::DEVELOPER);
                            $targetUser->removeRole(Role::DEVELOPER_RETIRED);

                            $newPermissions = Permissions::Developer;
                        } elseif ($attachedRole->name === Role::DEVELOPER_RETIRED) {
                            $targetUser->removeRole(Role::DEVELOPER_JUNIOR);
                            $targetUser->removeRole(Role::DEVELOPER);
                            $targetUser->removeRole(Role::DEVELOPER_STAFF);

                            $newPermissions = Permissions::Registered;
                        } else {
                            // There's nothing to synchronize for non-developer roles.
                            return;
                        }

                        // Only update permissions if the user isn't already a moderator.
                        $currentPermissions = (int) $targetUser->getAttribute('Permissions');
                        if ($currentPermissions < Permissions::Moderator) {
                            $targetUser->setAttribute('Permissions', $newPermissions);
                            $targetUser->save();
                        }
                    }),
            ])
            ->paginated(false)
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label(__('Remove'))
                    ->authorize(fn (SpatieRole $record) => $user->can('detachRole', [$this->getRecord(), $record]))
                    ->after(function (SpatieRole $record) {
                        /** @var User $targetUser */
                        $targetUser = $this->getRecord();

                        // Only reset permissions if it's a developer role being removed.
                        // Users can only have a single kind of developer role attached.
                        if (!in_array($record->name, [
                            Role::DEVELOPER_JUNIOR,
                            Role::DEVELOPER,
                            Role::DEVELOPER_STAFF,
                            Role::DEVELOPER_RETIRED,
                        ])) {
                            return;
                        }

                        // Keep legacy permissions in sync.
                        $currentPermissions = (int) $targetUser->getAttribute('Permissions');
                        // Don't strip moderation power away if the user already has it.
                        if ($currentPermissions < Permissions::Moderator) {
                            $targetUser->setAttribute('Permissions', Permissions::Registered);
                            $targetUser->save();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }
}
