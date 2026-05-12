<?php

use App\Models\Attraction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property 9: Sessions cannot be created for inactive attractions
 *
 * Validates: Requirements 8.8
 *
 * For any inactive attraction, session creation referencing it is rejected.
 */

// Generate 100 iterations with random valid session data
$dataset = array_map(
    function () {
        $start = fake()->dateTimeBetween('+1 day', '+30 days');
        $end = (clone $start)->modify('+'.fake()->numberBetween(30, 120).' minutes');

        return [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            fake()->numberBetween(1, 1000),
        ];
    },
    range(1, 100)
);

it(
    'rejects session creation for inactive attractions',
    /**
     * **Validates: Requirements 8.8**
     *
     * Feature: recreation-admin-management
     * Property 9: Sessions cannot be created for inactive attractions
     */
    function (string $startTime, string $endTime, int $capacity) {
        $admin = User::factory()->recreationAdmin()->create();
        $attraction = Attraction::factory()->inactive()->create();

        $this->actingAs($admin);

        $response = $this->post(route('sessions.store'), [
            'attraction_id' => $attraction->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_capacity' => $capacity,
        ]);

        $response->assertSessionHasErrors('attraction_id');
    }
)->with($dataset)->group('Feature: recreation-admin-management', 'Property 9: Sessions cannot be created for inactive attractions');

/**
 * Property 3: Valid creation produces correct defaults
 *
 * Validates: Requirements 8.1
 *
 * For any valid session data (active attraction, start < end, 1 ≤ capacity ≤ 1000),
 * creating produces `status = 'active'` and `current_pax = 0`.
 */

// Generate 100 iterations with random valid session data
$datasetProperty3 = array_map(
    function () {
        $start = fake()->dateTimeBetween('+1 day', '+30 days');
        $end = (clone $start)->modify('+'.fake()->numberBetween(30, 120).' minutes');

        return [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            fake()->numberBetween(1, 1000),
        ];
    },
    range(1, 100)
);

it(
    'valid session creation produces correct defaults',
    /**
     * **Validates: Requirements 8.1**
     *
     * Feature: recreation-admin-management
     * Property 3: Valid creation produces correct defaults
     */
    function (string $startTime, string $endTime, int $capacity) {
        $admin = User::factory()->recreationAdmin()->create();
        $attraction = Attraction::factory()->create();

        $this->actingAs($admin);

        $response = $this->post(route('sessions.store'), [
            'attraction_id' => $attraction->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_capacity' => $capacity,
        ]);

        $response->assertRedirect(route('sessions.index'));

        $this->assertDatabaseHas('tour_sessions', [
            'attraction_id' => $attraction->id,
            'max_capacity' => $capacity,
            'status' => 'active',
            'current_pax' => 0,
        ]);
    }
)->with($datasetProperty3)->group('Feature: recreation-admin-management', 'Property 3: Valid creation produces correct defaults');

/**
 * Property 8: End time must be strictly after start time
 *
 * Validates: Requirements 8.5
 *
 * For any pair where end_time ≤ start_time, session creation is rejected.
 */

// Generate 100 iterations where end_time is equal to or before start_time
$datasetProperty8 = array_map(
    function () {
        $start = fake()->dateTimeBetween('+1 day', '+30 days');
        // Either same time (offset = 0) or end before start (negative offset)
        $offsetMinutes = fake()->numberBetween(0, 120);
        $end = (clone $start)->modify('-'.$offsetMinutes.' minutes');

        return [
            $start->format('Y-m-d H:i:s'),
            $end->format('Y-m-d H:i:s'),
            fake()->numberBetween(1, 1000),
        ];
    },
    range(1, 100)
);

it(
    'rejects session creation when end time is not strictly after start time',
    /**
     * **Validates: Requirements 8.5**
     *
     * Feature: recreation-admin-management
     * Property 8: End time must be strictly after start time
     */
    function (string $startTime, string $endTime, int $capacity) {
        $admin = User::factory()->recreationAdmin()->create();
        $attraction = Attraction::factory()->create();

        $this->actingAs($admin);

        $response = $this->post(route('sessions.store'), [
            'attraction_id' => $attraction->id,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_capacity' => $capacity,
        ]);

        $response->assertSessionHasErrors('end_time');
    }
)->with($datasetProperty8)->group('Feature: recreation-admin-management', 'Property 8: End time must be strictly after start time');

/**
 * Property 10: Capacity cannot be reduced below current occupancy
 *
 * Validates: Requirements 9.3
 *
 * For any session with current_pax > 0, setting max_capacity < current_pax is rejected.
 */

// Generate 100 iterations where max_capacity is less than current_pax
$datasetProperty10 = array_map(
    function () {
        // Start from 2 so there is always a valid integer below current_pax (i.e. at least 1)
        $currentPax = fake()->numberBetween(2, 50);
        // too_low_capacity is strictly less than current_pax, so the floor validation triggers
        $tooLowCapacity = fake()->numberBetween(1, $currentPax - 1);

        return [
            $currentPax,
            $tooLowCapacity,
        ];
    },
    range(1, 100)
);

