<?php

namespace Modules\Admins\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Interfaces\RolesInterface;

class RolesDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = config('admins.base_roles');
        $repository = app(RolesInterface::class);
        $modulePermissions = DB::table('model_permissions')->get();
        $permissions = [];
        foreach ($modulePermissions as $permission) {
            $permissions[] = ['name' => $permission->name, 'model_id' => $permission->model_id, 'permission_id' => $permission->permission_id];
        }
        foreach ($roles as $role) {
            $role['permissions'] = $permissions;
            $repository->save($role, $role['id']);
        }
    }
}
