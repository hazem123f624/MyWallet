<?php

namespace App\Http\Controllers;

use App\Services\Documentations\DocumentationService;

class ApiDocumentationController extends Controller
{
    public function index(DocumentationService $service)
    {
        return $service->homePage();
    }

    public function returnDescription($modelName)
    {
        $modelName = \Str::singular(ucfirst($modelName));
        return [
            'create' => [
                'name' => 'Create ' . $modelName,
                'description' => 'Creates a new ' . $modelName . ' in the system using the provided data. The request validates the required fields before storing the resource in the database. Upon successful creation, the system returns the newly created record.',
                'short_description' => 'Create a new ' . $modelName . '.'
            ],

            'update' => [
                'name' => 'Update ' . $modelName,
                'description' => 'Updates the specified ' . $modelName . ' with the provided data. The system validates the request and applies the changes to the existing record.',
                'short_description' => 'Update an existing ' . $modelName . '.'
            ],

            'delete' => [
                'name' => 'Delete ' . $modelName,
                'description' => 'Deletes the specified ' . $modelName . ' from the system using its unique identifier. Depending on the system configuration, the deletion may be permanent or soft deleted.',
                'short_description' => 'Delete a ' . $modelName . '.'
            ],

            'show' => [
                'name' => 'Get ' . $modelName . ' Details',
                'description' => 'Retrieves detailed information about a specific ' . $modelName . ' using its unique identifier.',
                'short_description' => 'Retrieve ' . $modelName . ' details.'
            ],

            'index' => [
                'name' => 'Get ' . $modelName . ' List',
                'description' => 'Retrieves a list of ' . $modelName . ' records from the system. The response may support pagination, filtering, and sorting based on the provided request parameters.',
                'short_description' => 'Retrieve a list of ' . $modelName . '.'
            ],
            'active' => [
                'name' => 'Toggle ' . $modelName . ' Status',
                'description' => 'Updates the status of the specified ' . $modelName . ' by toggling it between active and inactive. This operation allows enabling or disabling the resource without deleting it from the system.',
                'short_description' => 'Switch the ' . $modelName . ' status between active and inactive.'
            ],
            'restore' => [
                'name' => "Restore {$modelName}",
                'description' => "Restore a previously deleted {$modelName} record from the recycle bin.",
                'short_description' => "Restore deleted {$modelName}."
            ],
            'force_delete' => [
                'name' => "Permanently Delete {$modelName}",
                'description' => "Permanently remove the {$modelName} record from the system. This action cannot be undone.",
                'short_description' => "Force delete {$modelName}."
            ],
            'trash' => [
                'name' => "Get Deleted {$modelName}",
                'description' => "Retrieve a list of soft-deleted {$modelName} records from the recycle bin.",
                'short_description' => "List deleted {$modelName}."
            ],
            'bulk_delete' => [
                'name' => "Bulk Delete {$modelName}",
                'description' => "Delete multiple {$modelName} records from the system.",
                'short_description' => "Bulk delete {$modelName}."
            ],
            'move_to_main_directory' => [
                'name' => "Move {$modelName} to Main Directory",
                'description' => "Move the specified {$modelName} records to the main directory.",
                'short_description' => "Move {$modelName} to main directory."
            ],


            // Mobile Auth Flow
            'mobile_register' => [
                'name' => 'Register Account',
                'description' => 'Initiate account registration. The user data is temporarily cached, and an OTP is sent to the provided phone number. The user record is NOT created in the database until the OTP is verified.',
                'short_description' => 'Signup and receive OTP.'
            ],
            'mobile_verify' => [
                'name' => 'Verify OTP',
                'description' => 'Verify an OTP received via SMS. This endpoint handles both initial account activation (creating the user record) and phone number changes.',
                'short_description' => 'Verify registration or phone change.'
            ],
            'mobile_login' => [
                'name' => 'Login',
                'description' => 'Authenticate using phone number and password. Returns a Bearer token upon success.',
                'short_description' => 'Standard mobile login.'
            ],
            'mobile_resend_otp' => [
                'name' => 'Resend OTP',
                'description' => 'Request a new OTP code if the previous one expired or was not received. Works for both pending registrations and existing users.',
                'short_description' => 'Resend verification code.'
            ],
            'mobile_forget_password' => [
                'name' => 'Forget Password',
                'description' => 'Request a password reset OTP by providing the registered phone number.',
                'short_description' => 'Request reset OTP.'
            ],
            'mobile_reset_password' => [
                'name' => 'Reset Password',
                'description' => 'Reset the account password by providing the OTP received and the new password.',
                'short_description' => 'Complete password reset.'
            ],
            'mobile_change_password' => [
                'name' => 'Change Password',
                'description' => 'Update the password for the currently authenticated user. Requires the current (old) password for security.',
                'short_description' => 'Update current password.'
            ],
            'mobile_set_transaction_pin' => [
                'name' => 'Set Transaction PIN',
                'description' => 'Set the first transaction PIN for the authenticated user. The PIN is stored as a secure hash and is never returned in API responses.',
                'short_description' => 'Create the transaction PIN.'
            ],
            'mobile_change_transaction_pin' => [
                'name' => 'Change Transaction PIN',
                'description' => 'Replace the transaction PIN after confirming the current PIN. Incorrect attempts count towards the PIN lockout policy.',
                'short_description' => 'Update the transaction PIN.'
            ],
            'mobile_forget_transaction_pin' => [
                'name' => 'Request Transaction PIN Reset',
                'description' => 'Send an OTP to the registered phone number so the authenticated user can reset the transaction PIN.',
                'short_description' => 'Request a PIN reset OTP.'
            ],
            'mobile_reset_transaction_pin' => [
                'name' => 'Reset Transaction PIN',
                'description' => 'Reset the transaction PIN using the OTP sent to the registered phone number. A successful reset clears any active PIN lock.',
                'short_description' => 'Reset the transaction PIN.'
            ],
            'mobile_verify_transaction_pin' => [
                'name' => 'Verify Transaction PIN',
                'description' => 'Verify the transaction PIN before a sensitive operation. Repeated incorrect attempts lock further verification until the PIN is reset.',
                'short_description' => 'Confirm the transaction PIN.'
            ],
            'mobile_profile' => [
                'name' => 'Get Profile',
                'description' => 'Retrieve the details of the currently authenticated user.',
                'short_description' => 'View current profile.'
            ],
            'mobile_update_profile' => [
                'name' => 'Update Profile',
                'description' => 'Update profile details like name, email, or phone. Note: Changing the phone number will trigger a new verification cycle.',
                'short_description' => 'Edit profile details.'
            ],
            'mobile_delete_account' => [
                'name' => 'Delete Account',
                'description' => 'Delete the account of the currently authenticated user. The account is soft deleted and every issued access token is revoked, so the user is no longer treated as an active user and cannot sign in again. A Super Admin can still restore the account from the recycle bin.',
                'short_description' => 'Delete my own account.'
            ],

            // Budgets Module
            'budget_index' => [
                'name' => 'List Budgets',
                'description' => 'Retrieves a list of all budgets the user owns or has access to. Supports filtering by status and type.',
                'short_description' => 'List my budgets.'
            ],
            'budget_create' => [
                'name' => 'Create Budget',
                'description' => 'Create a new budget. Requires specifying the name, overall limit, and budget type (one-time or scheduled).',
                'short_description' => 'Initialise a new budget.'
            ],
            'budget_summary' => [
                'name' => 'Get Budget Summary',
                'description' => 'Retrieves financial overview for a specific budget, including total income, expenses, and remaining balance percentage. Supports filtering by month and year query parameters.',
                'short_description' => 'View budget metrics.'
            ],
            'budget_hide' => [
                'name' => 'hide Budget',
                'description' => 'Updates the budget status to hidden. Hidden budgets are still accessible but may be filtered out from default listings.',
                'short_description' => 'Hide a budget.'
            ],
            'budget_leave' => [
                'name' => 'Leave Budget',
                'description' => 'Allow a collaborator to voluntarily leave a shared budget.',
                'short_description' => 'Leave a shared budget.'
            ],

            // Transactions
            'transaction_index' => [
                'name' => 'List Transactions',
                'description' => 'Retrieves a list of transactions for a specific budget. Supports date range filtering, category filtering, and listing trashed transactions.',
                'short_description' => 'List budget history.'
            ],
            'transaction_income' => [
                'name' => 'Add Income',
                'description' => 'Directly record an income transaction. This is a helper endpoint that forces the transaction type to income.',
                'short_description' => 'Record income.'
            ],
            'transaction_expense' => [
                'name' => 'Add Expense',
                'description' => 'Directly record an expense transaction. This is a helper endpoint that forces the transaction type to expense.',
                'short_description' => 'Record expense.'
            ],

            // Splits / Sharing
            'split_store' => [
                'name' => 'Split Budget',
                'description' => 'Distribute the budget total amount among multiple users as allocations.',
                'short_description' => 'Divide costs with others.'
            ],
            'split_pending' => [
                'name' => 'My Pending Splits',
                'description' => 'Retrieve all split allocations that the current user owes or is owed that are not yet settled.',
                'short_description' => 'View unsettled splits.'
            ],
            'split_show' => [
                'name' => 'View Splits',
                'description' => 'View how a specific budget is divided among collaborators.',
                'short_description' => 'View split info.'
            ],
            'split_update' => [
                'name' => 'Update Split',
                'description' => 'Modify the distribution amounts or percentages for a budget split.',
                'short_description' => 'Edit budget splits.'
            ],
            'split_settle' => [
                'name' => 'Settle Split',
                'description' => 'Mark a split allocation as settled.',
                'short_description' => 'Settle debt.'
            ],
            'budget_share_update' => [
                'name' => 'Update Shared Permission',
                'description' => 'Change the access level (viewer/editor) for a collaborator.',
                'short_description' => 'Update collaborator role.'
            ],
            'budget_shared_users' => [
                'name' => 'List Collaborators',
                'description' => 'Retrieve a list of all users who have access to this budget and their roles.',
                'short_description' => 'View who has access.'
            ],
            'share_remove_user' => [
                'name' => 'Remove Shared User',
                'description' => 'Remove a collaborator from a shared budget.',
                'short_description' => 'De-authorize a user.'
            ],

            // Schedules
            'schedule_store' => [
                'name' => 'Set Auto-Add Schedule',
                'description' => 'Configure or update an automatic income recurrence (daily, weekly, monthly) for a scheduled budget.',
                'short_description' => 'Automate budget top-up.'
            ],

            // Share Links
            'share_link_index' => [
                'name' => 'List Invitation Links',
                'description' => 'Retrieve a list of all active and expired public invitation links generated for this budget.',
                'short_description' => 'View all share links.'
            ],
            'share_link_create' => [
                'name' => 'Create Share Link',
                'description' => 'Create a new public invitation link with specific permissions, usage limits, and expiration date.',
                'short_description' => 'Create public join link.'
            ],
            'share_link_check' => [
                'name' => 'Check Token Validity (Public)',
                'description' => 'Publicly verify if an invitation token is valid and retrieve the budget owner and name before joining.',
                'short_description' => 'Preview invitation details.'
            ],
            'share_link_accept' => [
                'name' => 'Accept Join Link',
                'description' => 'Join a budget using a valid public invitation token. The user will be automatically added as a collaborator.',
                'short_description' => 'Join budget via link.'
            ],
        ];
    }


}
