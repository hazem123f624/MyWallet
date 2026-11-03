<?php

namespace App\DTOs;

class BaseDto
{
    public static function collection($items, $type = 'Model')
    {

        $method = 'from'.ucfirst($type);
        return $items->map(fn ($record) => static::$method($record));
    }
    public static function single($item, $type = 'Model'){
        $method = 'from'.ucfirst($type);

        return static::$method($item);
    }
}
