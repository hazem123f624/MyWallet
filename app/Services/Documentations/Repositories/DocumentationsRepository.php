<?php

namespace App\Services\Documentations\Repositories;

use App\Services\ForRepository\RepositoryService;
use Illuminate\Support\Facades\DB;
use App\Services\Documentations\Interfaces\DocumentationsInterface;
use App\Models\Documentations;

class DocumentationsRepository implements DocumentationsInterface
{
    public object $model;

    public RepositoryService $serviceHelper;

    public array $files;

    public array $images;

    public array $multiRecord;

    public array $relatedTables;

    public function __construct(Documentations $model)
    {
        $this->model = $model;
        $this->setImages();
        $this->setFiles();
        $this->setMultiRecord();
        $this->relatedTables();
        $this->serviceHelper = new RepositoryService($this);
    }

    public function setImages(): void
    {
        $this->images = [];
    }

    public function setFiles(): void
    {
        $this->files = [];
    }

    public function setMultiRecord(): void
    {
        $this->multiRecord = [];
    }

    public function relatedTables(): void
    {
        $this->relatedTables = [];
    }

    public function data($request, $id = '*', $fieldName = '', $collectionType = 'Model')
    {
        $request['orderBy'] = ['sorting'=>'asc'];
        $request['row_in_page'] = 100000000;
        return $this->serviceHelper->handelForGetData(request: $request, id: $id, fieldName: $fieldName, collectionType: $collectionType);
    }

    public function save($request, $id = null)
    {
        try {
            DB::beginTransaction();
            if ($id && $id != $request['id']) {
                throw new \Exception('your id is not match');
            }

            [$request , $requestAfterRemoveTranslatedAttributes] = $this->serviceHelper->handle(request: $request, id: $id);
            $data = $this->model->updateOrCreate(['category' => $requestAfterRemoveTranslatedAttributes['category']], $requestAfterRemoveTranslatedAttributes);
            $this->serviceHelper->handelAfterCreated(request: $request, data: $data, id: $data->id);
            DB::commit();

            return $data;
        } catch (\Exception $exception) {
            dd($exception->getMessage().'//////////'.$exception->getLine().'/////////'.$exception->getFile());
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
