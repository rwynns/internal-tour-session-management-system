<?php

use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Property 1: Allocation creation increments occupancy correctly
 *
 * Validates: Requirements 4.4
 *
 * For any active session with remaining capacity R > 0, and for any valid pax
 * value P where 1 ≤ P ≤ R, creating a guest allocation with pax P SHALL result
 * in the session's current_pax increasing by exactly P and a new GuestAllocation
 * record existing with status "active".
 */

// Generate 100 iterations: each with random max_capacity M (5–20),
// random current_pax C (0 to M-1), and random pax P (1 to M-C).
$dataset = array_map(
    function () {
        $maxCapacity = fake()->numberBetween(5, 20);
        $currentPax = fake()->numberBetween(0, $maxCapacity - 1);
        $remaining = $maxCapacity - $currentPax;
        $pax = fake()->numberBetween(1, $remaining);

        return [$maxCapacity, $currentPax, $pax];
    },
    range(1, 100)
);

it(
    'allocation creation increments occupancy correctly',
    /**
     * **Validates: Requirements 4.4**
     *
     * Feature: cashier-session-dashboard
     * Property 1: Allocation creation increments occupancy correctly
     */
    function (int $maxCapacity, int $currentPax, int $pax) {
        $cashier = User::factory()->cashier()->create();

        DB::beginTransaction();

        try {
            $session = Session::factory()->create([
                'max_capacity' => $maxCapacity,
                'current_pax' => $currentPax,
                'start_time' => now()->addHour(),
            ]);

            $this->actingAs($cashier)
                ->post(route('allocations.store', $session), [
                    'guest_name' => fake()->name(),
                    'pax' => $pax,
                    'source' => 'walk-in',
                    'notes' => null,
                ])
                ->assertRedirect();

            // Property assertion 1: current_pax increased by exactly P
            expect($session->fresh()->current_pax)->toBe($currentPax + $pax);

            // Property assertion 2: a GuestAllocation record exists with status 'active' and correct pax
            $this->assertDatabaseHas('guest_allocations', [
                'session_id' => $session->id,
                'pax' => $pax,
                'status' => 'active',
            ]);
        } finally {
            DB::rollBack();
        }
    }
)->with($dataset)->group('Feature: cashier-session-dashboard', 'Property 1: Allocation creation increments occupancy correctly');
