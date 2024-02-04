<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        if (Role::count() > 0) {
            return;
        }

        foreach (config('roles') as $roleData) {
            // make sure role is assigned to the web guard - not the legacy web guard
            $roleData['guard_name'] = 'web';
            $role = Role::create(Arr::except($roleData, ['assign', 'permissions', 'staff', 'legacy_role']));
            if (!empty($roleData['permissions'])) {
                foreach ($roleData['permissions'] as $permissionName) {
                    Permission::findOrCreate($permissionName);
                    $role->givePermissionTo($permissionName);
                }
            }
        }
    }
}
