<?php

namespace App\Services\ForRepository;

class RelatedModuleRepositoryService
{
    private TranslateRepositoryService $translatedService;

    public function __construct()
    {
        $this->setTranslatedService();
    }

    private function setTranslatedService(): void
    {
        $this->translatedService = new TranslateRepositoryService;
    }

    public function getTranslatedService(): TranslateRepositoryService
    {
        return $this->translatedService;
    }

    public function setInTableRelated($model, $request, $data): void
    {
        foreach ($model->relatedModules ?? [] as $relation => $relationData) {
            if (! array_key_exists($relation, $request)) {

                if (array_key_exists('keys' ,$relationData ) && $relationData['keys']) {
                    $this->setInTableRelatedWithoutPivot(data: $data, model: $model, item: $relationData['keys'], relation: $relation,request:$request);
                }
                continue;
            }

            if (! isset($model->foreignKey)) {
                continue;
            }
            if (array_key_exists('translate',$relationData) && $relationData['translate']) {
                $translateConfig = $relationData['translate'];
                $requestAfterRemoveTranslatedAttributes = app(RepositoryService::class)->getDataOfRequestAfterRemoveTranslatedAttributes(model: $translateConfig['model'], request: $request[$relation]);
                $requestAfterRemoveTranslatedAttributes[$translateConfig['key']] = $data->id;
                $relatedData = $translateConfig['model']->updateOrCreate([$translateConfig['key'] => $data->id], $requestAfterRemoveTranslatedAttributes);
                $this->translatedService->InitModelTranslate(model: $translateConfig['model'], request: $request[$relation], id: $relatedData->id);
            } else {
                if (is_array($request[$relation]) && array_key_first($request[$relation]) == 0) {
                    foreach ($request[$relation] as $item) {
                        $this->setInTableRelatedWithPivot(data: $data, model: $model, item: $item, relation: $relation, primaryKeys: $relationData['primary_keys']);
                    }
                } else {
                    $this->setInTableRelatedWithPivot(data: $data, model: $model, item: $request[$relation], relation: $relation, primaryKeys: $relationData['primary_keys']);
                }
            }
        }
    }
    private function setInTableRelatedWithoutPivot($data, $model, $item, $relation,$request): void
    {
        $dataForTable = [];
        $dataForTable[$model->foreignKey] = $data->id;
        foreach ($item as $key){
            if ($key != $model->foreignKey && array_key_exists($key, $request)) {
                $dataForTable[$key] = $request[$key];
            }
        }
        if (count($dataForTable) == count($item)) {
//            dd([$model->foreignKey => $data->id], $dataForTable);
            $data->$relation()->updateOrCreate([$model->foreignKey => $data->id], $dataForTable);
        }

    }

    private function setInTableRelatedWithPivot($data, $model, $item, $relation, $primaryKeys): void
    {
        $whereKeys[$model->foreignKey] = $data->id;
        foreach ($primaryKeys as $primaryKey) {
            if ($model->foreignKey == $primaryKey) {
                continue;
            }
            $whereKeys[$primaryKey] = $item[$primaryKey];
        }
        $data->$relation()->updateOrCreate($whereKeys, $item);
    }
}
