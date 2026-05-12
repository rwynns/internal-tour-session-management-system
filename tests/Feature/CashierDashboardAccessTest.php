<?php

use App\Models\Attraction;
use App\Models\Session;
use App\Models\User;

// ─── Access Control ──────────────────────────────────────────────────────────

test('authenticated cashier can access the dashboard and sees cashier view', function () {
    $cashier = User::factory()->cashier()->create();

    $response = $this->actingAs($cashier)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('cashier/dashboard', false));
});

test('authenticated admin can access the dashboard and sees admin view', function () {
    $admin = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('dashboard', false));
});

test('unauthenticated user is redirected to login when accessing dashboard', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('admin cannot access allocation routes', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $session = Session::factory()->create();

    $response = $this->actingAs($admin)->post(route('allocations.store', $session), [
        'guest_name' => 'Test Guest',
        'pax' => 1,
    ]);

    $response->assertForbidden();
});

test('unauthenticated user cannot access allocation routes', function () {
    $session = Session::factory()->create();

    $response = $this->post(route('allocations.store', $session), [
        'guest_name' => 'Test Guest',
        'pax' => 1,
    ]);

    $response->assertRedirect(route('login'));
});

// ─── Session Data ────────────────────────────────────────────────────────────

test('cashier dashboard renders sessions from today and future ordered by start_time', function () {
    $cashier = User::factory()->cashier()->create();
    $attraction = Attraction::factory()->create();

    $tomorrow = Session::factory()->for($attraction)->create([
        'start_time' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0),
        'end_time' => now()->addDay()->setHour(11)->setMinute(0)->setSecond(0),
    ]);

    $today = Session::factory()->for($attraction)->create([
        'start_time' => now()->setHour(14)->setMinute(0)->setSecond(0),
        'end_time' => now()->setHour(15)->setMinute(0)->setSecond(0),
    ]);

    $nextWeek = Session::factory()->for($attraction)->create([
        'start_time' => now()->addWeek()->setHour(9)->setMinute(0)->setSecond(0),
        'end_time' => now()->addWeek()->setHour(10)->setMinute(0)->setSecond(0),
    ]);

    $response = $this->actingAs($cashier)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('cashier/dashboard', false)
        ->has('sessions', 3)
        ->where('sessions.0.id', $today->id)
        ->where('sessions.1.id', $tomorrow->id)
        ->where('sessions.2.id', $nextWeek->id)
    );
});

test('past sessions are not included in the cashier dashboard', function () {
    $cashier = User::factory()->cashier()->create();
    $attraction = Attraction::factory()->create();

    Session::factory()->for($attraction)->create([
        'start_time' => now()->subDay()->setHour(10)->setMinute(0)->setSecond(0),
        'end_time' => now()->subDay()->setHour(11)->setMinute(0)->setSecond(0),
    ]);

    $futureSession = Session::factory()->for($attraction)->create([
        'start_time' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0),
        'end_time' => now()->addDay()->setHour(11)->setMinute(0)->setSecond(0),
    ]);

    $response = $this->actingAs($cashier)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('sessions', 1)
        ->where('sessions.0.id', $futureSession->id)
    );
});

test('cashier dashboard shows empty sessions when no upcoming sessions exist', function () {
    $cashier = User::factory()->cashier()->create();
    $attraction = Attraction::factory()->create();

    Session::factory()->for($attraction)->create([
        'start_time' => now()->subDay(),
        'end_time' => now()->subDay()->addHour(),
    ]);

    $response = $this->actingAs($cashier)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('sessions', 0)
    );
});

test('cashier dashboard sessions include eagerly loaded attraction data', function () {
    $cashier = User::factory()->cashier()->create();
    $attraction = Attraction::factory()->create(['name' => 'Batik Workshop']);

    Session::factory()->for($attraction)->create([
        'start_time' => now()->addDay(),
        'end_time' => now()->addDay()->addHour(),
    ]);

    $response = $this->actingAs($cashier)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('sessions', 1)
        ->where('sessions.0.attraction.name', 'Batik Workshop')
    );
});
