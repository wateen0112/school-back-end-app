<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    #[Test]
    public function unauthorized_test_parent_endpoint_has_been_removed()
    {
        $this->postJson('/api/test-parent', ['email' => 'hacker@test.com'])
            ->assertStatus(404);
    }

    #[Test]
    public function unauthenticated_write_route_returns_json_envelope_401()
    {
        $this->postJson('/api/grades', ['Name' => 'Hacked'])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    #[Test]
    public function unauthenticated_read_route_returns_json_envelope_401()
    {
        $this->getJson('/api/grades')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    #[Test]
    public function unauthenticated_api_call_without_json_accept_header_still_returns_json_401()
    {
        // No Accept: application/json here - API calls must never get a web redirect.
        $this->post('/api/grades', ['Name' => 'Hacked'])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    #[Test]
    public function public_info_endpoint_is_open_and_enveloped()
    {
        $this->getJson('/api/public/info')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    #[Test]
    public function login_is_rate_limited_after_five_attempts()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'nobody@test.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        $this->postJson('/api/login', [
            'email' => 'nobody@test.com',
            'password' => 'wrong-password',
        ])->assertStatus(429)
            ->assertJsonPath('success', false);
    }

    #[Test]
    public function refresh_rotates_old_token_and_issues_new_one()
    {
        $user = User::factory()->create();
        $oldToken = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($oldToken)
            ->postJson('/api/refresh');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['access_token']);

        $newToken = $response->json('access_token');
        $this->assertNotEquals($oldToken, $newToken);

        // The old token must no longer authenticate.
        $this->withToken($oldToken)
            ->postJson('/api/me')
            ->assertStatus(401);

        // The new token works.
        $this->withToken($newToken)
            ->postJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    #[Test]
    public function logout_revokes_only_the_current_token()
    {
        $user = User::factory()->create();
        $tokenA = $user->createToken('device-a')->plainTextToken;
        $tokenB = $user->createToken('device-b')->plainTextToken;

        $this->withToken($tokenA)->postJson('/api/logout')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Revoked token is now rejected...
        $this->withToken($tokenA)->postJson('/api/me')->assertStatus(401);

        // ...but the other device's token still works.
        $this->withToken($tokenB)->postJson('/api/me')
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}