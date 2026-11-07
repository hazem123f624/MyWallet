@extends('user.layouts.guest')

@section('content')
    @php
        $defaultCountry = collect($countries)->firstWhere('phone_code', '20') ?? ($countries[0] ?? null);
    @endphp

    <section class="dash-login-card user-login-card">
        <button class="dash-icon-button dash-login-theme" type="button" data-login-theme-toggle aria-label="Toggle dark mode">
            <span data-theme-icon="moon"><x-dashboard.icon name="moon" /></span>
            <span data-theme-icon="sun" hidden><x-dashboard.icon name="sun" /></span>
        </button>

        <div class="dash-login-brand">
            <span class="dash-brand-icon"><img src="{{ asset('images/wallet-icon.svg') }}" alt=""></span>
            <div>
                <strong>{{ config('app.name') }}</strong>
                <span>Personal wallet</span>
            </div>
        </div>

        @if(request()->boolean('expired'))
            <p class="dash-inline-note">Your session expired. Please sign in again.</p>
        @endif

        <div id="loginPanel">
            <div class="dash-login-heading">
                <span class="dash-eyebrow">Welcome back</span>
                <h1>Sign in to your wallet</h1>
                <p>Manage your balance, Sub Wallets, and funding source.</p>
            </div>

            <form method="POST" action="{{ route('user.login.store') }}" class="dash-form">
                @csrf

                <div class="user-phone-grid">
                    <div class="dash-field">
                        <label for="country_code">Country</label>
                        <select id="country_code" name="country_code" required>
                            @foreach($countries as $country)
                                <option value="{{ $country['phone_code'] }}" @selected(old('country_code', $defaultCountry['phone_code'] ?? '20') === $country['phone_code'])>
                                    {{ $country['name_en'] }} (+{{ $country['phone_code'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dash-field">
                        <label for="phone">Phone number</label>
                        <input id="phone" name="phone" type="tel" inputmode="numeric" autocomplete="username" required value="{{ old('phone') }}">
                    </div>
                </div>

                <div class="dash-field">
                    <label for="password">Password</label>
                    <div class="dash-password-wrap">
                        <input id="password" name="password" type="password" autocomplete="current-password" required>
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

            <p class="dash-form-success" id="loginMessage" hidden></p>

            <div class="user-login-divider"><span>New to MyWallet?</span></div>

            <button class="dash-button dash-button-ghost dash-button-block" type="button" id="loginRegisterLink">
                Create an account
            </button>
        </div>

        <div id="registerPanel" class="dash-login-recovery" hidden>
            <div class="dash-login-heading dash-login-heading-compact">
                <span class="dash-eyebrow">Create your account</span>
                <h2>Start using MyWallet</h2>
                <p>Register with your phone number and verify it with an OTP.</p>
            </div>

            <form id="registerForm" class="dash-form">
                <div class="dash-field">
                    <label for="registerName">Name</label>
                    <input id="registerName" type="text" required>
                </div>

                <div class="user-phone-grid">
                    <div class="dash-field">
                        <label for="registerCountry">Country</label>
                        <select id="registerCountry" required>
                            @foreach($countries as $country)
                                <option value="{{ $country['phone_code'] }}" @selected(old('country_code', $defaultCountry['phone_code'] ?? '20') === $country['phone_code'])>
                                    {{ $country['name_en'] }} (+{{ $country['phone_code'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dash-field">
                        <label for="registerPhone">Phone number</label>
                        <input id="registerPhone" type="tel" inputmode="numeric" required>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="registerEmail">Email <span class="dash-muted-text">(optional)</span></label>
                    <input id="registerEmail" type="email">
                </div>

                <div class="dash-field">
                    <label for="registerPassword">Password</label>
                    <div class="dash-password-wrap">
                        <input id="registerPassword" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="registerPassword" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="registerPasswordConfirmation">Confirm password</label>
                    <div class="dash-password-wrap">
                        <input id="registerPasswordConfirmation" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="registerPasswordConfirmation" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <button class="dash-button dash-button-primary dash-button-block" type="submit" id="registerSubmit">
                    Create account
                </button>
            </form>

            <form id="registerVerifyForm" class="dash-form" hidden>
                <div class="dash-field">
                    <label for="registerOtp">Verification code</label>
                    <input id="registerOtp" type="text" inputmode="numeric" maxlength="4" autocomplete="one-time-code" required>
                </div>

                <div class="dash-toolbar-actions">
                    <button class="dash-button dash-button-primary" type="submit" id="registerVerifySubmit">Verify account</button>
                    <button class="dash-button dash-button-ghost" type="button" id="registerResend">Resend code</button>
                </div>
            </form>

            <p class="dash-form-error" id="registerError" hidden></p>
            <p class="dash-form-success" id="registerMessage" hidden></p>

            <button class="dash-login-link" type="button" id="registerBack">Back to sign in</button>
        </div>

        <div id="recoveryPanel" class="dash-login-recovery" hidden>
            <div class="dash-login-heading dash-login-heading-compact">
                <span class="dash-eyebrow">Account recovery</span>
                <h2>Reset your password</h2>
                <p>We will send a verification code to your registered phone number.</p>
            </div>

            <form id="recoveryRequestForm" class="dash-form">
                <div class="user-phone-grid">
                    <div class="dash-field">
                        <label for="recoveryCountry">Country</label>
                        <select id="recoveryCountry" required>
                            @foreach($countries as $country)
                                <option value="{{ $country['phone_code'] }}" @selected(old('country_code', $defaultCountry['phone_code'] ?? '20') === $country['phone_code'])>
                                    {{ $country['name_en'] }} (+{{ $country['phone_code'] }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dash-field">
                        <label for="recoveryPhone">Phone number</label>
                        <input id="recoveryPhone" type="tel" inputmode="numeric" required value="{{ old('phone') }}">
                    </div>
                </div>

                <button class="dash-button dash-button-primary dash-button-block" type="submit" id="recoveryRequestSubmit">
                    Send verification code
                </button>
            </form>

            <form id="recoveryVerifyForm" class="dash-form" hidden>
                <div class="dash-field">
                    <label for="recoveryOtp">Verification code</label>
                    <input id="recoveryOtp" type="text" inputmode="numeric" maxlength="4" autocomplete="one-time-code" required>
                </div>

                <div class="dash-toolbar-actions">
                    <button class="dash-button dash-button-primary" type="submit" id="recoveryVerifySubmit">Verify code</button>
                    <button class="dash-button dash-button-ghost" type="button" id="recoveryResend">Resend code</button>
                </div>
            </form>

            <form id="recoveryResetForm" class="dash-form" hidden>
                <div class="dash-field">
                    <label for="recoveryPassword">New password</label>
                    <div class="dash-password-wrap">
                        <input id="recoveryPassword" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="recoveryPassword" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <div class="dash-field">
                    <label for="recoveryPasswordConfirmation">Confirm new password</label>
                    <div class="dash-password-wrap">
                        <input id="recoveryPasswordConfirmation" type="password" autocomplete="new-password" required>
                        <button class="dash-password-toggle" type="button" data-password-toggle data-target="recoveryPasswordConfirmation" aria-label="Show password"><x-dashboard.icon name="eye" /></button>
                    </div>
                </div>

                <button class="dash-button dash-button-primary dash-button-block" type="submit" id="recoveryResetSubmit">Reset password</button>
            </form>

            <p class="dash-form-error" id="recoveryError" hidden></p>
            <p class="dash-form-success" id="recoveryMessage" hidden></p>

            <button class="dash-login-link" type="button" id="recoveryBack">Back to sign in</button>
        </div>

        <p class="dash-login-footer">Protected personal wallet area.</p>
    </section>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/user/pages/login.js') }}?v={{ filemtime(public_path('js/user/pages/login.js')) }}"></script>
@endpush
