<?php

use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Property 4: Capacity invariant — no operation exceeds max_capacity
 *
 * Validates: Requirements 4.6, 6.6, 7.1, 7.2
 *
 * For any session with max_capacity M, and for any allocation or move operation
 * targeting that session, if the operation would result in current_pax > M,
 * the system SHALL reject the operation without persisting any changes, and the
 * session's current_pax SHALL remain unchanged.
 */

// ─── Scenario A: Allocation exceeds capacity ──────────────────────────────────

// Generate 100 iterations: random M (5–15), C (1–M), P = M - C + rand(1, 5)
$allocationDataset = array_map(
    function () {
        $maxCapacity = fake()->numberBetween(5, 15);
        $currentPax = fake()->numberBetween(1, $maxCapacity);
        $remaining = $maxCapacity - $currentPax;
        // P is guaranteed to exceed remaining capacity
        $pax = $remaining + fake()->numberBetween(1, 5);

        return [$maxCapacity, $currentPax, $pax];
    },
    range(1, 100)
);

it(
    'rejects allocation that would exceed max_capacity and leaves current_pax unchanged',
    /**
     * **Validates: Requirements 4.6, 6.6, 7.1, 7.2**
     *
     * Feature: cashier-session-dashboard
     * Property 4: Capacity invariant — no operation exceeds max_capacity
     */
    function (int $maxCapacity, int $currentPax, int $pax) {
        DB::beginTransaction();

        try {
            $cashier = User::factory()->cashier()->create();
            $session = Session::factory()->create([
                'max_capacity' => $maxCapacity,
                'current_pax' => $currentPax,
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
            ]);

            $allocationCountBefore = GuestAllocation::count();

            $response = $this->actingAs($cashier)
                ->post(route('allocations.store', $session), [
                    'guest_name' => 'Test Guest',
                    'pax' => $pax,
                    'source' => 'walk-in',
                    'notes' => null,
                ]);

            // Must be rejected (redirect back with errors)
            $response->assertRedirect();
            $response->assertSessionHasErrors('pax');

            // current_pax must remain unchanged
            expect($session->fresh()->current_pax)->toBe($currentPax);

            // No new GuestAllocation must have been created
            expect(GuestAllocation::count())->toBe($allocationCountBefore);
        } finally {
            DB::rollBack();
        }
    }
)->with($allocationDataset)->group('Feature: cashier-session-dashboard', 'Property 4: Capacity invariant — no operation exceeds max_capacity');

// ─── Scenario B: Move exceeds target capacity ─────────────────────────────────

// Generate 100 iterations: random M (5–15), C (1–M), P = M - C + rand(1, 5)
$moveDataset = array_map(
    function () {
        $maxCapacity = fake()->numberBetween(5, 15);
        $currentPax = fake()->numberBetween(1, $maxCapacity);
        $remaining = $maxCapacity - $currentPax;
        // P is guaranteed to exceed target remaining capacity
        $pax = $remaining + fake()->numberBetween(1, 5);

        return [$maxCapacity, $currentPax, $pax];
    },
    range(1, 100)
);

it(
    'rejects move that would exceed target max_capacity and leaves both sessions unchanged',
    /**
     * **Validates: Requirements 4.6, 6.6, 7.1, 7.2**
     *
     * Feature: cashier-session-dashboard
     * Property 4: Capacity invariant — no operation exceeds max_capacity
     */
    function (int $maxCapacity, int $currentPax, int $pax) {
        DB::beginTransaction();

        try {
            $cashier = User::factory()->cashier()->create();

            // Source session has enough room for the allocation
            $sourceSession = Session::factory()->create([
                'max_capacity' => 20,
                'current_pax' => 2,
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
            ]);

            // Target session is at or near capacity — pax will exceed its remaining space
            $targetSession = Session::factory()->create([
                'max_capacity' => $maxCapacity,
                'current_pax' => $currentPax,
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
            ]);

            // Create an allocation on the source session with pax that exceeds target remaining
            $allocation = GuestAllocation::factory()->create([
                'session_id' => $sourceSession->id,
                'pax' => $pax,
                'status' => 'active',
            ]);

            $response = $this->actingAs($cashier)
                ->patch(route('allocations.move', $allocation), [
                    'target_session_id' => $targetSession->id,
                ]);

            // Must be rejected
            $response->assertRedirect();
            $response->assertSessionHasErrors('target_session_id');

            // Target current_pax must remain unchanged
            expect($targetSession->fresh()->current_pax)->toBe($currentPax);

            // Allocation must still belong to the source session
            expect($allocation->fresh()->session_id)->toBe($sourceSession->id);
        } finally {
            DB::rollBack();
        }
    }
)->with($moveDataset)->group('Feature: cashier-session-dashboard', 'Property 4: Capacity invariant — no operation exceeds max_capacity');
