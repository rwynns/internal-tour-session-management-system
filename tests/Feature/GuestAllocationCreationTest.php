<?php

use App\Enums\SessionStatus;
use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Helper ──────────────────────────────────────────────────────────────────

function allocationPayload(array $overrides = []): array
{
    return array_merge([
        'guest_name' => 'John Doe',
        'pax' => 2,
        'source' => 'walk-in',
        'notes' => null,
    ], $overrides);
}

// ─── Successful Allocation (Requirements 4.4) ─────────────────────────────────

// Requirement 4.4: successful allocation creates record and increments current_pax
test('successful allocation creates guest allocation record', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 10, 'current_pax' => 0]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload(['pax' => 3]));

    $response->assertRedirect();
    $this->assertDatabaseHas('guest_allocations', [
        'session_id' => $session->id,
        'guest_name' => 'John Doe',
        'pax' => 3,
        'source' => 'walk-in',
        'status' => 'active',
        'allocated_by' => $cashier->id,
    ]);
});

// Requirement 4.4: successful allocation increments session current_pax by pax value
test('successful allocation increments session current_pax by pax value', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 10, 'current_pax' => 2]);

    $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload(['pax' => 3]));

    expect($session->fresh()->current_pax)->toBe(5);
});

// ─── Validation — Field Rules (Requirements 4.7) ─────────────────────────────

// Requirement 4.7: empty guest_name is rejected with validation error
test('empty guest_name is rejected with validation error', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 10, 'current_pax' => 0]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload(['guest_name' => '']));

    $response->assertSessionHasErrors('guest_name');
    $this->assertDatabaseCount('guest_allocations', 0);
});

// Requirement 4.7: pax less than 1 is rejected with validation error
test('pax less than 1 is rejected with validation error', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 10, 'current_pax' => 0]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload(['pax' => 0]));

    $response->assertSessionHasErrors('pax');
    $this->assertDatabaseCount('guest_allocations', 0);
});

// Requirement 4.7: guest_name exceeding 255 characters is rejected
test('guest_name exceeding 255 characters is rejected', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 10, 'current_pax' => 0]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload([
            'guest_name' => str_repeat('a', 256),
        ]));

    $response->assertSessionHasErrors('guest_name');
    $this->assertDatabaseCount('guest_allocations', 0);
});

// Requirement 4.7: source exceeding 100 characters is rejected
test('source exceeding 100 characters is rejected', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 10, 'current_pax' => 0]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload([
            'source' => str_repeat('x', 101),
        ]));

    $response->assertSessionHasErrors('source');
    $this->assertDatabaseCount('guest_allocations', 0);
});

// Requirement 4.7: notes exceeding 1000 characters is rejected
test('notes exceeding 1000 characters is rejected', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 10, 'current_pax' => 0]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload([
            'notes' => str_repeat('n', 1001),
        ]));

    $response->assertSessionHasErrors('notes');
    $this->assertDatabaseCount('guest_allocations', 0);
});

// ─── Capacity Exceeded (Requirements 4.6, 7.1, 7.2) ─────────────────────────

// Requirement 4.6 / 7.1: allocation that would exceed max_capacity is rejected
test('allocation that would exceed max_capacity is rejected with capacity error', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 5, 'current_pax' => 5]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload(['pax' => 1]));

    $response->assertRedirect();
    $response->assertSessionHasErrors('pax');
    $this->assertDatabaseCount('guest_allocations', 0);
    expect($session->fresh()->current_pax)->toBe(5);
});

// Requirement 7.2: capacity error message includes requested pax, available, and max_capacity
test('capacity error message includes requested pax, available, and max_capacity', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 5, 'current_pax' => 4]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload(['pax' => 3]));

    $response->assertRedirect();
    $response->assertSessionHasErrors('pax');
    $errors = session('errors');
    expect($errors->first('pax'))->toContain('3')
        ->toContain('1')
        ->toContain('5');
});

// ─── Inactive Session Restriction (Requirements 8.3) ─────────────────────────

// Requirement 8.3: allocation to inactive session is rejected with validation error
test('allocation to inactive session is rejected', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->inactive()->create(['max_capacity' => 10, 'current_pax' => 0]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload());

    $response->assertRedirect();
    $response->assertSessionHasErrors('session');
    $this->assertDatabaseCount('guest_allocations', 0);
});

// ─── Past Session Restriction (Requirements 9.2) ─────────────────────────────

// Requirement 9.2: allocation to past session is rejected with validation error
test('allocation to past session is rejected', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create([
        'max_capacity' => 10,
        'current_pax' => 0,
        'start_time' => now()->subHour(),
        'end_time' => now()->subMinutes(30),
    ]);

    $response = $this->actingAs($cashier)
        ->post(route('allocations.store', $session), allocationPayload());

    $response->assertRedirect();
    $response->assertSessionHasErrors('session');
    $this->assertDatabaseCount('guest_allocations', 0);
});
