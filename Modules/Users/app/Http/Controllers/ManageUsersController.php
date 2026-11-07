<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Users\Interfaces\UsersInterface;
use Modules\Users\Models\User;
use Modules\Users\Resources\UserResource;

class ManageUsersController extends Controller
{
    use HttpResponsesTrait;

    private UsersInterface $repository;

    public function __construct(UsersInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of users for the Super Admin.
     */
    public function index()
    {
        $request = $this->userListRequest();
        $data = $this->repository->data(request: $request);
        $data['data'] = UserResource::collection($data['data']);

        return $this->success('Users retrieved successfully.', data: $data);
    }

    public function show(User $user)
    {
        return $this->success('User details retrieved successfully.', data: new UserResource($user));
    }

    public function statistics()
    {
        return $this->success('User statistics retrieved successfully.', data: $this->repository->statistics());
    }

    public function switchActive(User $user)
    {
        $this->authorize('change-user-status');

        $data = $this->repository->switchActive($user);

        return $this->success('User status updated successfully.', data: new UserResource($data));
    }

    /**
     * Remove the specified user (Soft Delete).
     */
    public function destroy(User $user)
    {
        $this->repository->delete($user);

        return $this->success('User deleted successfully.', data: []);
    }

    /**
     * Recycle Bin for Users.
     */
    public function trash()
    {
        $allRequested = request()->all();
        $allRequested['only_trashed'] = true;
        $request = $this->userListRequest($allRequested);
        $data = $this->repository->data(request: $request);
        $data['data'] = UserResource::collection($data['data']);

        return $this->success('Trashed users retrieved successfully.', data: $data);
    }

    public function restore(User $user)
    {
        $data = $this->repository->restore($user);

        return $this->success('User restored successfully.', data: new UserResource($data));
    }

    public function remove(User $user)
    {
        $this->repository->forceDelete($user);

        return $this->success('User force deleted permanently.', data: []);
    }

    private function userListRequest(?array $data = null): array
    {
        $data ??= request()->all();
        $sortBy = $data['sort_by'] ?? null;
        $sortDir = $data['sort_dir'] ?? null;

        unset($data['sort_by'], $data['sort_dir']);

        $request = $this->handelRequests(requests: $data);

        if ($sortBy !== null) {
            $request['sort_by'] = $sortBy;
        }

        if ($sortDir !== null) {
            $request['sort_dir'] = $sortDir;
        }

        return $request;
    }
}
