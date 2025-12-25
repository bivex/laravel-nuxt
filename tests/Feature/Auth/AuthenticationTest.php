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
 * Last Updated: 2025-12-25T11:37:27
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
                fn (AssertableJson $json) => $json
                    ->hasAll(['ok', 'token'])
            );
        } else {
            $response->assertJson(
                fn (AssertableJson $json) => $json
                    ->has('ok')
                    ->where('ok', true)
                    ->missing('token')
            );
        }
    }

    public function test_spa_login_should_persist_session_for_authenticated_requests(): void
    {
        // This test verifies that SPA authentication session persistence now works
        // The login/register routes now have 'web' middleware to enable session-based auth
        // Sessions should persist after login for subsequent authenticated requests

        $user = User::factory()->create();

        // Login via SPA endpoint - should now set session due to 'web' middleware
        $loginResponse = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJson(['ok' => true]);

        // Now session should be maintained for subsequent requests
        // This should work because login route now has 'web' middleware
        $this->assertAuthenticated('web'); // Session-based auth should now work
    }

    public function test_spa_register_should_persist_session_for_authenticated_requests(): void
    {
        // Test that registration now has session persistence working
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        // Register via SPA endpoint - should now set session due to 'web' middleware
        $registerResponse = $this->postJson('/api/v1/register', $userData);

        $registerResponse->assertStatus(201)
            ->assertJson(['ok' => true]);

        // Session should now be maintained after registration
        // This should work because register route now has 'web' middleware
        $this->assertAuthenticated('web'); // Session-based auth should now work
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

    public function test_oauth_redirect_has_web_middleware_for_session_persistence(): void
    {
        // Issue #31: OAuth redirect should have 'web' middleware to maintain session
        // between redirect to provider and callback from provider

        // Test that the redirect route has web middleware for session persistence
        $routes = app('router')->getRoutes();
        $redirectRoute = null;

        foreach ($routes as $route) {
            if ($route->getName() === 'login.provider.redirect') {
                $redirectRoute = $route;
                break;
            }
        }

        $this->assertNotNull($redirectRoute, 'OAuth redirect route should exist');
        $this->assertContains('web', $redirectRoute->middleware(), 'OAuth redirect should have web middleware for session persistence');
    }

    public function test_session_based_logout_clears_server_session(): void
    {
        // Issue #31: SSR logout should clear session on server side
        // Currently, logout only clears client-side cookies but server-side
        // session remains, causing SSR to think user is still authenticated

        $user = User::factory()->create();

        // Simulate session-based login
        $this->actingAs($user, 'web');

        // Verify user is authenticated
        $this->assertAuthenticated('web');

        // Perform logout
        $response = $this->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson(['ok' => true]);

        // After logout, session should be cleared
        $this->assertGuest('web');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertJson(
            fn (AssertableJson $json) => $json
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
