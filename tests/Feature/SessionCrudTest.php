<?php

use App\Enums\SessionStatus;
use App\Models\Attraction;
use App\Models\Session;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─── Helper ──────────────────────────────────────────────────────────────────

function sessionPayload(Attraction $attraction, array $overrides = []): array
{
    $start = now()->addDay()->setSecond(0)->setMicrosecond(0);

    return array_merge([
        'attraction_id' => $attraction->id,
        'start_time' => $start->toDateTimeString(),
        'end_time' => $start->copy()->addHour()->toDateTimeString(),
        'max_capacity' => 50,
    ], $overrides);
}

// ─── Listing (Requirements 7.1, 7.2, 7.3, 7.4) ──────────────────────────────

// Requirement 7.1: listing returns paginated sessions with attraction name and occupancy
test('session listing returns paginated sessions with attraction name and occupancy', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create(['name' => 'Ocean Tour']);
    Session::factory()->for($attraction)->withOccupancy(3)->create(['max_capacity' => 10]);

    $response = $this->actingAs($user)->get(route('sessions.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('sessions/index', false)
        ->has('sessions.data', 1)
        ->where('sessions.data.0.attraction.name', 'Ocean Tour')
        ->where('sessions.data.0.current_pax', 3)
        ->where('sessions.data.0.max_capacity', 10)
    );
});

// Requirement 7.3: listing is paginated
test('session listing is paginated', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();
    Session::factory()->for($attraction)->count(20)->create();

    $response = $this->actingAs($user)->get(route('sessions.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('sessions.data', 15)
        ->where('sessions.total', 20)
    );
});

// Requirement 7.4: filtering by attraction_id returns only matching sessions
test('filtering sessions by attraction_id returns only matching sessions', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attractionA = Attraction::factory()->create();
    $attractionB = Attraction::factory()->create();
    Session::factory()->for($attractionA)->count(3)->create();
    Session::factory()->for($attractionB)->count(2)->create();

    $response = $this->actingAs($user)->get(route('sessions.index', ['attraction_id' => $attractionA->id]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('sessions.data', 3)
        ->where('sessions.data.0.attraction_id', $attractionA->id)
    );
});

// ─── Create (Requirements 8.1, 8.2, 8.5, 8.7, 8.8) ─────────────────────────

// Requirement 8.1: creating with valid data stores session with status 'active' and current_pax 0
test('creating session with valid data stores session with status active and current_pax 0', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();

    $response = $this->actingAs($user)->post(route('sessions.store'), sessionPayload($attraction));

    $response->assertRedirect(route('sessions.index'));
    $this->assertDatabaseHas('tour_sessions', [
        'attraction_id' => $attraction->id,
        'max_capacity' => 50,
        'current_pax' => 0,
        'status' => SessionStatus::Active->value,
    ]);
});

// Requirement 8.2: creating without attraction returns validation error
test('creating session without attraction returns validation error', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();
    $payload = sessionPayload($attraction);
    unset($payload['attraction_id']);

    $response = $this->actingAs($user)->post(route('sessions.store'), $payload);

    $response->assertSessionHasErrors('attraction_id');
    $this->assertDatabaseCount('tour_sessions', 0);
});

// Requirement 8.5: creating with end_time equal to start_time returns validation error
test('creating session with end_time equal to start_time returns validation error', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();
    $start = now()->addDay()->toDateTimeString();

    $response = $this->actingAs($user)->post(route('sessions.store'), sessionPayload($attraction, [
        'start_time' => $start,
        'end_time' => $start,
    ]));

    $response->assertSessionHasErrors('end_time');
});

// Requirement 8.5: creating with end_time before start_time returns validation error
test('creating session with end_time before start_time returns validation error', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();

    $response = $this->actingAs($user)->post(route('sessions.store'), sessionPayload($attraction, [
        'end_time' => now()->subDay()->toDateTimeString(),
    ]));

    $response->assertSessionHasErrors('end_time');
});

