<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_xss_payloads_are_rejected_before_they_are_stored(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->postJson('/reservas', [
            'source'=>'qr',
            'full_name'=>'Ana <script>alert(1)</script>',
            'nationality'=>'Bolivia',
            'passport_number'=>'XSS-001',
            'whatsapp'=>'+591 700 00000',
        ])->assertUnprocessable()->assertJsonValidationErrors('full_name');

        $this->assertDatabaseMissing('tourists', ['passport_number'=>'XSS-001']);
    }

    public function test_encoded_xss_and_malicious_query_strings_are_rejected(): void
    {
        $this->postJson('/reservas', [
            'source'=>'qr',
            'full_name'=>'Ana &lt;img src=x onerror=alert(1)&gt;',
            'nationality'=>'Bolivia',
            'passport_number'=>'XSS-002',
            'whatsapp'=>'+591 700 00000',
        ])->assertUnprocessable()->assertJsonValidationErrors('full_name');

        $this->get('/reservar?package=%3Cscript%3E')
            ->assertRedirect('/ingresar')
            ->assertSessionHasErrors('email');
    }

    public function test_unknown_web_urls_return_to_login_while_unknown_api_urls_stay_json(): void
    {
        $this->get('/panel/administracion/secreta')->assertRedirect('/ingresar');
        $this->getJson('/api/v1/private/unknown')
            ->assertNotFound()
            ->assertJsonPath('ok', false);
    }

    public function test_security_headers_and_request_trace_id_are_added(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('X-Request-ID');
    }

    public function test_login_is_blocked_after_three_failures_and_each_cycle_adds_ten_seconds(): void
    {
        $this->seed(DatabaseSeeder::class);
        $credentials = ['email'=>'maria@incaland.bo', 'password'=>'incorrecta'];

        $this->post('/ingresar', $credentials)->assertSessionHasErrors('email');
        $this->post('/ingresar', $credentials)->assertSessionHasErrors('email');
        $this->post('/ingresar', $credentials)
            ->assertSessionHasErrors('email')
            ->assertSessionHas('retry_after', 10);

        $this->post('/ingresar', ['email'=>'maria@incaland.bo', 'password'=>'maria1234'])
            ->assertSessionHas('retry_after');

        $this->travel(11)->seconds();
        $this->post('/ingresar', $credentials);
        $this->post('/ingresar', $credentials);
        $this->post('/ingresar', $credentials)
            ->assertSessionHas('retry_after', 20);

        $this->travel(21)->seconds();
        $this->post('/ingresar', ['email'=>'maria@incaland.bo', 'password'=>'maria1234'])
            ->assertRedirect('/panel');
    }

    public function test_api_login_returns_429_and_retry_after_on_the_third_failure(): void
    {
        $this->seed(DatabaseSeeder::class);
        $credentials = ['email'=>'daniel@incaland.bo', 'password'=>'incorrecta'];

        $this->postJson('/api/v1/auth/login', $credentials)->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', $credentials)->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', $credentials)
            ->assertStatus(429)
            ->assertHeader('Retry-After', '10')
            ->assertJsonPath('retry_after', 10);
    }
}
