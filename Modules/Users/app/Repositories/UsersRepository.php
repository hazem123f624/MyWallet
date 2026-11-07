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
        if (! empty($request['search'])) {
            $search = trim((string) $request['search']);
            $values = [$search];
            $digits = preg_replace('/[^0-9]/', '', $search);

            if ($digits !== '' && strlen($digits) >= 4) {
                $values[] = $digits;

                if (str_starts_with($digits, '0')) {
                    $values[] = substr($digits, 1);
                }
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

    public function statistics(): array
    {
        return [
            'total' => $this->model->newQuery()->count(),
            'active' => $this->model->newQuery()->where('is_active', true)->count(),
            'suspended' => $this->model->newQuery()->where('is_active', false)->count(),
            'trashed' => $this->model->onlyTrashed()->count(),
        ];
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
            } elseif (! empty($request['avatar']) && is_string($request['avatar'])) {
                if ($existing?->avatar && $existing->avatar !== $request['avatar']) {
                    Storage::disk('public')->delete($existing->avatar);
                }
                $requestAfterRemoveTranslatedAttributes['avatar'] = $request['avatar'];
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
            return DB::transaction(function () use ($data) {
                $data->is_active = ! $data->is_active;
                $data->save();

                if (! $data->is_active) {
                    $data->tokens()->delete();
                }

                return $data;
            });
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
