<?php

use App\Enums\SessionStatus;
use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Property 6: Inactive session rejection
 *
 * Validates: Requirements 8.3, 8.4
 *
 * For any session with status "inactive", for any allocation request or move
 * request targeting that session, the system SHALL reject the request and return
 * a validation error, leaving all data unchanged.
 */

// ─── Dataset ─────────────────────────────────────────────────────────────────

// Generate 100 iterations with randomised initial current_pax values
$datasetProperty6 = array_map(
    function () {
        return [
            fake()->numberBetween(0, 10), // initial current_pax for inactive session
            fake()->numberBetween(1, 5),  // pax for the allocation request
        ];
    },
    range(1, 100)
);

// ─── Allocation to inactive session (Requirements 8.3) ───────────────────────

it(
    'rejects allocation request targeting an inactive session and leaves data unchanged',
    /**
     * **Validates: Requirements 8.3**
     *
     * Feature: cashier-session-dashboard
     * Property 6: Inactive session rejection
     */
    function (int $initialPax, int $requestedPax) {
        DB::beginTransaction();

        try {
            $cashier = User::factory()->cashier()->create();

            $inactiveSession = Session::factory()->inactive()->create([
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
                'max_capacity' => 20,
                'current_pax' => $initialPax,
            ]);

            $allocationCountBefore = GuestAllocation::count();

            $response = $this->actingAs($cashier)->post(
                route('allocations.store', $inactiveSession),
                [
                    'guest_name' => fake()->name(),
                    'pax' => $requestedPax,
                    'source' => 'walk-in',
                    'notes' => null,
                ]
            );

            // System must reject with a validation error
            $response->assertSessionHasErrors();

            // current_pax must remain unchanged
            expect($inactiveSession->fresh()->current_pax)->toBe($initialPax);

            // No GuestAllocation must have been created
            expect(GuestAllocation::count())->toBe($allocationCountBefore);
        } finally {
            DB::rollBack();
        }
    }
)->with($datasetProperty6)->group('Feature: cashier-session-dashboard', 'Property 6: Inactive session rejection');

// ─── Move to inactive session (Requirements 8.4) ─────────────────────────────

it(
    'rejects move request targeting an inactive session and leaves data unchanged',
    /**
     * **Validates: Requirements 8.4**
     *
     * Feature: cashier-session-dashboard
     * Property 6: Inactive session rejection
     */
    function (int $initialPax, int $allocationPax) {
        DB::beginTransaction();

        try {
            $cashier = User::factory()->cashier()->create();

            // Active source session with an existing allocation
            $sourceSession = Session::factory()->create([
                'status' => SessionStatus::Active,
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
                'max_capacity' => 20,
                'current_pax' => $allocationPax,
            ]);

            $allocation = GuestAllocation::factory()->create([
                'session_id' => $sourceSession->id,
                'pax' => $allocationPax,
                'status' => 'active',
            ]);

            // Inactive target session
            $inactiveTarget = Session::factory()->inactive()->create([
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
                'max_capacity' => 20,
                'current_pax' => $initialPax,
            ]);

            $response = $this->actingAs($cashier)->patch(
                route('allocations.move', $allocation),
                ['target_session_id' => $inactiveTarget->id]
            );

            // System must reject with a validation error
            $response->assertSessionHasErrors();

            // Allocation must still point to the source session
            expect($allocation->fresh()->session_id)->toBe($sourceSession->id);

            // Source session current_pax must remain unchanged
            expect($sourceSession->fresh()->current_pax)->toBe($allocationPax);

            // Inactive target current_pax must remain unchanged
            expect($inactiveTarget->fresh()->current_pax)->toBe($initialPax);
        } finally {
            DB::rollBack();
        }
    }
)->with($datasetProperty6)->group('Feature: cashier-session-dashboard', 'Property 6: Inactive session rejection');
