<?php

namespace App\Http\Traits;

use App\Services\ForRepository\TranslateRepositoryService;

trait TranslateTrait
{
    public function getFromTableTranslate($model, $id, $fieldName = '', $language = ''): array|string|null
    {
        $TranslateService = new TranslateRepositoryService;
        return $TranslateService->getFromTableTranslate(model: $model, id: $id, fieldName: $fieldName, language: $language);
    }

    public function getTranslateByKeyInResourceFile($key, $object)
    {
        if (! $object) {
            return '';
        }
        if (is_array($key)) {
            $value     = $this->getTranslateByKeysInResourceFile($key, $object);
        }else{
            $value = '';
            $object = $object->where('lang', app()->getLocale())->first();
            if ($object) {
                $value = $object->$key;
            }
        }
        return $value;
    }
    public function getTranslateByKeysInResourceFile($keys , $object): array
    {
        $value = [];
        $languages = array_keys(config('app.languages'));
        foreach ($languages as $language){
            $row = $object->where('lang', $language)->first();
            foreach ($keys as $key) {
                $value[$key][$language] = $row ? $row->$key : '';
            }
        }
        return $value;
    }
}
