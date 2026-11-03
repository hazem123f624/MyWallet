<?php

namespace Modules\Admins\Tests\Documentation;

use App\Classes\TestCases\InitTestCaseBuilder;
use App\Classes\TestCases\TestCaseBuilder;
use App\Enums\ActiveStatus;
use App\Http\Controllers\ApiDocumentationController;
use Illuminate\Support\Facades\Http;
use Modules\Admins\Http\Requests\AssignAdminsToRoleRequest;
use Modules\Admins\Http\Requests\ChangePasswordRequest;
use Modules\Admins\Http\Requests\CreateAdminsRequest;
use Modules\Admins\Http\Requests\CreateRolesRequest;
use Modules\Admins\Http\Requests\UpdateAdminsRequest;
use Modules\Admins\Http\Requests\UpdateProfileRequest;
use Modules\Admins\Http\Requests\UpdateRolesRequest;
use Modules\Admins\Models\Admins;
use Modules\Admins\Models\Roles;

class DocumentationCases
{
    /**
     * Define the documentation test cases for this module.
     * Use the TestCaseBuilder for a fluent definition.
     */
    public static function cases(): array
    {
        $fakeLogin = fakeLogin();
        $testCasesObject = new InitTestCaseBuilder;

        // Document Admins, Roles and Authentication endpoints
        $adminsCases = self::adminsModule('admins', $testCasesObject, $fakeLogin);
        $rolesCases = self::rolesModule('roles', $testCasesObject, $fakeLogin);
        $authCases = self::authenticationModule('admins', $testCasesObject, $fakeLogin);

        // Extract index, show, and recycle-bin cases
        $indexCases = array_merge(
            $adminsCases['admins-create-index'] ?? [],
            $rolesCases['roles-create-index'] ?? []
        );

        $showCases = array_merge(
            $adminsCases['admins-show-update-delete'] ?? [],
            $rolesCases['roles-show-update-delete'] ?? []
        );

        $recycleBinCases = array_merge(
            $adminsCases['admins-recycle-bin'] ?? [],
            $rolesCases['roles-recycle-bin'] ?? []
        );

        return [
            'index' => $indexCases,
            'show' => $showCases,
            'recycle-bin' => $recycleBinCases,
            'authentication' => $authCases['authentication'] ?? [],
        ];
    }

