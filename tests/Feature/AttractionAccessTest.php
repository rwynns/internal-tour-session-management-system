<?php

use App\Models\Attraction;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Requirement 1.1: recreation_admin can access all attraction routes
test('recreation_admin can list attractions', function () {
    $user = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($user)->get(route('attractions.index'));

    $response->assertOk();
});

test('recreation_admin can access the create attraction page', function () {
    $user = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($user)->get(route('attractions.create'));

    $response->assertOk();
});

test('recreation_admin can access the edit attraction page', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();

    $response = $this->actingAs($user)->get(route('attractions.edit', $attraction));

    $response->assertOk();
});

// Requirement 1.2: cashier receives 403 on all attraction routes
test('cashier receives 403 on attraction index', function () {
    $user = User::factory()->cashier()->create();

    $response = $this->actingAs($user)->get(route('attractions.index'));

    $response->assertForbidden();
});

test('cashier receives 403 on attraction create page', function () {
    $user = User::factory()->cashier()->create();

    $response = $this->actingAs($user)->get(route('attractions.create'));

    $response->assertForbidden();
});

test('cashier receives 403 on attraction edit page', function () {
    $user = User::factory()->cashier()->create();
    $attraction = Attraction::factory()->create();

    $response = $this->actingAs($user)->get(route('attractions.edit', $attraction));

    $response->assertForbidden();
});

// Requirement 1.3: unauthenticated user is redirected to login
test('unauthenticated user is redirected to login when accessing attractions', function () {
    $response = $this->get(route('attractions.index'));

    $response->assertRedirect(route('login'));
});