// Requirement 8.7: creating with max_capacity below 1 returns validation error
test('creating session with max_capacity below 1 returns validation error', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();

    $response = $this->actingAs($user)->post(route('sessions.store'), sessionPayload($attraction, [
        'max_capacity' => 0,
    ]));

    $response->assertSessionHasErrors('max_capacity');
});

// Requirement 8.7: creating with max_capacity above 1000 returns validation error
test('creating session with max_capacity above 1000 returns validation error', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();

    $response = $this->actingAs($user)->post(route('sessions.store'), sessionPayload($attraction, [
        'max_capacity' => 1001,
    ]));

    $response->assertSessionHasErrors('max_capacity');
});

// Requirement 8.8: creating for inactive attraction returns validation error
test('creating session for inactive attraction returns validation error', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->inactive()->create();

    $response = $this->actingAs($user)->post(route('sessions.store'), sessionPayload($attraction));

    $response->assertSessionHasErrors('attraction_id');
    $this->assertDatabaseCount('tour_sessions', 0);
});

// ─── Edit / Update (Requirements 9.1, 9.3) ───────────────────────────────────

// Requirement 9.1: editing with valid data updates the record
test('editing session with valid data updates the record', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();
    $session = Session::factory()->for($attraction)->create(['max_capacity' => 20]);

    $response = $this->actingAs($user)->put(
        route('sessions.update', $session),
        sessionPayload($attraction, ['max_capacity' => 30])
    );

    $response->assertRedirect(route('sessions.index'));
    $this->assertDatabaseHas('tour_sessions', [
        'id' => $session->id,
        'max_capacity' => 30,
    ]);
});

// Requirement 9.3: editing with max_capacity below current_pax returns validation error
test('editing session with max_capacity below current_pax returns validation error', function () {
    $user = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();
    $session = Session::factory()->for($attraction)->withOccupancy(10)->create(['max_capacity' => 20]);

    $response = $this->actingAs($user)->put(
        route('sessions.update', $session),
        sessionPayload($attraction, ['max_capacity' => 5])
    );

    $response->assertSessionHasErrors('max_capacity');
    $this->assertDatabaseHas('tour_sessions', ['id' => $session->id, 'max_capacity' => 20]);
});

// ─── Status Change (Requirements 10.1, 10.2, 10.3, 10.5) ────────────────────

// Requirement 10.2: status change to inactive succeeds
test('session status change to inactive succeeds', function () {
    $user = User::factory()->recreationAdmin()->create();
    $session = Session::factory()->create(['status' => SessionStatus::Active]);

    $response = $this->actingAs($user)->patch(route('sessions.update-status', $session), ['status' => 'inactive']);

    $response->assertRedirect();
    $this->assertDatabaseHas('tour_sessions', [
        'id' => $session->id,
        'status' => SessionStatus::Inactive->value,
    ]);
});

// Requirement 10.1: status change to active succeeds
test('session status change to active succeeds', function () {
    $user = User::factory()->recreationAdmin()->create();
    $session = Session::factory()->inactive()->create();

    $response = $this->actingAs($user)->patch(route('sessions.update-status', $session), ['status' => 'active']);

    $response->assertRedirect();
    $this->assertDatabaseHas('tour_sessions', [
        'id' => $session->id,
        'status' => SessionStatus::Active->value,
    ]);
});

// Requirement 10.5: status change preserves current_pax
test('session status change preserves current_pax', function () {
    $user = User::factory()->recreationAdmin()->create();
    $session = Session::factory()->withOccupancy(7)->create(['status' => SessionStatus::Active]);

    $this->actingAs($user)->patch(route('sessions.update-status', $session), ['status' => 'inactive']);

    $this->assertDatabaseHas('tour_sessions', [
        'id' => $session->id,
        'current_pax' => 7,
        'status' => SessionStatus::Inactive->value,
    ]);
});
