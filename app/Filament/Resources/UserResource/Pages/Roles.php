<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\Permissions;
use App\Filament\Resources\UserResource;
use App\Models\AchievementMaintainer;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
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
                    ->recordSelectOptionsQuery(function (Builder $query) {
                        /** @var User $targetUser */
                        $targetUser = $this->getRecord();

                        // Start with basic role filtering based on user permissions.
                        $query->whereIn('name', Auth::user()->assignableRoles);

                        // If trying to assign staff developer roles, ensure the user has Role::DEVELOPER.
                        $staffRoles = [Role::QUALITY_ASSURANCE, Role::DEV_COMPLIANCE, Role::CODE_REVIEWER];
                        if (!$targetUser->hasRole(Role::DEVELOPER)) {
                            $query->whereNotIn('name', $staffRoles);
                        }

                        return $query;
                    })
                    ->form(function ($form) {
                        /** @var User $targetUser */
                        $targetUser = $this->getRecord();

                        $query = Role::query();

                        $query->whereIn('name', Auth::user()->assignableRoles);

                        // If trying to assign staff developer roles, ensure the user has Role::DEVELOPER.
                        $staffRoles = [Role::QUALITY_ASSURANCE, Role::DEV_COMPLIANCE, Role::CODE_REVIEWER];
                        if (!$targetUser->hasRole(Role::DEVELOPER)) {
                            $query->whereNotIn('name', $staffRoles);
                        }

                        // Get all matching roles and sort by translated label.
                        $options = $query->get()
                            ->mapWithKeys(fn (Role $role) => [$role->id => __('permission.role.' . $role->name)])
                            ->sort()
                            ->toArray();

                        return [
                            Forms\Components\Select::make('recordId')
                                ->label('Role')
                                ->options($options)
                                ->required()
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search) {
                                    /** @var User $targetUser */
                                    $targetUser = $this->getRecord();

                                    $query = Role::query();

                                    $query->whereIn('name', Auth::user()->assignableRoles);

                                    // If trying to assign staff developer roles, ensure the user has Role::DEVELOPER.
                                    $staffRoles = [Role::QUALITY_ASSURANCE, Role::DEV_COMPLIANCE, Role::CODE_REVIEWER];
                                    if (!$targetUser->hasRole(Role::DEVELOPER)) {
                                        $query->whereNotIn('name', $staffRoles);
                                    }

                                    // Get all roles and filter by translated label.
                                    return $query->get()
                                        ->mapWithKeys(fn (Role $role) => [$role->id => __('permission.role.' . $role->name)])
                                        ->filter(fn ($label) => str_contains(strtolower($label), strtolower($search)))
                                        ->sort()
                                        ->toArray();
                                }),
                        ];
                    })
                    ->after(function ($data) {
                        /** @var User $targetUser */
                        $targetUser = $this->getRecord();

                        $attachedRole = Role::findById((int) $data['recordId']);

                        if ($attachedRole->name === Role::DEVELOPER_JUNIOR) {
                            $targetUser->removeRole(Role::DEVELOPER);
                            $targetUser->removeRole(Role::DEVELOPER_RETIRED);
                            $this->removeDeveloperStaffRoles($targetUser); // jr devs cannot be staff

                            $newPermissions = Permissions::JuniorDeveloper;
                        } elseif ($attachedRole->name === Role::DEVELOPER) {
                            $targetUser->removeRole(Role::DEVELOPER_JUNIOR);
                            $targetUser->removeRole(Role::DEVELOPER_RETIRED);

                            $newPermissions = Permissions::Developer;
                        } elseif ($attachedRole->name === Role::DEVELOPER_RETIRED) {
                            $targetUser->removeRole(Role::DEVELOPER_JUNIOR);
                            $targetUser->removeRole(Role::DEVELOPER);
                            $this->removeDeveloperStaffRoles($targetUser); // retired devs cannot be staff

                            $newPermissions = Permissions::Registered;
                        } else {
                            // There's nothing to synchronize for non-developer roles.
                            return;
                        }

                        // Only update permissions if the user isn't already a moderator.
                        $currentPermissions = (int) $targetUser->getAttribute('Permissions');
                        if ($currentPermissions < Permissions::Moderator) {
                            $targetUser->setAttribute('Permissions', $newPermissions);
                            $targetUser->saveQuietly();
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
                            Role::DEVELOPER_RETIRED,
                        ])) {
                            return;
                        }

                        // When manually detaching any dev role, remove all staff dev roles.
                        $this->removeDeveloperStaffRoles($targetUser);

                        // Keep legacy permissions in sync.
                        $currentPermissions = (int) $targetUser->getAttribute('Permissions');
                        // Don't strip moderation power away if the user already has it.
                        if ($currentPermissions < Permissions::Moderator) {
                            $targetUser->setAttribute('Permissions', Permissions::Registered);
                            $targetUser->saveQuietly();
                        }

                        // If the user is losing their developer role, also expire any active maintainerships.
                        if ($record->name === Role::DEVELOPER) {
                            AchievementMaintainer::query()
                                ->where('user_id', $targetUser->id)
                                ->where('is_active', true)
                                ->whereNull('effective_until')
                                ->update([
                                    'is_active' => false,
                                    'effective_until' => now(),
                                ]);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([]),
            ]);
    }

    private function removeDeveloperStaffRoles(User $targetUser): void
    {
        if ($targetUser->hasRole(Role::QUALITY_ASSURANCE)) {
            $targetUser->removeRole(Role::QUALITY_ASSURANCE);
        }
        if ($targetUser->hasRole(Role::DEV_COMPLIANCE)) {
            $targetUser->removeRole(Role::DEV_COMPLIANCE);
        }
        if ($targetUser->hasRole(Role::CODE_REVIEWER)) {
            $targetUser->removeRole(Role::CODE_REVIEWER);
        }
    }
}
