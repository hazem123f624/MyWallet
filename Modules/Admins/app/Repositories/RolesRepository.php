<?php

namespace Modules\Admins\Repositories;

use App\Services\ForRepository\RepositoryService;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Interfaces\RolesInterface;
use Modules\Admins\Models\AdminRoles;
use Modules\Admins\Models\Admins;
use Modules\Admins\Models\Roles;

class RolesRepository implements RolesInterface
{
    public object $model;

    public RepositoryService $serviceHelper;

    public array $files = [];

    public array $images = [];

    public array $multiRecord = [];

    public array $relatedTables = [];

    public function __construct(Roles $model)
    {
        $this->model = $model;
        $this->serviceHelper = new RepositoryService($this);
    }

    public function data($request, $id = '*', $fieldName = '', $collectionType = 'model')
    {
        $request['with'] = ['permissions.translate', 'translate'];
        $request['withCount'] = ['admins', 'permissions'];

        if (! empty($request['search'])) {
            $request['whereHasClosure']['translate'] = function ($query) use ($request): void {
                $query->where('name', 'like', "%{$request['search']}%");
            };
        }

        return $this->serviceHelper->handelForGetData(request: $request, id: $id, fieldName: $fieldName, collectionType: $collectionType);
    }

    public function save($request, $id = null)
    {
        try {
            DB::beginTransaction();
            if ($id && $id != $request['id']) {
                abort(404);
            }

            [$request , $requestAfterRemoveTranslatedAttributes] = $this->serviceHelper->handle(request: $request, id: $id);
            $data = $this->model->updateOrCreate(['id' => $id], $requestAfterRemoveTranslatedAttributes);
            $this->serviceHelper->handelAfterCreated(request: $request, data: $data, id: $data->id);
            DB::commit();

            return $data;
        } catch (\Exception $exception) {
            DB::rollBack();
            abort(500);
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
            $this->serviceHelper->handelBeforeDeleted($data->id);
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

    public function admins($id, $request = [])
    {
        try {
            $role = $this->model->find($id);

            if (! $role) {
                return null;
            }

            return $role->adminsList()->paginate($request['row_in_page'] ?? 15);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    public function availableAdmins($id, $search = null)
    {
        try {
            $role = $this->model->find($id);

            if (! $role) {
                return null;
            }

            return Admins::whereDoesntHave('roles', function ($query) use ($id): void {
                $query->where('role_id', $id);
            })
                ->when($search, function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%");
                })
                ->get();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
    public function assignAdmins($role, array $adminIds)
    {
        try {
            foreach ($adminIds as $adminId) {
                AdminRoles::updateOrCreate(
                    ['admin_id' => $adminId, 'role_id' => $role->id],
                    ['admin_id' => $adminId, 'role_id' => $role->id]
                );
            }

            return [
                'role' => $role,
                'adminIds' => $adminIds,
            ];
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
    public function permissions($id)
    {
        try {
            return $this->model->with(['permissions.translate', 'permissions.model.translate'])->find($id);
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
