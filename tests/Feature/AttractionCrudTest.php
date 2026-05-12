<?php

use App\Models\Attraction;
use App\Models\Session;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// Requirement 2.1, 2.3: Listing returns paginated attractions
test('listing returns paginated attractions', function () {
    $admin = User::factory()->recreationAdmin()->create();
    Attraction::factory()->count(20)->create();

    $response = $this->actingAs($admin)->get(route('attractions.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('attractions/index', false)
        ->has('attractions.data')
        ->has('attractions.current_page')
        ->has('attractions.total')
    );
});

// Requirement 3.1: Creating with valid data stores attraction with is_active = true
test('creating with valid data stores attraction with is_active true', function () {
    $admin = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($admin)->post(route('attractions.store'), [
        'name' => 'Museum Tour',
        'description' => 'A guided tour of the museum.',
        'duration_minutes' => 60,
    ]);

    $response->assertRedirect(route('attractions.index'));
    $this->assertDatabaseHas('attractions', [
        'name' => 'Museum Tour',
        'duration_minutes' => 60,
        'is_active' => true,
    ]);
});

// Requirement 3.2: Creating without name returns validation error
test('creating without name returns validation error', function () {
    $admin = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($admin)->post(route('attractions.store'), [
        'description' => 'Some description.',
        'duration_minutes' => 30,
    ]);

    $response->assertSessionHasErrors(['name']);
});

// Requirement 3.3: Creating without duration returns validation error
test('creating without duration returns validation error', function () {
    $admin = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($admin)->post(route('attractions.store'), [
        'name' => 'Batik Workshop',
    ]);

    $response->assertSessionHasErrors(['duration_minutes']);
});

// Requirement 3.4: Creating with duration < 1 returns validation error
test('creating with duration less than 1 returns validation error', function () {
    $admin = User::factory()->recreationAdmin()->create();

    $response = $this->actingAs($admin)->post(route('attractions.store'), [
        'name' => 'Feeding Session',
        'duration_minutes' => 0,
    ]);

    $response->assertSessionHasErrors(['duration_minutes']);
});

// Requirement 4.1: Editing with valid data updates the record
test('editing with valid data updates the record', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create([
        'name' => 'Old Name',
        'duration_minutes' => 30,
    ]);

    $response = $this->actingAs($admin)->put(route('attractions.update', $attraction), [
        'name' => 'New Name',
        'duration_minutes' => 90,
    ]);

    $response->assertRedirect(route('attractions.index'));
    $this->assertDatabaseHas('attractions', [
        'id' => $attraction->id,
        'name' => 'New Name',
        'duration_minutes' => 90,
    ]);
});

// Requirement 4.2: Editing with invalid data returns validation errors
test('editing with invalid data returns validation errors', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();

    $response = $this->actingAs($admin)->put(route('attractions.update', $attraction), [
        'name' => '',
        'duration_minutes' => 0,
    ]);

    $response->assertSessionHasErrors(['name', 'duration_minutes']);
});

// Requirement 5.1: Deleting attraction without sessions succeeds
test('deleting attraction without sessions succeeds', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();

    $response = $this->actingAs($admin)->delete(route('attractions.destroy', $attraction));

    $response->assertRedirect(route('attractions.index'));
    $this->assertDatabaseMissing('attractions', ['id' => $attraction->id]);
});

// Requirement 5.2: Deleting attraction with sessions returns error
test('deleting attraction with sessions returns error and keeps the record', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create();
    Session::factory()->for($attraction)->create();

    $response = $this->actingAs($admin)->delete(route('attractions.destroy', $attraction));

    $response->assertRedirect();
    $this->assertDatabaseHas('attractions', ['id' => $attraction->id]);
});

// Requirement 6.1: Toggle active flips is_active from true to false
test('toggle active flips is_active from true to false', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->create(['is_active' => true]);

    $this->actingAs($admin)->patch(route('attractions.toggle-active', $attraction));

    $this->assertDatabaseHas('attractions', [
        'id' => $attraction->id,
        'is_active' => false,
    ]);
});

// Requirement 6.2: Toggle active flips is_active from false to true
test('toggle active flips is_active from false to true', function () {
    $admin = User::factory()->recreationAdmin()->create();
    $attraction = Attraction::factory()->inactive()->create();

    $this->actingAs($admin)->patch(route('attractions.toggle-active', $attraction));

    $this->assertDatabaseHas('attractions', [
        'id' => $attraction->id,
        'is_active' => true,
    ]);
});
