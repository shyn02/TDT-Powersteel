<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProxyTrustTest extends TestCase
{
    public function test_loopback_proxy_can_supply_https_scheme_but_not_override_request_host(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Host' => 'attacker.example',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('http://work-gadia.dev-x.site/admin/api/nav-counts');

        $response->assertRedirect('https://work-gadia.dev-x.site/login');
    }

    public function test_forwarded_headers_from_non_loopback_client_are_ignored(): void
    {
        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withHeaders([
                'X-Forwarded-Host' => 'attacker.example',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('http://work-gadia.dev-x.site/admin/api/nav-counts');

        $response->assertRedirect('http://work-gadia.dev-x.site/login');
    }
}
