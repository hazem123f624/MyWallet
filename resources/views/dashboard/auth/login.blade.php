@extends('dashboard.layouts.guest')

@section('content')
    <section class="dash-login-card">
        <button class="dash-icon-button dash-login-theme" type="button" data-login-theme-toggle aria-label="Toggle dark mode">
            <span data-theme-icon="moon"><x-dashboard.icon name="moon" /></span>
            <span data-theme-icon="sun" hidden><x-dashboard.icon name="sun" /></span>
        </button>

        <div class="dash-login-brand">
            <span class="dash-brand-icon"><img src="{{ asset('images/wallet-icon.svg') }}" alt=""></span>
            <div>
                <strong>{{ config('app.name') }}</strong>
                <span>Administration</span>
            </div>
        </div>

        <div class="dash-login-heading">
            <span class="dash-eyebrow">Welcome back</span>
            <h1>Sign in to the dashboard</h1>
            <p>Use your administrator account to manage users and account status.</p>
        </div>

        @if(request()->boolean('expired'))
            <p class="dash-inline-note">Your session expired. Please sign in again.</p>
        @endif

        <div id="loginPanel">
            <form method="POST" action="{{ route('dashboard.login.store') }}" class="dash-form">
                @csrf

                <div class="dash-field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="username"
                        required
                        value="{{ old('email', 'admin@base.com') }}"
                    >
                </div>

                <div class="dash-field">
                    <label for="password">Password</label>
                    <div class="dash-password-wrap">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                        >
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="password" aria-label="Show password">
                            <x-dashboard.icon name="eye" />
                        </button>
                    </div>
                    <p class="dash-caps-warning" data-caps-warning hidden>Caps Lock is on.</p>
                </div>

                @if($errors->any())
                    <p class="dash-form-error">{{ $errors->first() }}</p>
                @endif

                <button class="dash-button dash-button-primary dash-button-block" type="submit">
                    Sign in
                </button>
            </form>

            <button class="dash-login-link" type="button" id="loginRecoveryLink">
                Forgot password?
            </button>
        </div>

        <div id="recoveryPanel" class="dash-login-recovery" hidden>
            <div class="dash-login-heading dash-login-heading-compact">
                <span class="dash-eyebrow">Account recovery</span>
                <h2>Reset your password</h2>
                <p>We will send a verification code to your registered email address.</p>
            </div>

            <form id="recoveryRequestForm" class="dash-form">
                <div class="dash-field">
                    <label for="recoveryEmail">Email address</label>
                    <input id="recoveryEmail" name="email" type="email" autocomplete="username" required>
                </div>

                <button class="dash-button dash-button-primary dash-button-block" type="submit" id="recoveryRequestSubmit">
                    Send verification code
                </button>
            </form>

            <form id="recoveryVerifyForm" class="dash-form" hidden>
                <div class="dash-field">
                    <label for="recoveryVerifyEmail">Email address</label>
                    <input id="recoveryVerifyEmail" type="email" readonly>
                </div>

                <div class="dash-field">
                    <label for="recoveryOtp">Verification code</label>
                    <input id="recoveryOtp" name="otp" type="text" inputmode="numeric" maxlength="4" autocomplete="one-time-code" required>
                </div>

                <input type="hidden" id="recoveryToken">

                <div class="dash-toolbar-actions">
                    <button class="dash-button dash-button-primary" type="submit" id="recoveryVerifySubmit">
                        Verify code
                    </button>
                    <button class="dash-button dash-button-ghost" type="button" id="recoveryResend">
                        Resend code
                    </button>
                </div>
            </form>

            <form id="recoveryResetForm" class="dash-form" hidden>
                <div class="dash-field">
                    <label for="recoveryPassword">New password</label>
                    <div class="dash-password-wrap">
                        <input id="recoveryPassword" name="password" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="recoveryPassword" aria-label="Show password">
                            <x-dashboard.icon name="eye" />
                        </button>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="recoveryPasswordConfirmation">Confirm new password</label>
                    <div class="dash-password-wrap">
                        <input id="recoveryPasswordConfirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="recoveryPasswordConfirmation" aria-label="Show password">
                            <x-dashboard.icon name="eye" />
                        </button>
                    </div>
                </div>

                <button class="dash-button dash-button-primary dash-button-block" type="submit" id="recoveryResetSubmit">
                    Reset password
                </button>
            </form>

            <p class="dash-form-error" id="recoveryError" hidden></p>
            <p class="dash-form-success" id="recoveryMessage" hidden></p>

            <button class="dash-login-link" type="button" id="recoveryBack">
                Back to sign in
            </button>
        </div>

        <p class="dash-login-footer">Protected administration area.</p>
    </section>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/dashboard/pages/login.js') }}?v=1"></script>
@endpush
