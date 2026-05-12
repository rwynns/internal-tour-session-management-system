<?php

use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Property 8: Invalid allocation input rejection
 *
 * Validates: Requirements 4.7
 *
 * For any allocation submission where guest_name is empty/whitespace-only OR
 * pax < 1 OR guest_name exceeds 255 characters OR source exceeds 100 characters
 * OR notes exceeds 1000 characters, the system SHALL reject the submission with
 * validation errors and create no GuestAllocation record.
 */
it(
    'rejects invalid allocation inputs and creates no GuestAllocation record',
    /**
     * **Validates: Requirements 4.7**
     *
     * Feature: cashier-session-dashboard
     * Property 8: Invalid allocation input rejection
     */
    function (): void {
        $cashier = User::factory()->cashier()->create();
        $this->actingAs($cashier);

        for ($i = 0; $i < 100; $i++) {
            DB::beginTransaction();

            try {
                // Create a valid active session with plenty of capacity
                $session = Session::factory()->create([
                    'max_capacity' => 20,
                    'current_pax' => 0,
                    'start_time' => now()->addDay(),
                    'end_time' => now()->addDay()->addHours(2),
                ]);

                $initialPax = $session->current_pax;

                // Randomly pick one of the 7 invalid scenarios
                $scenario = rand(1, 7);

                $payload = match ($scenario) {
                    // Scenario 1: empty guest_name
                    1 => [
                        'guest_name' => '',
                        'pax' => rand(1, 5),
                        'source' => 'walk-in',
                        'notes' => null,
                    ],
                    // Scenario 2: whitespace-only guest_name
                    2 => [
                        'guest_name' => str_repeat(' ', rand(1, 10)),
                        'pax' => rand(1, 5),
                        'source' => 'walk-in',
                        'notes' => null,
                    ],
                    // Scenario 3: oversized guest_name (> 255 chars)
                    3 => [
                        'guest_name' => str_repeat('a', rand(256, 300)),
                        'pax' => rand(1, 5),
                        'source' => 'walk-in',
                        'notes' => null,
                    ],
                    // Scenario 4: pax = 0
                    4 => [
                        'guest_name' => 'Valid Guest',
                        'pax' => 0,
                        'source' => 'walk-in',
                        'notes' => null,
                    ],
                    // Scenario 5: negative pax
                    5 => [
                        'guest_name' => 'Valid Guest',
                        'pax' => -rand(1, 100),
                        'source' => 'walk-in',
                        'notes' => null,
                    ],
                    // Scenario 6: oversized source (> 100 chars)
                    6 => [
                        'guest_name' => 'Valid Guest',
                        'pax' => rand(1, 5),
                        'source' => str_repeat('s', rand(101, 150)),
                        'notes' => null,
                    ],
                    // Scenario 7: oversized notes (> 1000 chars)
                    7 => [
                        'guest_name' => 'Valid Guest',
                        'pax' => rand(1, 5),
                        'source' => 'walk-in',
                        'notes' => str_repeat('n', rand(1001, 1500)),
                    ],
                };

                $response = $this->post(route('allocations.store', $session), $payload);

                // The submission must be rejected with validation errors
                $response->assertSessionHasErrors();

                // No GuestAllocation record should have been created for this session
                expect(GuestAllocation::where('session_id', $session->id)->count())
                    ->toBe(0, "Scenario {$scenario}: expected no GuestAllocation to be created");

                // Session current_pax must remain unchanged
                expect($session->fresh()->current_pax)
                    ->toBe($initialPax, "Scenario {$scenario}: expected current_pax to remain {$initialPax}");
            } finally {
                DB::rollBack();
            }
        }
    }
)->group('Feature: cashier-session-dashboard', 'Property 8: Invalid allocation input rejection');
