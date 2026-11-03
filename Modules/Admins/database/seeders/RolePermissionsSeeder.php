<?php

namespace Modules\Admins\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Interfaces\RolesInterface;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $defaultRoles = config('admins.base_roles.default_role');
        $superAdminRole = config('admins.base_roles.super_role');

        $modulePermissions = DB::table('model_permissions')->get();
        $insertedData = [];
        $repository = app(RolesInterface::class);
        foreach ($modulePermissions as $permission) {
            $repository->save($defaultRoles, $defaultRoles['id']);

        }

        // DB::table('model_permissions')->upsert($modulePermissions,['name'],['model_id','permission_id','name','created_at']);
    }
}
