<?php

namespace Modules\Users\Repositories;

use App\Services\ForRepository\RepositoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Users\Interfaces\UsersInterface;
use Modules\Users\Models\User;

class UsersRepository implements UsersInterface
{
    public object $model;

    public RepositoryService $serviceHelper;

    public array $files = [];

    public array $images = ['avatar'];

    public array $multiRecord = [];

    public array $relatedTables = [];

    public function __construct(User $model)
    {
        $this->model = $model;
        $this->serviceHelper = new RepositoryService($this);
    }

    public function data($request, $id = '*', $fieldName = '', $collectionType = 'model')
    {
        return $this->serviceHelper->handelForGetData(request: $request, id: $id, fieldName: $fieldName, collectionType: $collectionType);
    }

    public function save($request, $id = null)
    {
        try {
            DB::beginTransaction();

            [$request, $requestAfterRemoveTranslatedAttributes] = $this->serviceHelper->handle(request: $request, id: $id);

            $existing = $id ? $this->model->find($id) : null;

            if (! empty($request['avatar']) && $request['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                if ($existing?->avatar) {
                    Storage::disk('public')->delete($existing->avatar);
                }
                $requestAfterRemoveTranslatedAttributes['avatar'] = $request['avatar']->store('users/avatars', 'public');
            } elseif (filter_var($request['delete_avatar'] ?? false)) {
                if ($existing?->avatar) {
                    Storage::disk('public')->delete($existing->avatar);
                }
                $requestAfterRemoveTranslatedAttributes['avatar'] = null;
            }

            $data = $this->model->updateOrCreate(['id' => $id], $requestAfterRemoveTranslatedAttributes);
            $this->serviceHelper->handelAfterCreated(request: $request, data: $data, id: $data->id);
            DB::commit();
            $data->refresh();

            return $data;
        } catch (\Exception $exception) {
            DB::rollBack();
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
}