    private static function authenticationModule($model, $testCasesObject, $fakeLogin)
    {
        $descriptions = app(ApiDocumentationController::class)->returnDescription($model);
        // The admin auth endpoints live directly under /api/v1 (no extra prefix).

        // Guarantee a real, known admin exists for the login/auth flows below —
        // relying on a seeded admin assumes a seeder ran in this environment,
        // which isn't reliable for a documentation run.
        $baseEmail = 'doc-admin@base.com';
        $basePassword = 'P@ssw0rd123';

        // Reset the fixture admin to a known state so repeated documentation
        // runs (e.g. migrate:fresh --seed) stay idempotent: the reset-password
        // case below changes the stored password and nulls the token, which
        // would otherwise break the login / forget-password cases on re-runs.
        Admins::withTrashed()->where('email', $baseEmail)->first()?->otps()?->delete();
        $fixtureAdmin = Admins::withTrashed()->where('email', $baseEmail)->first();
        if ($fixtureAdmin && $fixtureAdmin->trashed()) {
            $fixtureAdmin->restore();
        }
        Admins::updateOrCreate(
            ['email' => $baseEmail],
            [
                'name' => 'Documentation Admin',
                'phone' => '0169'.rand(1000000, 9999999),
                'password' => $basePassword,
                'job_title' => 'Administrator',
                'contract_type' => 'full_time',
                'is_active' => true,
                'change_password_token' => null,
                'change_password_expired_at' => null,
            ]
        );

        $auth = [
            'admin-login' => $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_login']['name'], route: 'login', params: ['step' => 1, 'email' => $baseEmail, 'password' => $basePassword], auth: false, desc: $descriptions['mobile_login']['description'], shortDesc: $descriptions['mobile_login']['short_description']),
            'forget-password-request' => $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_forget_password']['name'], route: 'forget-password', params: ['email' => $baseEmail], auth: false, desc: $descriptions['mobile_forget_password']['description'], shortDesc: $descriptions['mobile_forget_password']['short_description']),
            'verify-otp' => $testCasesObject->handle(type: 'POST', name: 'Verify Forget Password OTP', route: 'verify-forget-password-otp', params: ['email' => $baseEmail, 'otp' => '1234', 'change_password_token' => config('auth_guards.test.token')], auth: false, desc: 'Verify the OTP received for password reset.', shortDesc: 'Verify reset OTP.'),
            'reset-password' => $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_reset_password']['name'], route: 'reset-password', params: ['email' => $baseEmail, 'password' => 'NewP@ssw0rd123', 'new_password_confirmation' => 'NewP@ssw0rd123', 'change_password_token' => config('auth_guards.test.token')], auth: false, desc: $descriptions['mobile_reset_password']['description'], shortDesc: $descriptions['mobile_reset_password']['short_description']),
            'resend-otp' => $testCasesObject->handle(type: 'POST', name: $descriptions['mobile_resend_otp']['name'], route: 'resend-otp', params: ['email' => $baseEmail], auth: false, desc: $descriptions['mobile_resend_otp']['description'], shortDesc: $descriptions['mobile_resend_otp']['short_description']),
            'resend-forgot-password-otp' => $testCasesObject->handle(type: 'POST', name: 'Resend Forgot Password OTP', route: 'resend-forgot-password-otp', params: ['email' => $baseEmail], auth: false, desc: 'Request a new reset OTP if the previous one expired or was not received. Works exactly like the forget-password endpoint (same validation), regenerates the OTP and returns a fresh change-password token.', shortDesc: 'Resend reset code.'),
            'admin-logout' => $testCasesObject->handle(type: 'POST', name: 'Logout', route: 'logout', params: [], auth: $fakeLogin, desc: 'Log out the currently authenticated admin and invalidate the token.', shortDesc: 'Admin logout.'),
        ];

        return [
            'authentication' => $auth,
        ];
    }

    private static function fakeData($type): array
    {
        $data = [
            'admins' => [
                'create' => [
                    'name' => 'Admin 2',
                    'email' => 'admin2@gmail.com',
                    'phone' => '01112226666',
                    'password' => 'P@ss0rd',
                    'job_title' => 'Administrator',
                    'avatar' => null,
                    'contract_type' => 'full_time',
                    'password_confirmation' => 'P@ss0rd',
                    'roles' => [
                        ['role_id' => 1],
                        ['role_id' => 2],
                    ],
                    'description' => [
                        'ar' => 'وصف المسؤول',
                        'en' => 'Admin description',
                    ],
                ],
                'update' => [
                    'name' => 'Admin 2',
                    'email' => 'admin2@gmail.com',
                    'phone' => '01112226666',
                    'avatar' => null,
                    'password' => 'P@ss0rd',
                    'job_title' => 'Manager',
                    'contract_type' => 'part_time',
                    'roles' => [
                        ['role_id' => 1],
                        ['role_id' => 2],
                    ],
                ],
                'update_profile' => [
                    'name' => 'Admin Self Updated',
                    'email' => 'admin_self@gmail.com',
                    'phone' => '01112226666',
                    'delete_avatar' => false,
                ],
                'change_password' => [
                    'old_password' => 'P@ssw0rd',
                    'new_password' => 'NewP@ss0rd1',
                    'password_confirmation' => 'NewP@ss0rd1',
                ],
                'expected_keys' => [],
            ],
            'roles' => [
                'create' => [
                    'name' => [
                        'ar' => 'أختبار',
                        'en' => 'Test Role',
                    ],
                    'permissions' => [
                        [
                            'permission_id' => 1,
                            'model_id' => 1,
                        ],
                    ],
                    'description' => [
                        'ar' => 'وصف الدور',
                        'en' => 'Role description',
                    ],
                ],
                'update' => [
                    'id' => 3,
                    'name' => [
                        'ar' => 'أختبار',
                        'en' => 'Test Role',
                    ],
                    'permissions' => [
                        [
                            'permission_id' => 1,
                            'model_id' => 1,
                        ],
                    ],
                ],
                'expected_keys' => [],
            ],
        ];

        return $data[$type];
    }

