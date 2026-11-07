<?php

namespace Modules\Admins\Http\Controllers;

use App\Classes\Authentication\AuthController;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Admins\Http\Requests\ChangePasswordRequest;
use Modules\Admins\Http\Requests\CreateAdminsRequest;
use Modules\Admins\Http\Requests\UpdateAdminsRequest;
use Modules\Admins\Http\Requests\UpdateProfileRequest;
use Modules\Admins\Interfaces\AdminsInterface;
use Modules\Admins\Models\Admins;
use Modules\Admins\Models\Roles;
use Modules\Admins\Resources\AdminResource;

class AdminsController extends AuthController
{
    use HttpResponsesTrait;

    private AdminsInterface $repository;

    public function __construct(AdminsInterface $repository)
    {
        parent::__construct(authFactoryType: 'admins');
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requestData = request()->all();
        $role = $requestData['role'] ?? null;
        $sortBy = $requestData['sort_by'] ?? null;
        $sortDir = $requestData['sort_dir'] ?? null;
        unset($requestData['role'], $requestData['sort_by'], $requestData['sort_dir']);

        $request = $this->handelRequests(requests: $requestData);
        $request['role'] = $role;

        if ($sortBy !== null) {
            $request['sort_by'] = $sortBy;
        }

        if ($sortDir !== null) {
            $request['sort_dir'] = $sortDir;
        }

        $data = $this->repository->data(request: $request);
        $data['data'] = AdminResource::collection($data['data']);

        return $this->success('Data retrieved successfully.', data: $data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateAdminsRequest $request)
    {
        $data = $this->repository->save($request->validated());
        $data = new AdminResource($data);

        return $this->success('Data inserted successfully.', data: $data);
    }

    /**
     * Show the specified resource.
     */
    public function show(Admins $admin)
    {
        $result = $this->repository->data([], $admin->id);
        $data = new AdminResource($result);

        return $this->success('Data retrieved successfully.', data: $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAdminsRequest $request, Admins $admin)
    {
        $data = $this->repository->save($request->validated(), $admin->id);
        $data = new AdminResource($data);

        return $this->success('Data updated successfully.', data: $data);
    }

    public function switchActive(Request $request, Admins $admin)
    {
        $data = $this->repository->switchActive($admin);
        $data = new AdminResource($data);

        return $this->success('Data updated successfully.', data: $data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admins $admin)
    {
        $return = $this->repository->delete($admin);

        return $this->success('Data deleted successfully.', data: []);
    }

    public function trash()
    {
        $allRequested = request()->all();
        $allRequested['only_trashed'] = true;
        $request = $this->handelRequests(requests: $allRequested);
        $result = $this->repository->data(request: $request);
        $ids = collect($result['data'])->pluck('id')->filter();
        $admins = Admins::onlyTrashed()->whereIn('id', $ids)->get();
        $result['data'] = AdminResource::collection($admins);

        return $this->success('Data retrieved successfully.', data: $result);
    }

    public function restore(Admins $admin)
    {
        $data = $this->repository->restore($admin);
        $data = new AdminResource($data);

        return $this->success('Data updated successfully.', data: $data);
    }

    public function remove(Admins $admin)
    {
        $this->repository->forceDelete($admin);

        return $this->success('Data deleted successfully.', data: []);
    }

    public function removeRole(Request $request, Admins $admin, Roles $role)
    {
        $result = $this->repository->removeRole($admin, $role->id);

        if (! $result) {
            return $this->failure(__('admins::messages.admin_role_not_found'), 404);
        }

        return $this->success(__('admins::messages.role_removed_from_admin'), data: []);
    }

    public function resendOtp()
    {
        $identifier = request($this->loginType);
        if (! $identifier) {
            return $this->failure('Email is required.', 422);
        }

        $user = ($this->model)::where($this->loginType, $identifier)->first();
        if (! $user) {
            return $this->failure('Admin not found.', 404);
        }

        // For admins, we use forget_password type for OTP flows
        $code = app(OtpService::class)->generate($user, 'forget_password');

        return $this->success('OTP resent successfully.', data: [
            'otp' => config('app.env') !== 'production' ? $code : null,
        ]);
    }

    /**
     * Resend the forgot-password OTP. Works exactly like the forget-password
     * endpoint: same validation, regenerates the OTP through the auth factory
     * and returns a fresh change-password token.
     */
    public function resendForgotPasswordOtp()
    {
        return parent::forgetPassword();
    }

    public function getDashboardCounts(): JsonResponse
    {
        try {
            $counts = $this->repository->getTablesCounts();

            return response()->json([
                'status' => 'success',
                'data' => $counts,
            ], 200);
        } catch (\Exception $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    public function profile()
    {
        /** @var Admins $admin */
        $admin = auth('sanctum')->user();

        return $this->success(__('admins::messages.profile_retrieved'), data: new AdminResource($admin));
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        /** @var Admins $admin */
        $admin = auth('sanctum')->user();

        $validated = $request->validated();

        $updatedAdmin = $this->repository->save($validated, $admin->id);

        return $this->success(__('admins::messages.profile_updated'), data: new AdminResource($updatedAdmin));
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        /** @var Admins $admin */
        $admin = auth('sanctum')->user();

        if (! Hash::check($request->old_password, $admin->password)) {
            return $this->failure(__('admins::messages.password_incorrect'), 422);
        }

        $admin->update(['password' => $request->new_password]);

        return $this->success(__('admins::messages.password_changed'));
    }
}
