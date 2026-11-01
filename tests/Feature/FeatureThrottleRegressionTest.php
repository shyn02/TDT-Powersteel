<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\Auth\Login;
use App\Models\ContactMessage;
use App\Models\QuoteRequest;
use App\Models\Referral;
use App\Models\SiteSettings;
use App\Models\User;
use App\Models\UserProfile;
use Filament\Facades\Filament;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class FeatureThrottleRegressionTest extends TestCase
{
    public function test_feature_routes_have_no_throttle_middleware_and_password_reset_keeps_its_limit(): void
    {
        foreach (['submit_quote', 'quote_product_data', 'chat_messages_api', 'admin.nav_counts'] as $routeName) {
            $middleware = $this->routeMiddleware($routeName);

            $this->assertFalse(
                collect($middleware)->contains(fn (string $item): bool => str_starts_with($item, 'throttle:')),
                "Route [{$routeName}] must not have a feature throttle.",
            );
        }

        $this->assertContains('throttle:3,1', $this->routeMiddleware('password.request.post'));
    }

    public function test_identical_quote_payloads_are_all_persisted_when_submitted_rapidly(): void
    {
        $payload = [
            'clientName' => 'Rapid Quote Customer',
            'clientEmail' => 'rapid-quote@example.com',
            'clientContact' => '09171234567',
            'estimatedQty' => '100 pieces',
        ];

        $this->assertRapidSubmissionsPersisted($payload, QuoteRequest::class, 'email', $payload['clientEmail']);
    }

    public function test_identical_contact_payloads_are_all_persisted_when_submitted_rapidly(): void
    {
        $payload = [
            'cName' => 'Rapid Contact Customer',
            'cEmail' => 'rapid-contact@example.com',
            'cPhone' => '09171234568',
            'cMessage' => 'Please call me back.',
        ];

        $this->assertRapidSubmissionsPersisted($payload, ContactMessage::class, 'email', $payload['cEmail']);
    }

    public function test_identical_referral_payloads_are_all_persisted_when_submitted_rapidly(): void
    {
        $payload = [
            'ref_fullname' => 'Rapid Referral Customer',
            'ref_email' => 'rapid-referral@example.com',
            'ref_phone' => '09171234569',
            'ref_contact_person' => 'Referral Contact',
            'ref_referred_company' => 'Referral Company',
            'ref_project_type' => 'Construction',
            'ref_project_scale' => 'Large',
            'ref_region' => 'NCR',
            'ref_remarks' => 'Please follow up.',
        ];

        $this->assertRapidSubmissionsPersisted($payload, Referral::class, 'referrer_email', $payload['ref_email']);
    }

    public function test_quote_with_current_form_started_at_is_persisted(): void
    {
        $payload = [
            'clientName' => 'Current Timestamp Customer',
            'clientEmail' => 'current-timestamp@example.com',
            'clientContact' => '09171234570',
            'estimatedQty' => '50 pieces',
            'form_started_at' => (int) round(microtime(true) * 1000),
        ];

        $response = $this->postJson(route('submit_quote'), $payload);

        $response->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame(1, QuoteRequest::where('email', $payload['clientEmail'])->count());
    }

    public function test_password_reset_controller_accepts_three_requests_then_uses_300_second_decay(): void
    {
        $ip = '198.51.100.21';
        $key = "request-password-reset:{$ip}";
        RateLimiter::clear($key);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->withoutMiddleware(ThrottleRequests::class)->post(
                route('password.request.post'),
                ['email' => 'reset@example.com'],
            );

            $response->assertRedirect();
            $response->assertSessionHas('status');
        }

        $this->assertSame(3, RateLimiter::attempts($key));
        $this->assertGreaterThanOrEqual(299, RateLimiter::availableIn($key));

        $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])->withoutMiddleware(ThrottleRequests::class)->post(
            route('password.request.post'),
            ['email' => 'reset@example.com'],
        );

        $response->assertRedirect();
        $errors = session('errors');
        $this->assertNotNull($errors);
        $messages = data_get($errors, 'default.messages.email', []);
        $this->assertStringContainsString('Too many attempts', $messages[0] ?? '');
    }

    public function test_failed_password_uses_normalized_email_and_ip_key_and_site_settings(): void
    {
        SiteSettings::current()->update([
            'max_login_attempts' => 2,
            'lockout_minutes' => 7,
        ]);

        User::factory()->withKnownPassword('correct-password')->create([
            'email' => 'KNOWN.USER@EXAMPLE.COM',
        ]);

        $ip = '127.0.0.1';
        $key = "known.user@example.com|{$ip}";
        RateLimiter::clear($key);

        $this->loginAttempt('KNOWN.USER@EXAMPLE.COM', 'wrong-password', $ip)
            ->assertHasFormErrors(['email']);
        $this->assertSame($ip, request()->ip());
        $this->loginAttempt('KNOWN.USER@EXAMPLE.COM', 'wrong-password', $ip)
            ->assertHasFormErrors(['email']);

        $this->assertSame(2, RateLimiter::attempts($key));
        $this->assertGreaterThanOrEqual(419, RateLimiter::availableIn($key));

        $lockedOut = $this->loginAttempt('KNOWN.USER@EXAMPLE.COM', 'wrong-password', $ip)
            ->assertHasFormErrors(['email']);
        $this->assertStringContainsString(
            'lockout: 7 min',
            $lockedOut->errors()->first('data.email'),
        );
    }

    public function test_invalid_mfa_increments_the_normalized_email_and_ip_key(): void
    {
        SiteSettings::current()->update([
            'max_login_attempts' => 5,
            'lockout_minutes' => 9,
        ]);

        $user = User::factory()->withKnownPassword('correct-password')->create([
            'email' => 'MFA.USER@EXAMPLE.COM',
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_enabled' => true,
            'mfa_recovery_codes' => [Hash::make('valid-recovery-code')],
        ]);
        UserProfile::where('user_id', $user->id)->update(['position' => 'admin']);

        $ip = '127.0.0.1';
        $key = "mfa.user@example.com|{$ip}";
        RateLimiter::clear($key);

        $this->loginAttempt('MFA.USER@EXAMPLE.COM', 'correct-password', $ip, 'invalid-code')
            ->assertHasFormErrors(['mfa_code']);

        $this->assertGreaterThan(0, RateLimiter::attempts($key));
        $this->assertGreaterThanOrEqual(539, RateLimiter::availableIn($key));
    }

    public function test_successful_mfa_login_clears_the_normalized_email_and_ip_key(): void
    {
        $user = User::factory()->withKnownPassword('correct-password')->create([
            'email' => 'SUCCESSFUL.USER@EXAMPLE.COM',
            'mfa_secret' => 'JBSWY3DPEHPK3PXP',
            'mfa_enabled' => true,
            'mfa_recovery_codes' => [Hash::make('valid-recovery-code')],
        ]);
        UserProfile::where('user_id', $user->id)->update(['position' => 'admin']);

        $ip = '127.0.0.1';
        $key = "successful.user@example.com|{$ip}";
        RateLimiter::clear($key);

        $this->loginAttempt('SUCCESSFUL.USER@EXAMPLE.COM', 'wrong-password', $ip, '')->assertHasFormErrors(['email']);
        $this->assertGreaterThan(0, RateLimiter::attempts($key));

        $this->loginAttempt('SUCCESSFUL.USER@EXAMPLE.COM', 'correct-password', $ip, 'valid-recovery-code');

        $this->assertSame(0, RateLimiter::attempts($key));
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function assertRapidSubmissionsPersisted(array $payload, string $model, string $column, string $value): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $response = $this->postJson(route('submit_quote'), $payload);

            $response->assertOk()->assertJsonPath('status', 'success');
        }

        $this->assertSame(6, $model::where($column, $value)->count());
    }

    /**
     * @return array<int, string>
     */
    private function routeMiddleware(string $routeName): array
    {
        $route = Route::getRoutes()->getByName($routeName);

        $this->assertNotNull($route);

        $middleware = $route->getAction('middleware') ?? [];

        return is_array($middleware) ? $middleware : [$middleware];
    }

    private function loginAttempt(string $email, string $password, string $ip, string $mfaCode = '')
    {
        Filament::setCurrentPanel('admin');

        $component = Livewire::withHeaders(['REMOTE_ADDR' => $ip])
            ->test(Login::class)
            ->fillForm([
                'role' => 'admin',
                'email' => $email,
                'password' => $password,
                'remember' => false,
                'mfa_code' => $mfaCode,
            ]);

        $this->assertSame($email, $component->get('data.email'));

        return $component->call('authenticate');
    }
}