    private static function adminsModule($model, $testCasesObject, $fakeLogin)
    {
        $fakeData = self::fakeData($model);
        $params = $fakeData['create'];

        // Ensure each documentation generation run uses unique admin identity.
        $uniqueSuffix = substr(str()->random(8), 0, 6);
        $params['email'] = sprintf('admin+%s@gmail.com', $uniqueSuffix);
        $params['phone'] = '0111'.rand(1000000, 9999999);
        $params['name'] = 'Admin '.strtoupper($uniqueSuffix);
        $params['job_title'] = 'Administrator';
        $params['contract_type'] = 'full_time';

        $paramForUpdate = $fakeData['update'];
        // Target a disposable admin (never the account fakeLogin authenticates)
        // so the update/toggle/remove-role/delete cases cannot invalidate the
        // token used by the remaining authenticated cases in this section.
        $baseAdminEmail = config('admins.base_admins.0.email', 'admin@base.com');
        $latestRecord = Admins::where('email', '!=', $baseAdminEmail)->latest()->first();
        if (! $latestRecord) {
            $latestRecord = Admins::create([
                'name' => 'Documentation Target Admin',
                'email' => 'documentation-target@base.com',
                'phone' => '0161'.rand(1000000, 9999999),
                'password' => 'P@ss0rd',
                'job_title' => 'Administrator',
                'contract_type' => 'full_time',
                'is_active' => ActiveStatus::Active->value,
            ]);
        }
        $paramForUpdate['id'] = $latestRecord->id;

        // Use roles that currently exist (untrashed) for create/update/remove-role,
        // since a previous documentation run may have soft-deleted role records.
        $roleIds = Roles::withoutTrashed()->orderBy('id')->pluck('id')->take(2)->all();
        $params['roles'] = array_map(fn ($id) => ['role_id' => $id], $roleIds);
        $paramForUpdate['roles'] = $params['roles'];

        $trashedRecord = Admins::onlyTrashed()->latest()->first();
        $trashedTargetId = $trashedRecord->id ?? $paramForUpdate['id'];
        $paramForUpdate['email'] = sprintf('admin_upd+%s@gmail.com', $uniqueSuffix);
        $paramForUpdate['phone'] = '0122'.rand(1000000, 9999999);
        $paramForUpdate['name'] = 'Admin Updated '.strtoupper($uniqueSuffix);
        $expectedKeys = $fakeData['expected_keys'];
        $descriptions = app(ApiDocumentationController::class)->returnDescription($model);

        $paramForProfile = $fakeData['update_profile'];
        $paramForProfile['phone'] = '0155'.rand(1000000, 9999999);

        $paramForChangePassword = $fakeData['change_password'];

        // Pre-create a real export file through the running app (executes as the
        // web server user, which owns the exports directory) so the download case
        // can hit an existing file. The download endpoint deletes it after serving.
        $exportFileName = null;
        if ($fakeLogin && $paramForUpdate['id']) {
            $exportRoute = 'dashboard/activity-logs/'.$paramForUpdate['id'].'/export';
            $exportHeaders = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                config('security.api_key.header_name') => apiKeyGenerator(),
            ];
            $timestamp = time();
            if (config('security.timestamp.enabled')) {
                $exportHeaders[config('security.timestamp.header_name')] = $timestamp;
            }
            if (config('security.signature.enabled')) {
                $exportHeaders[config('security.signature.header_name')] = signatureKeyGenerator('GET', [], '/api/v1/'.$exportRoute, $timestamp);
            }
            $exportResponse = Http::withHeaders($exportHeaders)
                ->withToken($fakeLogin)
                ->get(config('app.url').'/api/v1/'.$exportRoute);
            $exportFileName = $exportResponse->json('data.file_name');
        }

