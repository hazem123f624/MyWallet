<?php

namespace Tests\Feature;

use App\Services\Security\Models\ApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Settings\Enums\SettingsType;
use Modules\Settings\Models\Settings;
use Modules\Users\Models\User;
use Tests\TestCase;

class UserDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    private Settings $country;

    protected function setUp(): void
    {
        parent::setUp();

        ApiClient::create([
            'name' => config('security.test_key'),
            'api_key' => 'user-dashboard-test-key',
            'secret' => str_repeat('b', 64),
            'is_active' => true,
        ]);

        $this->country = Settings::create([
            'type' => SettingsType::COUNTRY->value,
            'name_en' => 'Egypt',
            'name_ar' => 'مصر',
            'is_active' => true,
            'data' => [
                'code' => 'EG',
                'phone_code' => '20',
                'phone_length' => 11,
                'currency' => 'EGP',
                'currency_symbol' => 'E£',
            ],
        ]);

        Cache::flush();
    }

    public function test_guest_is_redirected_to_user_login(): void
    {
        $this->get('/user')->assertRedirect('/user/login');
    }

    public function test_login_page_exposes_country_and_api_configuration(): void
    {
        $this->get('/user/login')
            ->assertOk()
            ->assertSee('dashboard-api-base', false)
            ->assertSee('Egypt', false)
            ->assertSee('Forgot password?', false)
            ->assertSee('Create an account', false)
            ->assertSee('registerPanel', false);
    }

    public function test_verified_active_user_can_log_in(): void
    {
        $user = $this->createUser();

        $this->post('/user/login', $this->loginPayload())
            ->assertRedirect('/user');

        $this->assertAuthenticatedAs($user, 'users');
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->createUser();

        $this->post('/user/login', $this->loginPayload(password: 'wrong-password'))
            ->assertSessionHasErrors('credentials');

        $this->assertGuest('users');
    }

    public function test_unverified_user_cannot_log_in(): void
    {
        $this->createUser(['phone_verified_at' => null]);

        $this->post('/user/login', $this->loginPayload())
            ->assertSessionHasErrors('phone');

        $this->assertGuest('users');
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $this->createUser(['is_active' => false]);

        $this->post('/user/login', $this->loginPayload())
            ->assertSessionHasErrors('phone');

        $this->assertGuest('users');
    }

    public function test_authenticated_user_can_open_every_dashboard_page(): void
    {
        $user = $this->createUser();
        $this->actingAs($user, 'users');

        $this->get('/user')->assertOk()->assertSee('data-page="overview"', false);
        $this->get('/user/wallets')->assertOk()->assertSee('data-page="wallets"', false);
        $this->get('/user/bank-accounts')->assertOk()->assertSee('data-page="bank-accounts"', false);
        $this->get('/user/transfers')->assertOk()->assertSee('data-page="transfers"', false);
        $this->get('/user/transactions')->assertOk()->assertSee('data-page="transactions"', false);
        $this->get('/user/beneficiaries')->assertOk()->assertSee('data-page="beneficiaries"', false);
        $this->get('/user/profile')->assertOk()->assertSee('data-page="profile"', false);
        $this->get('/user/security')->assertOk()->assertSee('data-page="security"', false);
    }

    public function test_modal_submit_buttons_are_wired_to_their_forms(): void
    {
        $user = $this->createUser();
        $this->actingAs($user, 'users');

        $this->get('/user/bank-accounts')
            ->assertOk()
            ->assertSee('form="linkBankForm"', false)
            ->assertSee('form="verifyBankForm"', false);

        $this->get('/user/wallets')
            ->assertOk()
            ->assertSee('form="subWalletForm"', false)
            ->assertSee('form="allocateForm"', false)
            ->assertSee('form="redistributeForm"', false);

        $this->get('/user/beneficiaries')
            ->assertOk()
            ->assertSee('form="beneficiaryForm"', false);

        $this->get('/user/profile')
            ->assertOk()
            ->assertSee('form="verifyPhoneForm"', false);
    }

    public function test_suspended_user_session_is_rejected(): void
    {
        $user = $this->createUser(['is_active' => false]);
        $this->actingAs($user, 'users');

        $this->get('/user')->assertRedirect('/user/login');
    }

    public function test_unverified_user_session_is_rejected(): void
    {
        $user = $this->createUser(['phone_verified_at' => null]);
        $this->actingAs($user, 'users');

        $this->get('/user')->assertRedirect('/user/login');
    }

    public function test_logout_revokes_the_dashboard_token(): void
    {
        $this->createUser();

        $this->post('/user/login', $this->loginPayload())->assertRedirect('/user');

        $this->post('/user/logout')->assertRedirect('/user/login');

        $this->assertGuest('users');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Wallet Owner',
            'email' => 'wallet-owner@example.com',
            'phone' => '+201011112222',
            'country_id' => $this->country->id,
            'password' => 'P@ssw0rd123',
            'is_active' => true,
            'phone_verified_at' => now(),
        ], $overrides));
    }

    private function loginPayload(string $password = 'P@ssw0rd123'): array
    {
        return [
            'country_code' => '20',
            'phone' => '01011112222',
            'password' => $password,
        ];
    }
}
