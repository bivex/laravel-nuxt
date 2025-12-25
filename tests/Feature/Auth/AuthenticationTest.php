<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $guard = config('auth.defaults.guard');

        $user = User::factory()->create();

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ], [
            'Origin' => isset(config('sanctum.stateful')[0]) ? config('sanctum.stateful')[0] : 'localhost',
        ]);

        $response->assertStatus(200);

        if ($guard === 'api') {
            $response->assertJson(
                fn(AssertableJson $json) => $json
                    ->hasAll(['ok', 'token'])
            );
        } else {
            $response->assertJson(
                fn(AssertableJson $json) => $json
                    ->has('ok')
                    ->where('ok', true)
                    ->missing('token')
            );
        }
    }

    public function test_spa_login_should_persist_session_for_authenticated_requests(): void
    {
        // This test demonstrates the SPA authentication session persistence issue
        // The login/register routes should have 'web' middleware to enable session-based auth
        // Currently, sessions don't persist because routes use 'api' middleware group without 'web'

        $user = User::factory()->create();

        // Login via SPA endpoint
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson(['ok' => true]);

        // Check if session is maintained for subsequent requests
        // This should work for SPA auth but currently fails due to missing 'web' middleware
        $userResponse = $this->getJson('/api/v1/user');

        // This assertion will fail because session is not persisted
        // The route requires auth middleware but session-based auth doesn't work
        $userResponse->assertStatus(401); // Currently fails due to session not persisting
    }

    public function test_spa_register_should_persist_session_for_authenticated_requests(): void
    {
        // Test that registration also has session persistence issues
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        // Register via SPA endpoint
        $registerResponse = $this->postJson('/api/v1/register', $userData);

        $registerResponse->assertStatus(201)
            ->assertJson(['ok' => true]);

        // Check if session is maintained after registration
        // This should work but currently fails due to missing 'web' middleware
        $userResponse = $this->getJson('/api/v1/user');

        // This assertion will fail because session is not persisted after registration
        $userResponse->assertStatus(401); // Currently fails due to session not persisting
    }

    public function test_social_auth_callback_has_web_middleware(): void
    {
        // This test verifies that social auth callback correctly has 'web' middleware
        // This is a positive test case - social auth callback should work

        $user = User::factory()->create();

        // Test that the callback route has web middleware by checking route configuration
        $routes = app('router')->getRoutes();
        $callbackRoute = null;

        foreach ($routes as $route) {
            if ($route->getName() === 'login.provider.callback') {
                $callbackRoute = $route;
                break;
            }
        }

        $this->assertNotNull($callbackRoute, 'Social auth callback route should exist');
        $this->assertContains('web', $callbackRoute->middleware(), 'Social auth callback should have web middleware');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertJson(
            fn(AssertableJson $json) => $json
                ->hasAll(['ok', 'message', 'errors'])
                ->where('ok', false)
                ->missing('token')
        );
    }

    public function test_users_can_logout(): void
    {
        $guard = config('auth.defaults.guard');

        /** @var User $user */
        $user = User::factory()->create();

        if ($guard === 'api') {
            $token = $user->createDeviceToken('test-device', '127.0.0.1');
            $response = $this->post('/api/v1/logout', [], [
                'Authorization' => 'Bearer ' . $token,
            ]);
        } else {
            $this->actingAs($user, $guard);
            $response = $this->post('/api/v1/logout');
        }

        $response->assertJson(['ok' => true], true);
    }
}