        $createIndex = [
            $testCasesObject->handle(type: 'POST', name: $descriptions['create']['name'], route: 'dashboard/'.$model, params: $params, auth: $fakeLogin, desc: $descriptions['create']['description'], shortDesc: $descriptions['create']['short_description'], notes: CreateAdminsRequest::class),
            $testCasesObject->handle(type: 'GET', name: $descriptions['index']['name'], route: 'dashboard/'.$model, params: [], auth: $fakeLogin, desc: $descriptions['index']['description'], shortDesc: $descriptions['index']['short_description']),
            $testCasesObject->handle(type: 'GET', name: 'Get Admin Activity Logs By Subject', route: 'dashboard/activity-logs/'.$paramForUpdate['id'], params: [], auth: $fakeLogin, desc: 'Returns the activity log entries that belong to the given subject id (e.g. an admin or role record).', shortDesc: 'Get activity logs by subject.'),
            $testCasesObject->handle(type: 'GET', name: 'Export Admin Activity Logs By Subject', route: 'dashboard/activity-logs/'.$paramForUpdate['id'].'/export', params: [], auth: $fakeLogin, desc: 'Generates a CSV file with the activity log entries for the given subject id, stores it on the public disk, and returns the file name, path, and a download link. The file is deleted automatically after it is downloaded.', shortDesc: 'Export activity logs by subject.'),
        ];
        if ($exportFileName) {
            $createIndex[] = $testCasesObject->handle(type: 'GET', name: 'Download Admin Activity Log Export', route: 'dashboard/activity-logs/export/'.$exportFileName.'/download', params: [], auth: $fakeLogin, desc: 'Downloads an exported activity log CSV file and deletes it from storage after the download completes.', shortDesc: 'Download exported activity log file.');
        }
        $showUpdateDelete = [
            $testCasesObject->handle(type: 'GET', name: $descriptions['show']['name'], route: 'dashboard/'.$model.'/'.$paramForUpdate['id'], params: $expectedKeys, auth: $fakeLogin, desc: $descriptions['show']['description'], shortDesc: $descriptions['show']['short_description']),
            $testCasesObject->handle(type: 'PUT', name: $descriptions['update']['name'], route: 'dashboard/'.$model.'/'.$paramForUpdate['id'], params: $paramForUpdate, auth: $fakeLogin, desc: $descriptions['update']['description'], shortDesc: $descriptions['update']['short_description'], notes: UpdateAdminsRequest::class),
            $testCasesObject->handle(type: 'PATCH', name: $descriptions['active']['name'], route: 'dashboard/'.$model.'/'.$paramForUpdate['id'].'/toggle-active', params: [], auth: $fakeLogin, desc: $descriptions['active']['description'], shortDesc: $descriptions['active']['short_description']),
            $testCasesObject->handle(type: 'DELETE', name: 'Remove Role From Admin', route: 'dashboard/'.$model.'/'.$paramForUpdate['id'].'/roles/'.$params['roles'][0]['role_id'], params: [], auth: $fakeLogin, desc: 'Removes the specified role from the admin. Returns an error if the admin does not have this role.', shortDesc: 'Remove role from admin.'),
            $testCasesObject->handle(type: 'DELETE', name: $descriptions['delete']['name'], route: 'dashboard/'.$model.'/'.$paramForUpdate['id'], params: [], auth: $fakeLogin, desc: $descriptions['delete']['description'], shortDesc: $descriptions['delete']['short_description']),
            $testCasesObject->handle(type: 'GET', name: 'Get Admin Profile', route: 'dashboard/profile', params: [], auth: $fakeLogin, desc: 'Retrieves the profile information of the currently authenticated admin (identified via the auth token, not a URL id). Returns the same profile structure as the update-profile endpoint.', shortDesc: 'Get authenticated admin profile.'),
            $testCasesObject->handle(type: 'PUT', name: 'Update Admin Profile', route: 'dashboard/profile', params: $paramForProfile, auth: $fakeLogin, desc: "Updates the profile of the currently authenticated admin. Only editable fields (name, email, phone, avatar) are accepted; password, permissions, and roles are excluded and must be changed via their own endpoints. Send 'delete_avatar' as true to remove the current avatar. Since file uploads cannot be sent over a literal PUT/PATCH request, send this as POST with an 'X-HTTP-Method-Override: PUT' header when uploading an avatar.", shortDesc: 'Update authenticated admin profile.', notes: UpdateProfileRequest::class),
            $testCasesObject->handle(type: 'POST', name: 'Change Admin Password', route: 'dashboard/change-password', params: $paramForChangePassword, auth: $fakeLogin, desc: 'Changes the password of the currently authenticated admin. Requires the correct old_password; the new password must satisfy the project password policy and be confirmed via password_confirmation. The old/new password values are never returned in the response.', shortDesc: 'Change authenticated admin password.', notes: ChangePasswordRequest::class),
        ];
        $recycleBin = [
            $testCasesObject->handle(type: 'GET', name: $descriptions['trash']['name'], route: 'dashboard/'.$model.'/trash', params: $expectedKeys, auth: $fakeLogin, desc: $descriptions['trash']['description'], shortDesc: $descriptions['trash']['short_description']),
            $testCasesObject->handle(type: 'PATCH', name: $descriptions['restore']['name'], route: 'dashboard/'.$model.'/'.$trashedTargetId.'/restore', params: [], auth: $fakeLogin, desc: $descriptions['restore']['description'], shortDesc: $descriptions['restore']['short_description']),
            $testCasesObject->handle(type: 'DELETE', name: $descriptions['force_delete']['name'], route: 'dashboard/'.$model.'/'.$trashedTargetId.'/force-delete', params: [], auth: $fakeLogin, desc: $descriptions['force_delete']['description'], shortDesc: $descriptions['force_delete']['short_description']),
        ];

