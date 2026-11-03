<?php

namespace Modules\Admins\Interfaces;

interface RolesInterface
{
    public function data($request, $id = '*');

    public function save($request, $id = '');

    public function switchActive($data);

    public function delete($data);

    public function restore($data);

    public function forceDelete($data);

    public function admins($id, $request = []);

    public function availableAdmins($id, $search = null);

    public function permissions($id);

    public function assignAdmins($role, array $adminIds);
}
