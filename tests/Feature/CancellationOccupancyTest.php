<?php

use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Property 2: Cancellation decrements occupancy correctly
 *
 * Validates: Requirements 5.4
 *
 * For any active GuestAllocation with pax P belonging to a session with
 * current_pax C, cancelling that allocation SHALL result in the allocation's
 * status becoming "cancelled" and the session's current_pax decreasing by
 * exactly P (new current_pax = C - P).
 */
test(
    'cancellation decrements occupancy correctly',
    /**
     * **Validates: Requirements 5.4**
     *
     * Feature: cashier-session-dashboard
     * Property 2: Cancellation decrements occupancy correctly
     */
    function (): void {
        $cashier = User::factory()->cashier()->create();
        $this->actingAs($cashier);

        for ($i = 0; $i < 100; $i++) {
            DB::beginTransaction();

            try {
                // Generate random pax P (1–10) and current_pax C (P–20, so C >= P)
                $pax = fake()->numberBetween(1, 10);
                $currentPax = fake()->numberBetween($pax, 20);

                $session = Session::factory()->withOccupancy($currentPax)->create([
                    'max_capacity' => 30,
                ]);

                $allocation = GuestAllocation::factory()->create([
                    'session_id' => $session->id,
                    'pax' => $pax,
                    'status' => 'active',
                    'allocated_by' => $cashier->id,
                ]);

                $this->patch(route('allocations.cancel', $allocation));

                // Assert allocation status is 'cancelled'
                expect($allocation->fresh()->status)
                    ->toBe('cancelled', "Iteration {$i}: allocation status should be 'cancelled' after cancellation");

                // Assert session current_pax decreased by exactly P
                expect($session->fresh()->current_pax)
                    ->toBe($currentPax - $pax, "Iteration {$i}: current_pax should be {$currentPax} - {$pax} = ".($currentPax - $pax).' after cancellation');
            } finally {
                DB::rollBack();
            }
        }
    }
)->group('Feature: cashier-session-dashboard', 'Property 2: Cancellation decrements occupancy correctly');
