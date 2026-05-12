<?php

use App\Enums\SessionStatus;
use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Property 3: Move atomically transfers pax between sessions
 *
 * Validates: Requirements 6.4
 *
 * For any active GuestAllocation with pax P on source session S1 (with current_pax C1)
 * being moved to eligible target session S2 (with current_pax C2 and remaining capacity ≥ P),
 * the operation SHALL result in: allocation.session_id = S2.id, S1.current_pax = C1 - P,
 * S2.current_pax = C2 + P, with no intermediate state visible to other transactions.
 */

// Generate 100 iterations with randomized pax (P), source occupancy (C1), and target occupancy (C2)
$moveAtomicityDataset = array_map(
    function () {
        $p = fake()->numberBetween(1, 5);
        $c1 = fake()->numberBetween($p, 10);
        $c2 = fake()->numberBetween(0, 10 - $p);

        return [$p, $c1, $c2];
    },
    range(1, 100)
);

it(
    'move atomically transfers pax between sessions',
    /**
     * **Validates: Requirements 6.4**
     *
     * Feature: cashier-session-dashboard
     * Property 3: Move atomically transfers pax between sessions
     */
    function (int $p, int $c1, int $c2) {
        $cashier = User::factory()->cashier()->create();

        DB::beginTransaction();

        try {
            $sourceSession = Session::factory()->create([
                'status' => SessionStatus::Active,
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
                'max_capacity' => 20,
                'current_pax' => $c1,
            ]);

            $targetSession = Session::factory()->create([
                'status' => SessionStatus::Active,
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
                'max_capacity' => 20,
                'current_pax' => $c2,
            ]);

            $allocation = GuestAllocation::factory()->create([
                'session_id' => $sourceSession->id,
                'pax' => $p,
                'status' => 'active',
            ]);

            $response = $this->actingAs($cashier)->patch(
                route('allocations.move', $allocation),
                ['target_session_id' => $targetSession->id]
            );

            $response->assertRedirect();
            $response->assertSessionHasNoErrors();

            // allocation.session_id must equal target session
            expect($allocation->fresh()->session_id)->toBe($targetSession->id);

            // source current_pax must decrease by P
            expect($sourceSession->fresh()->current_pax)->toBe($c1 - $p);

            // target current_pax must increase by P
            expect($targetSession->fresh()->current_pax)->toBe($c2 + $p);
        } finally {
            DB::rollBack();
        }
    }
)->with($moveAtomicityDataset)->group('Feature: cashier-session-dashboard', 'Property 3: Move atomically transfers pax between sessions');
