@extends('user.layouts.app')

@section('page', 'security')

@section('content')
    <x-dashboard.page-header
        title="Security"
        description="Manage your password and the PIN used to confirm transfers."
    />

    <div class="user-security-grid">
        <section class="dash-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Change password</h2>
                    <p>Use a strong password you do not reuse elsewhere.</p>
                </div>
            </div>

            <form id="userPasswordForm" class="dash-form dash-panel-body">
                <div class="dash-field">
                    <label for="userOldPassword">Current password</label>
                    <div class="dash-password-wrap">
                        <input id="userOldPassword" type="password" autocomplete="current-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="userOldPassword" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="userNewPassword">New password</label>
                    <div class="dash-password-wrap">
                        <input id="userNewPassword" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="userNewPassword" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="userNewPasswordConfirmation">Confirm new password</label>
                    <div class="dash-password-wrap">
                        <input id="userNewPasswordConfirmation" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="userNewPasswordConfirmation" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <p class="dash-form-error" id="userPasswordError" hidden></p>

                <div>
                    <button class="dash-button dash-button-primary" type="submit" id="userPasswordSubmit">Update password</button>
                </div>
            </form>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-heading">
                <div>
                    <h2>Transaction PIN</h2>
                    <p>This PIN confirms transfers and other sensitive actions.</p>
                </div>
            </div>

            <div class="dash-panel-body user-security-stack">
                <form id="setPinForm" class="dash-form user-security-card">
                    <h3>Set PIN</h3>
                    <div class="dash-form-grid">
                        <div class="dash-field">
                            <label for="setPin">New PIN</label>
                            <input id="setPin" type="password" inputmode="numeric" maxlength="4" required>
                        </div>
                        <div class="dash-field">
                            <label for="setPinConfirmation">Confirm PIN</label>
                            <input id="setPinConfirmation" type="password" inputmode="numeric" maxlength="4" required>
                        </div>
                    </div>
                    <p class="dash-form-error" id="setPinError" hidden></p>
                    <button class="dash-button dash-button-primary" type="submit" id="setPinSubmit">Set PIN</button>
                </form>

                <form id="changePinForm" class="dash-form user-security-card">
                    <h3>Change PIN</h3>
                    <div class="dash-form-grid">
                        <div class="dash-field">
                            <label for="currentPin">Current PIN</label>
                            <input id="currentPin" type="password" inputmode="numeric" maxlength="4" required>
                        </div>
                        <div class="dash-field">
                            <label for="changePin">New PIN</label>
                            <input id="changePin" type="password" inputmode="numeric" maxlength="4" required>
                        </div>
                    </div>
                    <div class="dash-field">
                        <label for="changePinConfirmation">Confirm new PIN</label>
                        <input id="changePinConfirmation" type="password" inputmode="numeric" maxlength="4" required>
                    </div>
                    <p class="dash-form-error" id="changePinError" hidden></p>
                    <button class="dash-button dash-button-primary" type="submit" id="changePinSubmit">Change PIN</button>
                </form>

                <form id="resetPinForm" class="dash-form user-security-card">
                    <h3>Reset PIN</h3>
                    <p class="dash-muted-text">Request an OTP, then choose a new PIN.</p>
                    <button class="dash-button dash-button-ghost" type="button" id="sendPinResetOtp">Send reset code</button>
                    <p class="dash-form-success" id="pinResetHint" hidden></p>

                    <div class="dash-form-grid">
                        <div class="dash-field">
                            <label for="pinResetOtp">OTP</label>
                            <input id="pinResetOtp" type="text" inputmode="numeric" maxlength="4" required>
                        </div>
                        <div class="dash-field">
                            <label for="pinResetNew">New PIN</label>
                            <input id="pinResetNew" type="password" inputmode="numeric" maxlength="4" required>
                        </div>
                    </div>

                    <div class="dash-field">
                        <label for="pinResetConfirmation">Confirm new PIN</label>
                        <input id="pinResetConfirmation" type="password" inputmode="numeric" maxlength="4" required>
                    </div>

                    <p class="dash-form-error" id="resetPinError" hidden></p>
                    <button class="dash-button dash-button-primary" type="submit" id="resetPinSubmit">Reset PIN</button>
                </form>
            </div>
        </section>
    </div>
@endsection
