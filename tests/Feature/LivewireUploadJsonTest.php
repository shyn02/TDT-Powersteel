<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Layer B (upload HTML trap) regression guard.
 *
 * Root cause: bootstrap/app.php `shouldRenderJsonWhen(fn => $request->is('api/*'))`
 * replaces Laravel's default expectsJson() check, so Livewire upload/update
 * failures (419/401/422) render text/html despite Accept: application/json + XHR.
 * Livewire's client then throws `Unexpected token '<'` on JSON.parse and the
 * Create button sticks on "Uploading".
 *
 * Fix: extend shouldRenderJsonWhen to also return JSON for Livewire
 * upload/update routes (path + route-name + expectsJson fallback).
 *
 * NOTE on statuses: live (browser) an unsigned POST fails CSRF first (419)
 * because a session exists; under phpunit CSRF is bypassed by
 * PreventRequestForgery::runningUnitTests(), so the same unsigned POST reaches
 * the controller and fails the signature check (401). Both MUST render JSON.
 *
 * NOTE on assertions: plain PHPUnit assertions are used instead of
 * TestResponse::assertStatus() because the latter's failure-context injector
 * crashes on redirect responses, masking the red/green signal.
 */
class LivewireUploadJsonTest extends TestCase
{
    /**
     * The effective max upload size (in KB) enforced at Livewire's temporary
     * upload endpoint, read from config so this test tracks it automatically.
     */
    private function configuredMaxKilobytes(): int
    {
        $rules = (array) config('livewire.temporary_file_upload.rules', []);

        foreach ($rules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'max:')) {
                return (int) substr($rule, 4);
            }
        }

        // Livewire's built-in default when no rules are configured.
        return 12288;
    }

    public function test_unsigned_upload_failure_returns_json_not_html(): void
    {
        // Unsigned POST: Livewire's JS uploads with Accept: application/json +
        // XHR, so the failure MUST render JSON — otherwise JSON.parse('<...')
        // blows up and the button sticks on "Uploading".
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post(route('livewire.upload-file'));

        $this->assertSame(
            401,
            $response->getStatusCode(),
            'Unsigned livewire upload should fail the signature check (401); got: '
                .substr((string) $response->getContent(), 0, 200)
        );
        $this->assertStringContainsString(
            'application/json',
            $response->headers->get('Content-Type', ''),
            'Unsigned livewire upload failure must render JSON, not text/html.'
        );
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload, 'Unsigned upload failure body must be JSON.');
        $this->assertArrayHasKey('message', $payload);
    }

    public function test_upload_validation_failure_returns_json_not_redirect(): void
    {
        // Signed URL (passes hasValidSignature) but an oversized file so the
        // controller's Validator throws ValidationException -> must be 422
        // JSON, not a 302 redirect/HTML page.
        $signedUrl = URL::temporarySignedRoute(
            'livewire.upload-file', now()->addMinutes(5)
        );

        // Derive the oversized fixture from the configured ceiling instead of
        // hardcoding a value. A hardcoded 12289 KB was sized to the old
        // max:12288 default; once the ceiling was raised to 50MB for video
        // uploads that file PASSED validation and fell through into the
        // controller's store path, which 500s on the unfaked tmp-for-tests
        // disk. Going one KB over whatever the config says keeps this test
        // asserting the 422 contract regardless of the configured limit.
        $maxKilobytes = $this->configuredMaxKilobytes();
        $oversized = UploadedFile::fake()->image('big.jpg')->size($maxKilobytes + 1);

        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post($signedUrl, ['files' => [$oversized]]);

        $this->assertSame(
            422,
            $response->getStatusCode(),
            'Oversized livewire upload must fail validation (422); got: '
                .substr((string) $response->getContent(), 0, 200)
        );
        $this->assertStringContainsString(
            'application/json',
            $response->headers->get('Content-Type', ''),
            'Livewire upload validation failure must render 422 JSON, not a 302 redirect/HTML.'
        );
        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload, 'Validation failure body must be JSON.');
        $this->assertArrayHasKey('errors', $payload);
        $this->assertArrayHasKey('files.0', $payload['errors']);
    }
}
