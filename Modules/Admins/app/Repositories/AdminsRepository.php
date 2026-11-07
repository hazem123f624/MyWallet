<?php

namespace Modules\Admins\Repositories;

use App\Services\ForRepository\RepositoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Admins\Interfaces\AdminsInterface;
use Modules\Admins\Models\Admins;
use Modules\Users\Models\User;

class AdminsRepository implements AdminsInterface
{
    public object $model;

    public RepositoryService $serviceHelper;

    public array $files = [];

    public array $images = ['avatar'];

    public array $multiRecord = [];

    public array $relatedTables = [];

    public function __construct(Admins $model)
    {
        $this->model = $model;
        $this->serviceHelper = new RepositoryService($this);
    }

    public function data($request, $id = '*', $fieldName = '', $collectionType = 'model')
    {
        $request['with'] = ['roles.role' => function ($query): void {
            $query->withCount(['admins', 'permissions'])->with(['permissions.translate', 'translate']);
        },
        ];

        if (! empty($request['role'])) {
            $request['whereHas']['roles'] = ['role_id' => $request['role']];
        }

        if (! empty($request['search'])) {
            $search = trim((string) $request['search']);
            $values = [$search];
            $digits = preg_replace('/[^0-9]/', '', $search);

            if ($digits !== '' && strlen($digits) >= 4) {
                $values[] = $digits;
            }

            $request['whereAnyLike'][] = [
                'columns' => ['name', 'email', 'phone'],
                'values' => array_values(array_unique($values)),
            ];
            unset($request['search']);
        }

        $sortable = ['name', 'email', 'phone', 'is_active', 'created_at'];
        $sortBy = in_array($request['sort_by'] ?? null, $sortable, true) ? $request['sort_by'] : 'created_at';
        $sortDir = strtolower((string) ($request['sort_dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $request['orderBy'] = [$sortBy => $sortDir];
        unset($request['sort_by'], $request['sort_dir']);

        return $this->serviceHelper->handelForGetData(request: $request, id: $id, fieldName: $fieldName, collectionType: $collectionType);
    }

    public function save($request, $id = null)
    {
        try {
            DB::beginTransaction();

            $deleteAvatar = ! empty($request['delete_avatar']);
            unset($request['delete_avatar']);

            if ($deleteAvatar && $id) {
                $admin = $this->model->find($id);
                if ($admin && ! empty($admin->avatar)) {
                    $path = str_replace(Storage::url(''), '', $admin->avatar);
                    if (Storage::exists($path)) {
                        Storage::delete($path);
                    }
                }
                $request['avatar'] = null;
            }

            [$request , $requestAfterRemoveTranslatedAttributes] = $this->serviceHelper->handle(request: $request, id: $id);
            $data = $this->model->updateOrCreate(['id' => $id], $requestAfterRemoveTranslatedAttributes);
            $this->serviceHelper->handelAfterCreated(request: $request, data: $data, id: $data->id);
            DB::commit();

            return $data;
        } catch (\Exception $exception) {
            DB::rollBack();
            logException($exception);
            throw new \Exception($exception->getMessage());
        }
    }

    public function switchActive($data)
    {
        try {
            $data->is_active = ! $data->is_active;
            $data->save();

            return $data;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function delete($data): void
    {
        try {
            //            $this->serviceHelper->handelBeforeDeleted($data->id);
            $data->delete();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function forceDelete($data): void
    {
        try {
            $data->forceDelete();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function restore($data)
    {
        try {
            $data->restore();

            return $data;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function removeRole($admin, $roleId)
    {
        try {
            $adminRole = $admin->roles()->where('role_id', $roleId)->first();

            if (! $adminRole) {
                return null;
            }

            $adminRole->delete();

            return $adminRole;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function getTablesCounts(): array
    {
        try {
            return [
                'users_count' => User::count(),
                'admins_count' => Admins::count(),
                'total_wallets' => 'static value',
                'total_transactions' => 'static value',
                'total_balance' => 'static value',
            ];
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
