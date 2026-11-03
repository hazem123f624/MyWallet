<?php

namespace Modules\Admins\Database\Seeders;

use Illuminate\Database\Seeder;
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
        foreach ($admins as $value) {
            $value['roles'] = [['role_id' => 1], ['role_id' => 2]];
            $repository->save($value);
        }
    }
}
