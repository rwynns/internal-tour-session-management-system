<?php

use App\Enums\SessionStatus;
use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function cashierUser(): User
{
    return User::factory()->cashier()->create();
}

function activeSession(array $overrides = []): Session
{
    return Session::factory()->create(array_merge([
        'status' => SessionStatus::Active,
        'start_time' => now()->addHour(),
        'end_time' => now()->addHours(2),
        'max_capacity' => 20,
        'current_pax' => 0,
    ], $overrides));
}

function allocationFor(Session $session, int $pax = 2): GuestAllocation
{
    return GuestAllocation::factory()->create([
        'session_id' => $session->id,
        'pax' => $pax,
        'status' => 'active',
    ]);
}

// ─── Requirement 6.4: Successful move ────────────────────────────────────────

// Requirement 6.4: successful move updates session_id, decrements source pax, increments target pax
test('successful move updates session_id and transfers pax between sessions', function () {
    $cashier = cashierUser();
    $sourceSession = activeSession(['current_pax' => 2]);
    $targetSession = activeSession(['current_pax' => 3]);
    $allocation = allocationFor($sourceSession, 2);

    $response = $this->actingAs($cashier)->patch(
        route('allocations.move', $allocation),
        ['target_session_id' => $targetSession->id]
    );

    $response->assertRedirect();

    expect($allocation->fresh()->session_id)->toBe($targetSession->id);

    $this->assertDatabaseHas('tour_sessions', [
        'id' => $sourceSession->id,
        'current_pax' => 0,
    ]);

    $this->assertDatabaseHas('tour_sessions', [
        'id' => $targetSession->id,
        'current_pax' => 5,
    ]);
});

// ─── Requirement 6.6 / 7.4: Capacity validation ──────────────────────────────

// Requirement 6.6, 7.4: move to session with insufficient capacity is rejected
test('move to session with insufficient capacity is rejected', function () {
    $cashier = cashierUser();
    $sourceSession = activeSession(['current_pax' => 3]);
    $targetSession = activeSession(['max_capacity' => 5, 'current_pax' => 5]);
    $allocation = allocationFor($sourceSession, 2);

    $response = $this->actingAs($cashier)->patch(
        route('allocations.move', $allocation),
        ['target_session_id' => $targetSession->id]
    );

    $response->assertRedirect();
    $response->assertSessionHasErrors('target_session_id');

    // Allocation and pax values must remain unchanged
    expect($allocation->fresh()->session_id)->toBe($sourceSession->id);
    $this->assertDatabaseHas('tour_sessions', ['id' => $sourceSession->id, 'current_pax' => 3]);
    $this->assertDatabaseHas('tour_sessions', ['id' => $targetSession->id, 'current_pax' => 5]);
});

// ─── Requirement 8.4: Inactive session restriction ───────────────────────────

// Requirement 8.4: move to inactive session is rejected
test('move to inactive session is rejected', function () {
    $cashier = cashierUser();
    $sourceSession = activeSession(['current_pax' => 2]);
    $targetSession = activeSession(['status' => SessionStatus::Inactive]);
    $allocation = allocationFor($sourceSession, 2);

    $response = $this->actingAs($cashier)->patch(
        route('allocations.move', $allocation),
        ['target_session_id' => $targetSession->id]
    );

    $response->assertRedirect();
    $response->assertSessionHasErrors('target_session_id');

    expect($allocation->fresh()->session_id)->toBe($sourceSession->id);
    $this->assertDatabaseHas('tour_sessions', ['id' => $sourceSession->id, 'current_pax' => 2]);
});

// ─── Requirement 9.3: Past session restriction ───────────────────────────────

// Requirement 9.3: move to past session is rejected
test('move to past session is rejected', function () {
    $cashier = cashierUser();
    $sourceSession = activeSession(['current_pax' => 2]);
    $targetSession = activeSession(['start_time' => now()->subHour(), 'end_time' => now()->subMinutes(30)]);
    $allocation = allocationFor($sourceSession, 2);

    $response = $this->actingAs($cashier)->patch(
        route('allocations.move', $allocation),
        ['target_session_id' => $targetSession->id]
    );

    $response->assertRedirect();
    $response->assertSessionHasErrors('target_session_id');

    expect($allocation->fresh()->session_id)->toBe($sourceSession->id);
    $this->assertDatabaseHas('tour_sessions', ['id' => $sourceSession->id, 'current_pax' => 2]);
});

// ─── Requirement 6.3: Same session restriction ───────────────────────────────

// Requirement 6.3: move to same session is rejected
test('move to same session is rejected', function () {
    $cashier = cashierUser();
    $session = activeSession(['current_pax' => 2]);
    $allocation = allocationFor($session, 2);

    $response = $this->actingAs($cashier)->patch(
        route('allocations.move', $allocation),
        ['target_session_id' => $allocation->session_id]
    );

    $response->assertRedirect();
    $response->assertSessionHasErrors('target_session_id');

    expect($allocation->fresh()->session_id)->toBe($session->id);
    $this->assertDatabaseHas('tour_sessions', ['id' => $session->id, 'current_pax' => 2]);
});
