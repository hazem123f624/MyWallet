@extends('user.layouts.app')

@section('page', 'profile')

@section('content')
    <x-dashboard.page-header
        title="Profile"
        description="Keep your personal details and phone number up to date."
    />

    <div class="dash-profile-grid">
        <section class="dash-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Account details</h2>
                    <p>Your wallet identity and contact information.</p>
                </div>
            </div>

            <form id="userProfileForm" class="dash-form dash-panel-body">
                <div class="dash-profile-avatar">
                    <div class="dash-profile-avatar-preview" id="userAvatarPreview"><x-dashboard.icon name="profile" /></div>

                    <div class="dash-profile-avatar-copy">
                        <label class="dash-button dash-button-ghost dash-button-xs" for="userAvatar">Upload avatar</label>
                        <input id="userAvatar" type="file" accept="image/jpeg,image/png,image/jpg,image/gif" hidden>
                        <button class="dash-button dash-button-ghost dash-button-xs dash-button-danger-text" type="button" id="userDeleteAvatar">Remove</button>
                        <input id="userDeleteAvatarFlag" type="hidden" value="0">
                        <p class="dash-table-subtext">JPG, PNG, or GIF. Maximum 2 MB.</p>
                    </div>
                </div>

                <div class="dash-form-grid">
                    <div class="dash-field">
                        <label for="userName">Name</label>
                        <input id="userName" type="text" required>
                    </div>

                    <div class="dash-field">
                        <label for="userEmail">Email</label>
                        <input id="userEmail" type="email">
                    </div>

                    <div class="dash-field">
                        <label for="userPostalCode">Postal code</label>
                        <input id="userPostalCode" type="text" maxlength="20">
                    </div>
                </div>

                <div class="dash-field">
                    <label for="userAddress">Main address</label>
                    <input id="userAddress" type="text">
                </div>

                <div class="user-readonly-row">
                    <span>Phone</span>
                    <strong id="userPhoneReadonly">—</strong>
                    <span class="dash-badge dash-badge-neutral" id="userPhoneStatus">—</span>
                </div>

                <p class="dash-form-error" id="userProfileError" hidden></p>

                <div>
                    <button class="dash-button dash-button-primary" type="submit" id="userProfileSubmit">Save changes</button>
                </div>
            </form>
        </section>

        <div class="user-side-stack">
            <section class="dash-panel">
                <div class="dash-panel-heading">
                    <div>
                        <h2>Change phone number</h2>
                        <p>Changing your phone requires OTP verification.</p>
                    </div>
                </div>

                <form id="userPhoneForm" class="dash-form dash-panel-body">
                    <div class="user-phone-grid">
                        <div class="dash-field">
                            <label for="userCountryCode">Country</label>
                            <select id="userCountryCode" required>
                                @foreach($countries as $country)
                                    <option value="{{ $country['phone_code'] }}">{{ $country['name_en'] }} (+{{ $country['phone_code'] }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="dash-field">
                            <label for="userNewPhone">New phone number</label>
                            <input id="userNewPhone" type="tel" inputmode="numeric" required>
                        </div>
                    </div>

                    <p class="dash-form-error" id="userPhoneError" hidden></p>

                    <div>
                        <button class="dash-button dash-button-primary" type="submit" id="userPhoneSubmit">Send verification code</button>
                    </div>
                </form>
            </section>

            <section class="dash-panel user-danger-panel">
                <div class="dash-panel-heading">
                    <div>
                        <h2>Danger zone</h2>
                        <p>Deleting your account revokes access and signs you out.</p>
                    </div>
                </div>

                <div class="dash-panel-body">
                    <button class="dash-button dash-button-danger" type="button" id="userDeleteAccount">Delete my account</button>
                </div>
            </section>
        </div>
    </div>

    <x-dashboard.modal id="verifyPhoneModal" title="Verify new phone number">
        <form id="verifyPhoneForm" class="dash-form">
            <p class="dash-muted-text">Enter the code sent to your new phone number.</p>
            <p class="dash-form-success" id="verifyPhoneHint" hidden></p>

            <div class="dash-field">
                <label for="verifyPhoneOtp">Verification code</label>
                <input id="verifyPhoneOtp" type="text" inputmode="numeric" maxlength="4" required>
            </div>

            <p class="dash-form-error" id="verifyPhoneError" hidden></p>

            <x-slot:footer>
                <button class="dash-button dash-button-ghost" type="button" data-close-modal>Cancel</button>
                <button class="dash-button dash-button-primary" type="submit" id="verifyPhoneSubmit">Verify phone</button>
            </x-slot:footer>
        </form>
    </x-dashboard.modal>
@endsection
