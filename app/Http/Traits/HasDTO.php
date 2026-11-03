<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasDTO
{
    public static function bootHasDTO()
    {
        // replace single model
        static::retrieved(function ($model) {
            if (property_exists($model, 'dto')) {
                return $model->dto::fromModel($model);
            }
        });

        // replace multiple models (collections)
        static::addGlobalScope('dto', function (Builder $builder) {
            $builder->macro('get', function ($builder, $columns = ['*']) {
                $collection = $builder->toBase()->get($columns);

                // check model dto
                $modelClass = get_class($builder->getModel());
                $dtoClass = (new $modelClass)->dto ?? null;

                if (! $dtoClass) {
                    return $collection;
                }

                return $collection->map(fn ($row) => $dtoClass::fromModel((new $modelClass)->newFromBuilder((array) $row)));
            });
        });
    }
}
