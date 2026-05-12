<?php

use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Property 9: Cascade delete preserves referential integrity
 *
 * Validates: Requirements 1.2
 *
 * For any session with N associated GuestAllocation records (where N ≥ 0),
 * deleting that session SHALL result in exactly zero GuestAllocation records
 * referencing that session's ID remaining in the database.
 */
test(
    'cascade delete preserves referential integrity',
    /**
     * **Validates: Requirements 1.2**
     *
     * Feature: cashier-session-dashboard
     * Property 9: Cascade delete preserves referential integrity
     */
    function (): void {
        $user = User::factory()->create();

        for ($i = 0; $i < 100; $i++) {
            DB::beginTransaction();

            try {
                // Generate random N (0–10) allocations
                $n = fake()->numberBetween(0, 10);

                $session = Session::factory()->create([
                    'max_capacity' => 50,
                    'current_pax' => 0,
                ]);

                // Create N GuestAllocation records (mix of active and cancelled)
                GuestAllocation::factory()->count($n)->create([
                    'session_id' => $session->id,
                    'allocated_by' => $user->id,
                    'status' => fake()->randomElement(['active', 'cancelled']),
                ]);

                // Assert exactly N allocations exist for this session before delete
                expect(GuestAllocation::where('session_id', $session->id)->count())
                    ->toBe($n, "Iteration {$i}: expected {$n} allocations before delete");

                // Delete the session
                $sessionId = $session->id;
                $session->delete();

                // Assert zero allocations remain for that session_id
                expect(GuestAllocation::where('session_id', $sessionId)->count())
                    ->toBe(0, "Iteration {$i}: expected 0 allocations after cascade delete of session {$sessionId}");
            } finally {
                DB::rollBack();
            }
        }
    }
)->group('Feature: cashier-session-dashboard', 'Property 9: Cascade delete preserves referential integrity');
