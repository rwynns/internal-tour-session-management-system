<?php

/**
 * Property 7: Past session rejection
 *
 * For any session with start_time in the past relative to server time, for any
 * allocation request or move request targeting that session, the system SHALL
 * reject the request and return a validation error, leaving all data unchanged.
 *
 * Validates: Requirements 9.2, 9.3
 */

use App\Enums\SessionStatus;
use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Illuminate\Support\Facades\DB;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Property 7: Past session rejection ──────────────────────────────────────

test('Property 7: allocation to past session is always rejected with no data changes', function () {
    $cashier = User::factory()->cashier()->create();

    for ($i = 0; $i < 100; $i++) {
        DB::beginTransaction();

        try {
            // Generate random hours in the past (1–48 hours ago)
            $hoursAgo = rand(1, 48);

            // Create a past session: active status, start_time in the past
            $pastSession = Session::factory()->create([
                'status' => SessionStatus::Active,
                'start_time' => now()->subHours($hoursAgo),
                'end_time' => now()->subHours($hoursAgo - 1),
                'max_capacity' => 20,
                'current_pax' => rand(0, 10),
            ]);

            $originalPax = $pastSession->current_pax;
            $allocationCountBefore = GuestAllocation::where('session_id', $pastSession->id)->count();

            // Attempt to allocate to the past session
            $response = $this->actingAs($cashier)->post(
                route('allocations.store', $pastSession),
                [
                    'guest_name' => 'Test Guest',
                    'pax' => 1,
                    'source' => 'walk-in',
                    'notes' => null,
                ]
            );

            // Assert validation errors are present
            $response->assertSessionHasErrors();

            // Assert current_pax is unchanged
            expect($pastSession->fresh()->current_pax)->toBe($originalPax);

            // Assert no GuestAllocation was created
            $allocationCountAfter = GuestAllocation::where('session_id', $pastSession->id)->count();
            expect($allocationCountAfter)->toBe($allocationCountBefore);
        } finally {
            DB::rollBack();
        }
    }
})->group('Feature: cashier-session-dashboard, Property 7: past session rejection');

test('Property 7: move to past session is always rejected with no data changes', function () {
    $cashier = User::factory()->cashier()->create();

    for ($i = 0; $i < 100; $i++) {
        DB::beginTransaction();

        try {
            // Create an active source session with an allocation
            $sourceSession = Session::factory()->create([
                'status' => SessionStatus::Active,
                'start_time' => now()->addHour(),
                'end_time' => now()->addHours(2),
                'max_capacity' => 20,
                'current_pax' => 2,
            ]);

            $allocation = GuestAllocation::factory()->create([
                'session_id' => $sourceSession->id,
                'pax' => 2,
                'status' => 'active',
            ]);

            // Generate random hours in the past (1–48 hours ago)
            $hoursAgo = rand(1, 48);

            // Create a past target session: active status, start_time in the past
            $pastTarget = Session::factory()->create([
                'status' => SessionStatus::Active,
                'start_time' => now()->subHours($hoursAgo),
                'end_time' => now()->subHours($hoursAgo - 1),
                'max_capacity' => 20,
                'current_pax' => rand(0, 10),
            ]);

            $originalAllocationSessionId = $allocation->session_id;

            // Attempt to move the allocation to the past target session
            $response = $this->actingAs($cashier)->patch(
                route('allocations.move', $allocation),
                ['target_session_id' => $pastTarget->id]
            );

            // Assert validation errors are present
            $response->assertSessionHasErrors();

            // Assert allocation session_id is unchanged
            expect($allocation->fresh()->session_id)->toBe($originalAllocationSessionId);
        } finally {
            DB::rollBack();
        }
    }
})->group('Feature: cashier-session-dashboard, Property 7: past session rejection');
