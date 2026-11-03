<?php

namespace App\Services\ForRepository;

use App\Models\GenericTranslate;
use Exception;

class TranslateRepositoryService
{
    public function getFromTableTranslate($model, $id, $fieldName = '', $language = '*'): array|string|null
    {
        [$tableName,$translatedTableName,$relationalFieldName,$attributesTranslate] = $this->handelDataModel($model);
        if (\Schema::hasTable($translatedTableName) && is_array($attributesTranslate) && in_array($fieldName, $attributesTranslate)) {
            $return = [];
            $translateModel = $this->InitTableInModel($translatedTableName);
            if ($fieldName == '') {
                $data = $translateModel->where($relationalFieldName, $id)->get();
                foreach (config('app.languages') as $lang => $langData) {
                    $return[$lang] = $data->where('lang', $lang)->first()->toArray();
                }

                return $return;
            }
            if ($language == '') {
                $lang = app()->getLocale();

                return $translateModel->where($relationalFieldName, $id)->where('lang', $lang)->value($fieldName) ?? null;
            } elseif ($language == '*') {
                $data = $translateModel->where($relationalFieldName, $id)->select(['lang', $fieldName])->get();
                foreach (config('app.languages') as $lang => $langData) {
                    $return[$lang] = $data->where('lang', $lang)->pluck($fieldName, 'lang')->first() ?? null;
                }

                return $return;
            }

            return $translateModel->where($relationalFieldName, $id)->where('lang', $language)->value($fieldName) ?? null;
        }

        return null;
    }

    public function InitModelTranslate($model, $request, $id): void
    {
        try {
            $data = $this->handelDataModel($model);
            $this->initTranslateBody($data, $request, $id);
        } catch (Exception $e) {
            dd($e->getMessage());
        }
    }

    //    function setInTableTranslate($model,$request,$id):void
    //    {
    //        try {
    //            $data = $this->handelDataTable($model);
    //            $this->initTranslateBody($data,$request,$id);
    //        }catch (Exception $e) {
    //            dd($e->getMessage());
    //        }
    //    }
    private function handelDataModel($model): array
    {
        $attributeTranslate = $model->translatedAttributes;
        $tableName = $model->getTable();
        $fieldName = explode('_', $tableName);
        $translatedTableName = $tableName.'_translate';
        $fieldName = (isset($model->foreignKey)) ? $model->foreignKey : str()->singular(implode('_', $fieldName)).'_id';

        return [$tableName, $translatedTableName, $fieldName, $attributeTranslate];
    }

    private function handelDataTable($tableArray): array
    {
        $attributeTranslate = $tableArray['translated'];
        $tableName = $tableArray['table_name'];
        $fieldName = explode('_', $tableName);
        $translatedTableName = $tableName.'_translate';
        $fieldName = (isset($model->foreignKey)) ? $model->foreignKey : str()->singular(implode('_', $fieldName)).'_id';

        return [$tableName, $translatedTableName, $fieldName, $attributeTranslate];
    }

    public function InitTableInModel($translatedTableName): GenericTranslate
    {
        $translateModel = new GenericTranslate;
        return $translateModel->setTable($translatedTableName);
    }

    private function initTranslateBody($data, $request, $id): void
    {
        [$tableName,$translatedTableName,$relationalFieldName,$attributesTranslate] = $data;
        if (\Schema::hasTable($translatedTableName) && is_array($attributesTranslate) && ! empty($attributesTranslate)) {
            $dataShouldBeInsert = [];
            foreach ($attributesTranslate as $attribute) {
                if (isset($request[$attribute]) && is_array($request[$attribute])) {
                    foreach ($request[$attribute] as $lang => $value) {
                        $dataShouldBeInsert[$lang][$relationalFieldName] = $id;
                        $dataShouldBeInsert[$lang][$attribute] = $value;
                        $dataShouldBeInsert[$lang]['lang'] = $lang;
                    }
                }
            }
            foreach ($dataShouldBeInsert as $lang => $values) {
                $translateModel = $this->InitTableInModel($translatedTableName);
                $whereCondition = ['lang' => $lang, $relationalFieldName => $id];
                $translateModel->updateOrCreate($whereCondition, $values);
            }
        }
    }
}
