<?php

namespace Modules\Admins\Repositories;

use App\Services\ForRepository\RepositoryService;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Interfaces\ModelsInterface;
use Modules\Admins\Models\Models;

class ModelsRepository implements ModelsInterface
{
    public object $model;

    public RepositoryService $serviceHelper;

    public array $files = [];

    public array $images = [];

    public array $multiRecord = [];

    public array $relatedTables = [];

    public function __construct(Models $model)
    {
        $this->model = $model;
        $this->serviceHelper = new RepositoryService($this);
    }

    public function data($request, $id = '*', $fieldName = '', $collectionType = 'model')
    {
        $request['with'] = ['translate', 'permissions.translate'];

        if (! empty($request['search'])) {
            $search = $request['search'];
            $request['whereHasClosure']['translate'] = function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%");
            };
            unset($request['search']);
        }

        return $this->serviceHelper->handelForGetData(request: $request, id: $id, fieldName: $fieldName, collectionType: $collectionType);
    }

    public function save($request, $id = '')
    {
        try {
            DB::beginTransaction();
            if ($id != '' && $id != $request['id']) {
                throw new \Exception('your id is not match');
            }

            [$request , $requestAfterRemoveTranslatedAttributes] = $this->serviceHelper->handle(request: $request, id: $id);
            $data = $this->model->updateOrCreate(['id' => $id], $requestAfterRemoveTranslatedAttributes);
            $this->serviceHelper->handelAfterCreated(request: $request, data: $data, id: $data->id);
            DB::commit();

            return $data;
        } catch (\Exception $exception) {
            DB::rollBack();
            throw new \Exception($exception->getMessage());
        }
    }

    public function delete($id): void
    {
        try {
            $data = $this->model->findOrFail($id);
            $this->serviceHelper->handelBeforeDeleted($id);
            $data->delete();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }
}
