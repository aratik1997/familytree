<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_hardening_headers_are_sent(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'same-origin');
    }

    /**
     * Sending HSTS from a local http:// origin would pin localhost to HTTPS
     * in the developer's browser, which is tedious to undo.
     */
    public function test_hsts_is_not_sent_outside_production(): void
    {
        $this->get('/login')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_error_responses_are_hardened_too(): void
    {
        $this->get('/no-such-page-exists')
            ->assertNotFound()
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
