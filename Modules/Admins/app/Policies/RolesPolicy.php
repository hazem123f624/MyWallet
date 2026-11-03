<?php

namespace Modules\Admins\Policies;

use Modules\Admins\Models\Admins;

class RolesPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Admins $admins): bool
    {
        return $admins->hasPermission('roles_fetch');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Admins $admins): bool
    {
        return $admins->hasPermission('roles_read');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Admins $admins): bool
    {
        return $admins->hasPermission('roles_create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Admins $admins): bool
    {
        return $admins->hasPermission('roles_update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Admins $admins): bool
    {
        return $admins->hasPermission('roles_delete');
    }
}
