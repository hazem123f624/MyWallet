<?php

namespace Modules\Admins\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Admins\Models\Admins;

class AdminsPolicy
{
    use HandlesAuthorization;

    private string $model;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        $this->model = 'admins';
    }

    public function viewAny(Admins $admin)
    {
        return $admin->hasPermission($this->model.'-fetch');
    }

    public function view(Admins $admin)
    {
        return $admin->hasPermission($this->model.'-read');
    }

    public function create(Admins $admin)
    {
        return $admin->hasPermission($this->model.'-create');
    }

    public function update(Admins $admin)
    {
        return $admin->hasPermission($this->model.'-update');
    }

    public function delete(Admins $admin)
    {
        return $admin->hasPermission($this->model.'-delete');
    }
}