        return [
            $model.'-create-index' => $createIndex,
            $model.'-show-update-delete' => $showUpdateDelete,
            $model.'-recycle-bin' => $recycleBin,
        ];
    }

    private static function rolesModule($model, $testCasesObject, $fakeLogin)
    {
        $fakeData = self::fakeData($model);
        $params = $fakeData['create'];
        $paramForUpdate = $fakeData['update'];
        $latestRecord = Roles::latest()->first();
        $paramForUpdate['id'] = $latestRecord->id ?? 1;

        $trashedRecord = Roles::onlyTrashed()->latest()->first();
        $trashedTargetId = $trashedRecord->id ?? $paramForUpdate['id'];
        $expectedKeys = $fakeData['expected_keys'];
        $descriptions = app(ApiDocumentationController::class)->returnDescription($model);
        $latestAdminId = Admins::latest()->first()?->id ?? 1;
        $createIndex = [
            $testCasesObject->handle(type: 'POST', name: $descriptions['create']['name'], route: 'dashboard/'.$model, params: $params, auth: $fakeLogin, desc: $descriptions['create']['description'], shortDesc: $descriptions['create']['short_description'], notes: CreateRolesRequest::class),
            $testCasesObject->handle(type: 'GET', name: $descriptions['index']['name'], route: 'dashboard/'.$model, params: [], auth: $fakeLogin, desc: $descriptions['index']['description'], shortDesc: $descriptions['index']['short_description']),
            $testCasesObject->handle(type: 'GET', name: 'Search Roles', route: 'dashboard/'.$model.'?search=Admin', params: [], auth: $fakeLogin, desc: 'Retrieves a paginated list of roles whose translated name matches the optional search keyword (matches against any available locale).', shortDesc: 'Search roles by name.'),
            $testCasesObject->handle(type: 'GET', name: 'Get Permission Models', route: 'dashboard/models', params: [], auth: $fakeLogin, desc: 'Retrieves every module with its available permissions and IDs, for use when building the role create/edit permissions form. Does not require a role id.', shortDesc: 'List permission models.'),
            $testCasesObject->handle(type: 'GET', name: 'Get Role Admins', route: 'dashboard/'.$model.'/'.$paramForUpdate['id'].'/admins', params: [], auth: $fakeLogin, desc: 'Retrieves a paginated list of admins assigned to the specified role.', shortDesc: 'List role admins.'),
            $testCasesObject->handle(type: 'POST', name: 'Assign Admins To Role', route: 'dashboard/'.$model.'/'.$paramForUpdate['id'].'/admins', params: ['admin_ids' => [$latestAdminId]], auth: $fakeLogin, desc: 'Assigns one or more existing admins to the specified role. Accepts an array of admin_ids. Re-assigning an admin already on the role has no effect - duplicates are safely ignored. Returns the updated role (with refreshed admins_count) and the list of assigned admins.', shortDesc: 'Assign admins to a role.', notes: AssignAdminsToRoleRequest::class),
            $testCasesObject->handle(type: 'GET', name: 'Get Role Available Admins', route: 'dashboard/'.$model.'/'.$paramForUpdate['id'].'/available-admins', params: [], auth: $fakeLogin, desc: 'Retrieves a list of admins that are not assigned to the specified role.', shortDesc: 'List available admins.'),
            $testCasesObject->handle(type: 'GET', name: 'Search Available Admins', route: 'dashboard/'.$model.'/'.$paramForUpdate['id'].'/available-admins?search=sarah', params: [], auth: $fakeLogin, desc: 'Retrieves admins not assigned to the specified role, filtered by name. The search parameter is optional and combines with the existing role-exclusion filter.', shortDesc: 'Search available admins by name.'),
            $testCasesObject->handle(type: 'GET', name: 'Get Role Permissions', route: 'dashboard/'.$model.'/'.$paramForUpdate['id'].'/permissions', params: [], auth: $fakeLogin, desc: 'Retrieves the permissions assigned to the specified role, categorized by module.', shortDesc: 'List role permissions.'),
        ];
        $showUpdateDelete = [
            $testCasesObject->handle(type: 'GET', name: $descriptions['show']['name'], route: 'dashboard/'.$model.'/'.$paramForUpdate['id'], params: $expectedKeys, auth: $fakeLogin, desc: $descriptions['show']['description'], shortDesc: $descriptions['show']['short_description']),
            $testCasesObject->handle(type: 'PUT', name: $descriptions['update']['name'], route: 'dashboard/'.$model.'/'.$paramForUpdate['id'], params: $paramForUpdate, auth: $fakeLogin, desc: $descriptions['update']['description'], shortDesc: $descriptions['update']['short_description'], notes: UpdateRolesRequest::class),
            $testCasesObject->handle(type: 'PATCH', name: $descriptions['active']['name'], route: 'dashboard/'.$model.'/'.$paramForUpdate['id'].'/toggle-active', params: [], auth: $fakeLogin, desc: $descriptions['active']['description'], shortDesc: $descriptions['active']['short_description']),
            $testCasesObject->handle(type: 'DELETE', name: $descriptions['delete']['name'], route: 'dashboard/'.$model.'/'.$paramForUpdate['id'], params: [], auth: $fakeLogin, desc: $descriptions['delete']['description'], shortDesc: $descriptions['delete']['short_description']),
        ];

        $recycleBin = [
            $testCasesObject->handle(type: 'GET', name: $descriptions['trash']['name'], route: 'dashboard/'.$model.'/trash', params: $expectedKeys, auth: $fakeLogin, desc: $descriptions['trash']['description'], shortDesc: $descriptions['trash']['short_description']),
            $testCasesObject->handle(type: 'PATCH', name: $descriptions['restore']['name'], route: 'dashboard/'.$model.'/'.$trashedTargetId.'/restore', params: [], auth: $fakeLogin, desc: $descriptions['restore']['description'], shortDesc: $descriptions['restore']['short_description']),
            $testCasesObject->handle(type: 'DELETE', name: $descriptions['force_delete']['name'], route: 'dashboard/'.$model.'/'.$trashedTargetId.'/force-delete', params: [], auth: $fakeLogin, desc: $descriptions['force_delete']['description'], shortDesc: $descriptions['force_delete']['short_description']),
        ];

        return [
            $model.'-create-index' => $createIndex,
            $model.'-show-update-delete' => $showUpdateDelete,
            $model.'-recycle-bin' => $recycleBin,
        ];
    }
}
