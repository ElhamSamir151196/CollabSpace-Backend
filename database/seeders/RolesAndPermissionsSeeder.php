<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Safe to run many times: it creates what is missing and re-syncs
 * each role with the permissions defined in App\Enums\Permission::forRole().
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value)->syncPermissions(
                array_map(fn (PermissionEnum $p) => $p->value, PermissionEnum::forRole($role)),
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
