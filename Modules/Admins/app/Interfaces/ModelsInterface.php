<?php

namespace Modules\Admins\Interfaces;

interface ModelsInterface
{
    public function data($request, $id = '*');

    public function save($request, $id = '');

    public function delete($id);
}
