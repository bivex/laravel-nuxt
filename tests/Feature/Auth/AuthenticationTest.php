<?php
/**
 * Copyright (c) 2025 Bivex
 *
 * Author: Bivex
 * Available for contact via email: support@b-b.top
 * For up-to-date contact information:
 * https://github.com/bivex
 *
 * Created: 2025-12-25T11:27:34
 * Last Updated: 2025-12-25T11:27:58
 *
 * Licensed under the MIT License.
 * Commercial licensing available upon request.
 */

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

        // Login via SPA endpoint - this should set session but currently doesn't due to missing 'web' middleware
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson(['ok' => true]);

        // The issue: subsequent requests should maintain session, but they don't
        // because login/register routes lack 'web' middleware
        // This demonstrates the problem - session is not persisted
        $this->assertGuest('web'); // Session-based auth should work but doesn't
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

        // Register via SPA endpoint - this should set session but currently doesn't due to missing 'web' middleware
        $registerResponse = $this->postJson('/api/v1/register', $userData);

        $registerResponse->assertStatus(201)
            ->assertJson(['ok' => true]);

        // The issue: session should be maintained after registration, but it's not
        // because register route lacks 'web' middleware
        // This demonstrates the problem - session is not persisted
        $this->assertGuest('web'); // Session-based auth should work but doesn't
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
