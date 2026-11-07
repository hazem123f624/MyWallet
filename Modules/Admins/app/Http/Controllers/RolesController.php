<?php

namespace Modules\Admins\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Modules\Admins\Http\Requests\AssignAdminsToRoleRequest;
use Modules\Admins\Http\Requests\CreateRolesRequest;
use Modules\Admins\Http\Requests\UpdateRolesRequest;
use Modules\Admins\Interfaces\RolesInterface;
use Modules\Admins\Models\Admins;
use Modules\Admins\Models\Roles;
use Modules\Admins\Resources\AvailableAdminResource;
use Modules\Admins\Resources\RoleAdminResource;
use Modules\Admins\Resources\RoleModulesPermissionsResource;
use Modules\Admins\Resources\RolesResource;

class RolesController extends Controller
{
    use HttpResponsesTrait;

    private RolesInterface $repository;

    public function __construct(RolesInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $request = $this->roleListRequest(request()->all());
        $data = $this->repository->data(request: $request);
        $data['data'] = RolesResource::collection($data['data']);

        return $this->success(__('admins::messages.roles_retrieved'), data: $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRolesRequest $request)
    {
        $data = $this->repository->save($request->validated());

        return $this->success(__('admins::messages.role_created'), data: new RolesResource($data));
    }

    public function show($id)
    {
        $requests = request()->all();
        $request = $this->handelRequests(requests: $requests);
        $data = $this->repository->data($request, $id);
        $data = new RolesResource($data);

        return $this->success(__('admins::messages.role_retrieved'), data: $data);
    }

    public function admins($role)
    {
        $roleId = $role instanceof Roles ? $role->id : $role;
        $request = $this->handelRequests(requests: request()->all());
        $admins = $this->repository->admins($roleId, $request);

        if (! $admins) {
            return $this->failure(__('admins::messages.role_not_found'), 404);
        }

        return $this->success(__('admins::messages.role_admins_retrieved'), data: paginationData($admins, RoleAdminResource::collection($admins->getCollection())));
    }

    public function availableAdmins($role)
    {
        $roleId = $role instanceof Roles ? $role->id : $role;
        $search = request()->query('search');
        $result = $this->repository->availableAdmins($roleId, $search);

        if (! $result) {
            return $this->failure(__('admins::messages.role_not_found'), 404);
        }

        return $this->success(__('admins::messages.available_admins_retrieved'), data: AvailableAdminResource::collection($result));
    }

    public function permissions($role)
    {
        $roleId = $role instanceof Roles ? $role->id : $role;
        $roleData = $this->repository->permissions($roleId);

        if (! $roleData) {
            return $this->failure(__('admins::messages.role_not_found'), 404);
        }

        return $this->success(__('admins::messages.role_permissions_retrieved'), data: new RoleModulesPermissionsResource($roleData));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRolesRequest $request, Roles $role)
    {
        $validated = $request->validated();
        $validated['id'] = $role->id;
        $data = $this->repository->save($validated, $role->id);

        return $this->success(__('admins::messages.role_updated'), data: new RolesResource($data));
    }

    public function switchActive(Request $request, Roles $role)
    {
        $data = $this->repository->switchActive($role);

        return $this->success(__('admins::messages.role_status_updated'), data: new RolesResource($data));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Roles $role)
    {
        $this->repository->delete($role);

        return $this->success(__('admins::messages.role_deleted'), data: []);
    }

    public function trash()
    {
        $allRequested = request()->all();
        $allRequested['only_trashed'] = true;
        $request = $this->roleListRequest($allRequested);
        $result = $this->repository->data(request: $request);
        $ids = collect($result['data'])->pluck('id')->filter();
        $roles = Roles::onlyTrashed()->whereIn('id', $ids)->get();
        $result['data'] = RolesResource::collection($roles);

        return $this->success(__('admins::messages.role_trash_retrieved'), data: $result);
    }

    private function roleListRequest(array $data): array
    {
        $sortBy = $data['sort_by'] ?? null;
        $sortDir = $data['sort_dir'] ?? null;
        unset($data['sort_by'], $data['sort_dir']);

        $request = $this->handelRequests(requests: $data);

        if ($sortBy !== null) {
            $sortField = in_array($sortBy, ['created_at', 'is_active', 'id'], true) ? $sortBy : 'created_at';
            $sortDirection = strtolower((string) $sortDir) === 'asc' ? 'asc' : 'desc';
            $request['orderBy'] = [$sortField => $sortDirection];
        }

        return $request;
    }

    public function restore(Roles $role)
    {
        $data = $this->repository->restore($role);

        return $this->success(__('admins::messages.role_restored'), data: new RolesResource($data));
    }

    public function remove(Roles $role)
    {
        $this->repository->forceDelete($role);

        return $this->success(__('admins::messages.role_permanently_deleted'), data: []);
    }

    public function assignAdmins(AssignAdminsToRoleRequest $request, Roles $role)
    {
        $result         = $this->repository->assignAdmins($role, $request->validated()['admin_ids']);
        $roleData       = $this->repository->data([], $result['role']->id);
        $assignedAdmins = Admins::whereIn('id', $result['adminIds'])->get();

        return $this->success(__('admins::messages.admins_assigned_to_role'), data: [
            'role' => new RolesResource($roleData),
            'admins' => RoleAdminResource::collection($assignedAdmins),
        ]);
    }
}
