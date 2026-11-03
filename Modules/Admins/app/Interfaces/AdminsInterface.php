<?php

namespace Modules\Admins\Interfaces;

interface AdminsInterface
{
    public function data($request, $id = '*');

    public function save($request, $id = '');

    public function switchActive($data);

    public function delete($data);

    public function restore($data);

    public function forceDelete($data);

    public function removeRole($admin, $roleId);

    public function getTablesCounts(): array;
}
