<?php

use App\Models\User;

// Requirement 3.2: Valid credentials redirect to /dashboard
test('valid credentials redirect to dashboard', function () {
    $user = User::factory()->recreationAdmin()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

// Requirement 3.2: Cashier role also redirects to dashboard on valid login
test('cashier with valid credentials redirects to dashboard', function () {
    $user = User::factory()->cashier()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

// Requirement 3.3: Invalid credentials return a generic error without field disclosure
test('invalid credentials return a generic error without revealing which field is wrong', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);

    // The error message must not disclose whether the email or password was wrong
    $errors = session('errors');
    $emailError = $errors->first('email');
    expect($emailError)->not->toContain('password');
    expect($emailError)->not->toContain('email address');
});

// Requirement 3.6: Empty email field returns validation error
test('empty email field returns a validation error', function () {
    $response = $this->post(route('login.store'), [
        'email' => '',
        'password' => 'password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email']);
});

// Requirement 3.6: Empty password field returns validation error
test('empty password field returns a validation error', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => '',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['password']);
});

// Requirement 3.6: Both fields empty returns validation errors for both
test('empty email and password fields return validation errors without attempting authentication', function () {
    $response = $this->post(route('login.store'), [
        'email' => '',
        'password' => '',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors(['email', 'password']);
});

// Requirement 3.4: Unauthenticated access to /dashboard redirects to /login
test('unauthenticated access to dashboard redirects to login', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

// Requirement 3.5: Authenticated user visiting /login redirects to /dashboard
test('authenticated user visiting login page is redirected to dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('login'));

    $response->assertRedirect(route('dashboard', absolute: false));
});