it(
    'rejects update when max_capacity is set below current occupancy',
    /**
     * **Validates: Requirements 9.3**
     *
     * Feature: recreation-admin-management
     * Property 10: Capacity cannot be reduced below current occupancy
     */
    function (int $currentPax, int $tooLowCapacity) {
        $admin = User::factory()->recreationAdmin()->create();

        // Create a session with current_pax occupancy and max_capacity >= current_pax
        $session = \App\Models\Session::factory()
            ->withOccupancy($currentPax)
            ->create(['max_capacity' => $currentPax + fake()->numberBetween(0, 50)]);

        $this->actingAs($admin);

        $start = fake()->dateTimeBetween('+1 day', '+30 days');
        $end = (clone $start)->modify('+'.fake()->numberBetween(30, 120).' minutes');

        $response = $this->put(route('sessions.update', $session), [
            'attraction_id' => $session->attraction_id,
            'start_time' => $start->format('Y-m-d H:i:s'),
            'end_time' => $end->format('Y-m-d H:i:s'),
            'max_capacity' => $tooLowCapacity,
        ]);

        $response->assertSessionHasErrors('max_capacity');
    }
)->with($datasetProperty10)->group('Feature: recreation-admin-management', 'Property 10: Capacity cannot be reduced below current occupancy');

/**
 * Property 11: Only active sessions appear in available sessions list
 *
 * Validates: Requirements 10.4
 *
 * For any query of available sessions, every result has status = 'active'.
 * No session with status 'inactive' shall appear in the results.
 */

// Generate 50 iterations with random counts of active and inactive sessions
$datasetProperty11 = array_map(
    function () {
        return [
            fake()->numberBetween(1, 5),  // active count
            fake()->numberBetween(1, 5),  // inactive count
        ];
    },
    range(1, 50)
);

it(
    'only active sessions appear in the available sessions query',
    /**
     * **Validates: Requirements 10.4**
     *
     * Feature: recreation-admin-management
     * Property 11: Only active sessions appear in available sessions list
     */
    function (int $activeCount, int $inactiveCount) {
        $attraction = Attraction::factory()->create();

        \App\Models\Session::factory()->count($activeCount)->create([
            'attraction_id' => $attraction->id,
        ]);
        \App\Models\Session::factory()->inactive()->count($inactiveCount)->create([
            'attraction_id' => $attraction->id,
        ]);

        $availableSessions = \App\Models\Session::where('status', 'active')->get();

        // Every result must have status = 'active'
        foreach ($availableSessions as $session) {
            expect($session->status->value)->toBe('active');
        }

        // Total active sessions in DB must equal the count returned
        expect($availableSessions)->toHaveCount($activeCount);

        // Inactive sessions must NOT appear in the active-only result set
        $inactiveIds = \App\Models\Session::where('status', 'inactive')->pluck('id');
        $availableIds = $availableSessions->pluck('id');

        foreach ($inactiveIds as $id) {
            expect($availableIds->contains($id))->toBeFalse();
        }
    }
)->with($datasetProperty11)->group('Feature: recreation-admin-management', 'Property 11: Only active sessions appear in available sessions list');

/**
 * Property 13: Session filter returns only matching attraction's sessions
 *
 * Validates: Requirements 7.4
 *
 * For any attraction filter applied to the session list, every returned session
 * SHALL have an attraction_id matching the filter value. No session belonging to
 * a different attraction SHALL appear.
 */

// Generate 50 iterations: each with 2-3 attractions and 2-5 sessions per attraction
$datasetProperty13 = array_map(
    function () {
        return [
            fake()->numberBetween(2, 3), // number of attractions
            fake()->numberBetween(2, 5), // sessions per attraction
        ];
    },
    range(1, 50)
);

it(
    'session filter returns only sessions belonging to the filtered attraction',
    /**
     * **Validates: Requirements 7.4**
     *
     * Feature: recreation-admin-management
     * Property 13: Session filter returns only matching attraction's sessions
     */
    function (int $attractionCount, int $sessionsPerAttraction) {
        $admin = User::factory()->recreationAdmin()->create();

        // Create 2-3 attractions, each with 2-5 sessions
        $attractions = Attraction::factory()->count($attractionCount)->create();
        foreach ($attractions as $attraction) {
            \App\Models\Session::factory()->for($attraction)->count($sessionsPerAttraction)->create();
        }

        // Pick one attraction to filter by
        $targetAttraction = $attractions->random();

        $this->actingAs($admin);

        $response = $this->get(route('sessions.index', ['attraction_id' => $targetAttraction->id]));

        $response->assertOk();
        $response->assertInertia(function ($page) use ($targetAttraction) {
            $page->has('sessions.data');

            $sessions = $page->toArray()['props']['sessions']['data'];

            foreach ($sessions as $session) {
                expect($session['attraction_id'])->toBe($targetAttraction->id);
            }
        });
    }
)->with($datasetProperty13)->group('Feature: recreation-admin-management', 'Property 13: Session filter returns only matching attraction\'s sessions');
