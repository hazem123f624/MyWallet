<?php

namespace App\Services\ForRepository;

use App\Http\Traits\DatabaseStatementsInitTrait;
use App\Services\MediaSystem\MediaService;
use Illuminate\Http\Request;

class RepositoryService
{
    use DatabaseStatementsInitTrait;

    private object $repository;

    private MediaService $mediaService;

    private TranslateRepositoryService $translateService;

    private RelatedModuleRepositoryService $relatedService;

    public function __construct(object $repository)
    {
        $this->repository   = $repository;
        $this->mediaService = app(MediaService::class);
        $this->translateService = new TranslateRepositoryService;
        $this->relatedService   = new RelatedModuleRepositoryService;
    }

    public function handle($request, $id)
    {
        $originalRequest = $request;

        if ($id != '') {
            unset($request['_method']);
        }
        unset($request['_token']);
        foreach ($this->repository?->multiRecord ?? [] as $multiRecord) {
            $request[$multiRecord] = $this->multiRecord($multiRecord, $request);
        }
        foreach ($this->repository?->images ?? [] as $imageName) {
            $request[$imageName] = $this->images($imageName, $request, $id);
        }
        foreach ($this->repository?->files ?? [] as $fileName) {
            $request[$fileName] = $this->files($fileName, $request, $id);
        }

        $requestAfterRemoveTranslatedAttributes = $this->getDataOfRequestAfterRemoveTranslatedAttributes(model: $this->repository->model, request: $request);

        if ($id != '') {
            $requestAfterRemoveTranslatedAttributes = $this->preserveUnsentMediaFields($originalRequest, $requestAfterRemoveTranslatedAttributes);
        }

        return [$request, $requestAfterRemoveTranslatedAttributes];
    }

    private function preserveUnsentMediaFields($originalRequest, array $payload): array
    {
        if ($originalRequest instanceof Request) {
            $originalRequest = $originalRequest->all();
        }

        if (! is_array($originalRequest)) {
            return $payload;
        }

        foreach (array_merge($this->repository?->images ?? [], $this->repository?->files ?? []) as $key) {
            if (! array_key_exists($key, $originalRequest) && array_key_exists($key, $payload)) {
                unset($payload[$key]);
            }
        }

        return $payload;
    }

    public function getDataOfRequestAfterRemoveTranslatedAttributes($model, $request): array
    {
        $translatedAttributes = $this->repository->model->translatedAttributes ?? [];
        $requestAfterRemoveTranslatedAttributes = $request;
        foreach ($translatedAttributes as $translatedAttribute) {
            unset($requestAfterRemoveTranslatedAttributes[$translatedAttribute]);
        }
        $relatedModules = $this->repository->model->relatedModules ?? [];
        foreach ($relatedModules as $key => $relatedModule) {
            unset($requestAfterRemoveTranslatedAttributes[$key]);
        }

        return $requestAfterRemoveTranslatedAttributes;
    }

    public function handelAfterCreated($request, $data, $id): void
    {
        $this->translateService->InitModelTranslate(model: $this->repository->model, request: $request, id: $id);
        $this->relatedService->setInTableRelated($this->repository->model, request: $request, data: $data);
    }

    public function handelForGetData($request, $id = '*', $fieldName = '', $collectionType = 'Model')
    {
        // :string|array|object
        $data = $this->repository->model;
        $data = $this->StatementDB($data, $request);
        $dtoClass = $this->repository->model->dto;
        if ($id == '*') {
            $data = $data->paginate($request['row_in_page'] ?? 15);
            $pagination = $this->handelPagination($data);

            if ($data->count() > 0) {
                $data = $dtoClass::collection($data, $collectionType);
            } else {
                $data = [];
            }

            $return = [];
            $return['data'] = $data;
            $return['pagination'] = $pagination;

            return $return;
        } else {
            $data = $data->where($this->repository->model->table.'.id',$id)->first();
            $data = $dtoClass::single($data, $collectionType);
            if ($fieldName != '') {
                $data = $data->$fieldName;
            }
            return $data;
        }
    }



    public function handelBeforeDeleted($id): void
    {
        $files = array_merge($this->repository->images ?? [], $this->repository->files ?? []);
        foreach ($files as $file) {
            $filePath = $this->getOldData($id, $file);

            if ($filePath) {
                if (is_array($filePath)) {
                    foreach ($filePath as $path) {
                        if (! empty($path) && is_string($path)) {
                            $this->mediaService->delete($path);
                        }
                    }
                } elseif (is_string($filePath)) {
                    $this->mediaService->delete($filePath);
                }
            }
        }
    }

    private function images($imageName, $request, $id = ''): string
    {
        $imageFile = request()->file($imageName);
        if (! $imageFile) {
            return '';
        }

        $mediaFile = $this->mediaService->upload(
            $imageFile,
            relatable: $this->repository->model,
            folderName: $imageName
        );

        $image = $mediaFile->path;

        if ($id != '') {
            $oldImage = $this->getOldData($id, $imageName);
            $image = $this->handelRequestAndUploadFile($request, $image, $imageName, $oldImage);
        }

        return $image;
    }

    private function files($fileName, $request, $id = ''): string
    {
        $uploadedFile = request()->file($fileName);
        if (! $uploadedFile) {
            return '';
        }

        $mediaFile = $this->mediaService->upload(
            $uploadedFile,
            relatable: $this->repository->model,
            folderName: $fileName
        );

        $file = $mediaFile->path;

        if ($id != '') {
            $oldFile = $this->getOldData($id, $fileName);
            $file = $this->handelRequestAndUploadFile($request, $file, $fileName, $oldFile);
        }

        return $file;
    }

    private function multiRecord($multiRecordName, $request): string
    {
        $array = json_encode([]);
        if (array_key_exists($multiRecordName, $request)) {
            $array = json_encode($request[$multiRecordName]);
            $firstRow = current($request[$multiRecordName]);
            if (is_array($firstRow)) {
                $key = array_key_first($request[$multiRecordName]);
                if ($request[$multiRecordName][$key][0] != null) {
                    $array = json_encode($request[$multiRecordName]);
                }
            }
        }

        return $array;
    }

    public function getOldData($id, $fieldName): string|array|null
    {
        return $this->handelForGetData(request: [], id: $id, fieldName: $fieldName);
    }

    private function handelRequestAndUploadFile($request, $file, $fileName, $oldFiles = '')
    {
        if (array_key_exists($fileName.'_removed', $request)) {
            $oldFiles = json_decode($oldFiles, true);
            $removedFiles = $request[$fileName.'_removed'];
            if ($removedFiles) {
                $removedFiles = explode(',', $removedFiles);
                foreach ($removedFiles as $file) {
                    if (in_array($file, $oldFiles)) {
                        $this->mediaService->delete($file);
                        $key = array_search($file, $oldFiles);
                        unset($oldFiles[$key]);
                    }
                }
            }
            $newImage = json_decode($request[$fileName], true);
            $allFiles = array_merge($oldFiles, $newImage);
            $file = json_encode($allFiles);
        } else {
            if ($oldFiles) {
                $this->mediaService->delete($oldFiles);
            }
        }

        return $file;
    }

    private function handelPagination($data): array
    {
        return [
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'total' => $data->total(),
            'last_page' => $data->lastPage(),
        ];
    }
}
