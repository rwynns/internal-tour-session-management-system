<?php

use App\Models\Session;
use App\Models\User;

// Requirement 1.1: recreation_admin can access session index
test('recreation_admin can access session index', function () {
    $user = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($user)->get(route('sessions.index'));

    $response->assertOk();
});

// Requirement 1.1: recreation_admin can access session create form
test('recreation_admin can access session create form', function () {
    $user = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($user)->get(route('sessions.create'));

    $response->assertOk();
});

// Requirement 1.1: recreation_admin can access session edit form
test('recreation_admin can access session edit form', function () {
    $user = User::factory()->recreationAdmin()->create();
    $session = Session::factory()->create();

    $response = $this->actingAs($user)->get(route('sessions.edit', $session));

    $response->assertOk();
});

// Requirement 1.2: cashier receives 403 on session index
test('cashier receives 403 on session index', function () {
    $user = User::factory()->cashier()->create();

    $response = $this->actingAs($user)->get(route('sessions.index'));

    $response->assertForbidden();
});

// Requirement 1.2: cashier receives 403 on session create form
test('cashier receives 403 on session create form', function () {
    $user = User::factory()->cashier()->create();

    $response = $this->actingAs($user)->get(route('sessions.create'));

    $response->assertForbidden();
});

// Requirement 1.2: cashier receives 403 on session edit form
test('cashier receives 403 on session edit form', function () {
    $user = User::factory()->cashier()->create();
    $session = Session::factory()->create();

    $response = $this->actingAs($user)->get(route('sessions.edit', $session));

    $response->assertForbidden();
});

// Requirement 1.3: unauthenticated user is redirected to login on session index
test('unauthenticated user is redirected to login when accessing sessions', function () {
    $response = $this->get(route('sessions.index'));

    $response->assertRedirect(route('login'));
});
