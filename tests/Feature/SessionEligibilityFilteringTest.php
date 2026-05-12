<?php

/**
 * Property 5: Session eligibility filtering
 *
 * For any set of sessions in the database, the eligible target sessions presented
 * in the Move_Modal for a source session S SHALL be exactly the set of sessions where:
 * status = "active" AND start_time >= current server time AND id != S.id.
 * No ineligible session shall appear, and no eligible session shall be omitted.
 *
 * Validates: Requirements 6.3, 8.5, 9.4
 */

use App\Enums\SessionStatus;
use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Property 5: Session eligibility filtering — 100 randomised iterations.
 *
 * For each iteration a fresh set of sessions is created:
 *   - N_active_future (1–5) eligible target sessions (active, future)
 *   - N_inactive (0–3) ineligible sessions (inactive, future)
 *   - N_past (0–3) ineligible sessions (active, past start_time)
 *   - The source session itself is always ineligible (id != S.id rule)
 *
 * Eligible sessions MUST accept the move (302, no session errors).
 * Ineligible sessions MUST be rejected (302, session errors on target_session_id).
 *
 * Validates: Requirements 6.3, 8.5, 9.4
 */
test('Property 5: session eligibility filtering — 100 randomised iterations', function () {
    $cashier = User::factory()->cashier()->create();

    for ($iteration = 0; $iteration < 100; $iteration++) {
        $nActiveFuture = rand(1, 5);
        $nInactive = rand(0, 3);
        $nPast = rand(0, 3);

        // Source session: active, future, pax=1 so capacity is never the rejection reason
        $sourceSession = Session::factory()->create([
            'status' => SessionStatus::Active,
            'start_time' => now()->addHours(rand(1, 24)),
            'end_time' => now()->addHours(rand(25, 48)),
            'max_capacity' => 20,
            'current_pax' => 1,
        ]);

        $allocation = GuestAllocation::factory()->create([
            'session_id' => $sourceSession->id,
            'pax' => 1,
            'status' => 'active',
            'allocated_by' => $cashier->id,
        ]);

        // Eligible: active + future
        $eligibleSessions = collect();
        for ($i = 0; $i < $nActiveFuture; $i++) {
            $eligibleSessions->push(Session::factory()->create([
                'status' => SessionStatus::Active,
                'start_time' => now()->addHours(rand(1, 24)),
                'end_time' => now()->addHours(rand(25, 48)),
                'max_capacity' => 20,
                'current_pax' => 0,
            ]));
        }

        // Ineligible: inactive + future
        $inactiveSessions = collect();
        for ($i = 0; $i < $nInactive; $i++) {
            $inactiveSessions->push(Session::factory()->create([
                'status' => SessionStatus::Inactive,
                'start_time' => now()->addHours(rand(1, 24)),
                'end_time' => now()->addHours(rand(25, 48)),
                'max_capacity' => 20,
                'current_pax' => 0,
            ]));
        }

        // Ineligible: active + past start_time
        $pastSessions = collect();
        for ($i = 0; $i < $nPast; $i++) {
            $pastSessions->push(Session::factory()->create([
                'status' => SessionStatus::Active,
                'start_time' => now()->subHours(rand(1, 24)),
                'end_time' => now()->subMinutes(rand(1, 30)),
                'max_capacity' => 20,
                'current_pax' => 0,
            ]));
        }

        // ── Eligible sessions must accept the move ────────────────────────────
        foreach ($eligibleSessions as $targetSession) {
            // Ensure allocation is on source before each move attempt.
            // Use a raw query to bypass Eloquent's dirty-tracking, since the
            // controller updates the DB directly and the PHP object may not
            // reflect the latest session_id.
            GuestAllocation::where('id', $allocation->id)
                ->update(['session_id' => $sourceSession->id]);
            Session::where('id', $sourceSession->id)->update(['current_pax' => 1]);
            Session::where('id', $targetSession->id)->update(['current_pax' => 0]);

            $response = $this->actingAs($cashier)->patch(
                route('allocations.move', $allocation),
                ['target_session_id' => $targetSession->id]
            );

            $response->assertRedirect();
            $response->assertSessionMissing('errors');
        }

        // Reset allocation to source before checking ineligible sessions.
        GuestAllocation::where('id', $allocation->id)
            ->update(['session_id' => $sourceSession->id]);
        Session::where('id', $sourceSession->id)->update(['current_pax' => 1]);

        // ── Source session itself must be rejected (id != S.id rule) ─────────
        $response = $this->actingAs($cashier)->patch(
            route('allocations.move', $allocation),
            ['target_session_id' => $sourceSession->id]
        );
        $response->assertRedirect();
        $response->assertSessionHasErrors('target_session_id');
        expect($allocation->fresh()->session_id)->toBe($sourceSession->id);

        // ── Inactive sessions must be rejected ────────────────────────────────
        foreach ($inactiveSessions as $inactiveSession) {
            $response = $this->actingAs($cashier)->patch(
                route('allocations.move', $allocation),
                ['target_session_id' => $inactiveSession->id]
            );
            $response->assertRedirect();
            $response->assertSessionHasErrors('target_session_id');
            expect($allocation->fresh()->session_id)->toBe($sourceSession->id);
        }

        // ── Past sessions must be rejected ────────────────────────────────────
        foreach ($pastSessions as $pastSession) {
            $response = $this->actingAs($cashier)->patch(
                route('allocations.move', $allocation),
                ['target_session_id' => $pastSession->id]
            );
            $response->assertRedirect();
            $response->assertSessionHasErrors('target_session_id');
            expect($allocation->fresh()->session_id)->toBe($sourceSession->id);
        }
    }
});
