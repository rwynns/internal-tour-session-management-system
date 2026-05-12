<?php

use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Successful Cancellation (Requirements 5.4) ───────────────────────────────

// Requirement 5.4: successful cancellation sets status to 'cancelled' and decrements current_pax
test('successful cancellation sets allocation status to cancelled', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->withOccupancy(3)->create(['max_capacity' => 20]);
    $allocation = GuestAllocation::factory()->create([
        'session_id' => $session->id,
        'pax' => 3,
        'status' => 'active',
        'allocated_by' => $cashier->id,
    ]);

    $this->actingAs($cashier)->patch(route('allocations.cancel', $allocation));

    expect($allocation->fresh()->status)->toBe('cancelled');
});

// Requirement 5.4: successful cancellation decrements session current_pax by allocation pax
test('successful cancellation decrements session current_pax by allocation pax', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->withOccupancy(5)->create(['max_capacity' => 20]);
    $allocation = GuestAllocation::factory()->create([
        'session_id' => $session->id,
        'pax' => 3,
        'status' => 'active',
        'allocated_by' => $cashier->id,
    ]);

    $this->actingAs($cashier)->patch(route('allocations.cancel', $allocation));

    expect($session->fresh()->current_pax)->toBe(2);
});

// Requirement 5.4: successful cancellation redirects back
test('successful cancellation returns a redirect response', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->withOccupancy(2)->create(['max_capacity' => 20]);
    $allocation = GuestAllocation::factory()->create([
        'session_id' => $session->id,
        'pax' => 2,
        'status' => 'active',
        'allocated_by' => $cashier->id,
    ]);

    $response = $this->actingAs($cashier)->patch(route('allocations.cancel', $allocation));

    $response->assertRedirect();
});

// ─── Already-Cancelled Rejection (Requirement 5.6) ───────────────────────────

// Requirement 5.6: cancelling an already-cancelled allocation returns an error
test('cancelling already-cancelled allocation returns validation error', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->create(['max_capacity' => 20]);
    $allocation = GuestAllocation::factory()->cancelled()->create([
        'session_id' => $session->id,
        'allocated_by' => $cashier->id,
    ]);

    $response = $this->actingAs($cashier)->patch(route('allocations.cancel', $allocation));

    $response->assertSessionHasErrors('allocation');
});

// Requirement 5.6: cancelling already-cancelled allocation does not change session current_pax
test('cancelling already-cancelled allocation does not change session current_pax', function () {
    $cashier = User::factory()->cashier()->create();
    $session = Session::factory()->withOccupancy(4)->create(['max_capacity' => 20]);
    $allocation = GuestAllocation::factory()->cancelled()->create([
        'session_id' => $session->id,
        'pax' => 2,
        'allocated_by' => $cashier->id,
    ]);

    $this->actingAs($cashier)->patch(route('allocations.cancel', $allocation));

    expect($session->fresh()->current_pax)->toBe(4);
});

// ─── Authentication & Role Guards (Requirement 10.4) ─────────────────────────

// Requirement 10.4: unauthenticated user is redirected when accessing cancel endpoint
test('unauthenticated user is redirected when accessing cancel endpoint', function () {
    $session = Session::factory()->create();
    $allocation = GuestAllocation::factory()->create(['session_id' => $session->id]);

    $response = $this->patch(route('allocations.cancel', $allocation));

    $response->assertRedirect(route('login'));
});

// Requirement 10.4: non-cashier user receives 403 on cancel endpoint
test('non-cashier user receives 403 on cancel endpoint', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $session = Session::factory()->create();
    $allocation = GuestAllocation::factory()->create(['session_id' => $session->id]);

    $response = $this->actingAs($admin)->patch(route('allocations.cancel', $allocation));

    $response->assertForbidden();
});

// Requirement 10.4: unauthenticated user is redirected when accessing store endpoint
test('unauthenticated user is redirected when accessing store endpoint', function () {
    $session = Session::factory()->create();

    $response = $this->post(route('allocations.store', $session), [
        'guest_name' => 'Test Guest',
        'pax' => 1,
    ]);

    $response->assertRedirect(route('login'));
});

// Requirement 10.4: non-cashier user receives 403 on store endpoint
test('non-cashier user receives 403 on store endpoint', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $session = Session::factory()->create();

    $response = $this->actingAs($admin)->post(route('allocations.store', $session), [
        'guest_name' => 'Test Guest',
        'pax' => 1,
    ]);

    $response->assertForbidden();
});

// Requirement 10.4: unauthenticated user is redirected when accessing move endpoint
test('unauthenticated user is redirected when accessing move endpoint', function () {
    $session = Session::factory()->create();
    $allocation = GuestAllocation::factory()->create(['session_id' => $session->id]);

    $response = $this->patch(route('allocations.move', $allocation), [
        'target_session_id' => $session->id,
    ]);

    $response->assertRedirect(route('login'));
});

// Requirement 10.4: non-cashier user receives 403 on move endpoint
test('non-cashier user receives 403 on move endpoint', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $session = Session::factory()->create();
    $allocation = GuestAllocation::factory()->create(['session_id' => $session->id]);

    $response = $this->actingAs($admin)->patch(route('allocations.move', $allocation), [
        'target_session_id' => $session->id,
    ]);

    $response->assertForbidden();
});
