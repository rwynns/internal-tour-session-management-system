<?php

use App\Models\User;

/**
 * Logout Feature Tests
 *
 * Validates: Requirements 4.1, 4.2, 4.3, 4.4
 */

test('logout invalidates the session and redirects to home', function () {
    // Requirement 4.2: logout invalidates session and redirects
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('after logout accessing dashboard redirects to login', function () {
    // Requirement 4.3: user cannot access protected routes using the previous session
    $user = User::factory()->create();

    // Log out first
    $this->actingAs($user)->post(route('logout'));

    // Now attempt to access the dashboard without authentication
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('unauthenticated post to logout redirects to login', function () {
    // Requirement 4.4: unauthenticated logout attempt redirects to login
    $response = $this->post(route('logout'));

    $response->assertRedirect(route('login'));
});
