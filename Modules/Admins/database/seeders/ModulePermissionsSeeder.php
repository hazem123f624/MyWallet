<?php

namespace Modules\Admins\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Enums\ActiveStatus;
use Modules\Admins\Models\Models;
use Modules\Admins\Models\Permissions;

class ModulePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = config('admins.permissions');
        $models = config('admins.modules');
        try {
            DB::beginTransaction();
            foreach ($permissions as $permission) {
                $permissionDB = Permissions::updateOrCreate(
                    ['id' => $permission['id']],
                    ['id' => $permission['id'], 'name' => $permission['name'], 'is_active' => ActiveStatus::Active]
                );
                $translatedValues = [];
                foreach (config('app.languages') as $lang => $language) {
                    $translatedValues[] = [
                        'permission_id' => $permissionDB->id,
                        'title' => $permission['title'][$lang],
                        'lang' => $lang,
                        'created_at' => now(),
                    ];
                }
                DB::table('permissions_translate')->upsert($translatedValues, ['permission_id', 'lang'], ['title', 'created_at']);
            }
            foreach ($models as $modelKey => $model) {
                $modelDB = Models::updateOrCreate(
                    ['name' => $modelKey],
                    ['name' => $modelKey, 'is_active' => ActiveStatus::Active]
                );
                $translatedValues = [];
                foreach (config('app.languages') as $lang => $language) {
                    $translatedValues[] = [
                        'model_id' => $modelDB->id,
                        'title' => $model['title'][$lang],
                        'lang' => $lang,
                        'created_at' => now(),
                    ];
                }
                DB::table('models_translate')->upsert($translatedValues, ['model_id', 'lang'], ['title', 'created_at']);
                $modulePermissions = [];
                foreach ($model['permissions'] as $permissionId => $permissionName) {
                    $modulePermissions[] = [
                        'model_id' => $modelDB->id,
                        'permission_id' => $permissionId,
                        'name' => $modelDB->name.'-'.$permissionName,
                        'created_at' => now(),
                    ];
                }
                DB::table('model_permissions')->upsert($modulePermissions, ['name'], ['model_id', 'permission_id', 'name', 'created_at']);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
        }
    }
}
