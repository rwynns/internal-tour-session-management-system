<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Requirement 11.1: recreation_admin sees Attractions and Sessions nav items
test('recreation_admin has role recreation_admin in shared Inertia props', function () {
    $user = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.role', 'recreation_admin')
    );
});

// Requirement 11.2: cashier does NOT see Attractions and Sessions nav items
test('cashier has role cashier in shared Inertia props', function () {
    $user = User::factory()->cashier()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('auth.user.role', 'cashier')
    );
});
