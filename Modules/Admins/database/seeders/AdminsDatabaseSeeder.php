<?php

namespace Modules\Admins\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Interfaces\AdminsInterface;

class AdminsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed module permissions first (from former Models module)
        $this->call([
            ModulePermissionsSeeder::class,
            RolesDatabaseSeeder::class,
            RolePermissionsSeeder::class,
        ]);

        // Seed admins
        $admins = config('admins.base_admins');
        $repository = app(AdminsInterface::class);
        $roleIds = DB::table('roles')->whereNull('deleted_at')->pluck('id')->all();

        if ($roleIds === []) {
            $roleIds = array_map(static fn (array $role): int => (int) $role['id'], config('admins.base_roles', []));
        }

        foreach ($admins as $value) {
            $value['roles'] = array_map(static fn (int $roleId): array => ['role_id' => $roleId], $roleIds);
            $repository->save($value, $value['id'] ?? null);
        }
    }
}
