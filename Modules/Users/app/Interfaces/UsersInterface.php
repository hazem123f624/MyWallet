<?php

namespace Modules\Users\Interfaces;

interface UsersInterface
{
    public function data($request, $id = '*', $fieldName = '', $collectionType = 'model');

    public function save($request, $id = null);

    public function switchActive($data);

    public function delete($data);

    public function forceDelete($data);

    public function restore($data);
}
