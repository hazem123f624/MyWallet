@extends('dashboard.layouts.app')

@section('page', 'profile')

@section('content')
    <x-dashboard.page-header
        title="Profile"
        description="Update your administrator profile and password."
    />

    <div class="dash-profile-grid">
        <section class="dash-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Account details</h2>
                    <p>Your administrator identity and contact information.</p>
                </div>
            </div>

            <form id="profileForm" class="dash-form dash-panel-body">
                <div class="dash-profile-avatar">
                    <div class="dash-profile-avatar-preview" id="profileAvatarPreview">
                        <x-dashboard.icon name="profile" />
                    </div>

                    <div class="dash-profile-avatar-copy">
                        <label class="dash-button dash-button-ghost dash-button-xs" for="profileAvatar">Upload avatar</label>
                        <input id="profileAvatar" name="avatar" type="file" accept="image/jpeg,image/png,image/jpg,image/gif" hidden>
                        <button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" id="profileDeleteAvatar">
                            Remove
                        </button>
                        <input id="profileDeleteAvatarFlag" name="delete_avatar" type="hidden" value="0">
                        <p class="dash-table-subtext">JPG, PNG, or GIF. Maximum 2 MB.</p>
                    </div>
                </div>

                <div class="dash-form-grid">
                    <div class="dash-field">
                        <label for="profileName">Name</label>
                        <input id="profileName" name="name" type="text" required>
                    </div>

                    <div class="dash-field">
                        <label for="profileEmail">Email</label>
                        <input id="profileEmail" type="email" disabled>
                    </div>

                    <div class="dash-field">
                        <label for="profilePhone">Phone</label>
                        <input id="profilePhone" name="phone" type="text">
                    </div>

                    <div class="dash-field">
                        <label for="profileRoles">Roles</label>
                        <input id="profileRoles" type="text" disabled>
                    </div>
                </div>

                <p class="dash-form-error" id="profileFormError" hidden></p>

                <div>
                    <button class="dash-button dash-button-primary" type="submit" id="profileSubmit">
                        Save changes
                    </button>
                </div>
            </form>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Change password</h2>
                    <p>Use a strong password that you do not reuse elsewhere.</p>
                </div>
            </div>

            <form id="passwordForm" class="dash-form dash-panel-body">
                <div class="dash-field">
                    <label for="oldPassword">Current password</label>
                    <div class="dash-password-wrap">
                        <input id="oldPassword" name="old_password" type="password" autocomplete="current-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="oldPassword" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="newPassword">New password</label>
                    <div class="dash-password-wrap">
                        <input id="newPassword" name="new_password" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="newPassword" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="newPasswordConfirmation">Confirm new password</label>
                    <div class="dash-password-wrap">
                        <input id="newPasswordConfirmation" name="new_password_confirmation" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="newPasswordConfirmation" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <p class="dash-form-error" id="passwordFormError" hidden></p>

                <div>
                    <button class="dash-button dash-button-primary" type="submit" id="passwordSubmit">
                        Update password
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
