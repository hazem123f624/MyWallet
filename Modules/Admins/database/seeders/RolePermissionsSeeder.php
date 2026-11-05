<?php

namespace Modules\Admins\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roleIds = DB::table('roles')->whereNull('deleted_at')->pluck('id');
        $modulePermissions = DB::table('model_permissions')->get();

        if ($roleIds->isEmpty() || $modulePermissions->isEmpty()) {
            return;
        }

        $timestamp = now();
        $rows = [];

        foreach ($roleIds as $roleId) {
            foreach ($modulePermissions as $permission) {
                $rows[] = [
                    'role_id' => $roleId,
                    'model_id' => $permission->model_id,
                    'permission_id' => $permission->permission_id,
                    'name' => $permission->name,
                    'deleted_at' => null,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        DB::table('role_permissions')->upsert(
            $rows,
            ['role_id', 'model_id', 'permission_id'],
            ['name', 'deleted_at', 'updated_at']
        );
    }
}
