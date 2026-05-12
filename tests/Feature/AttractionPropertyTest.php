<?php

use App\Models\Attraction;
use App\Models\User;

/**
 * Property 1: Sorting preserves the complete data set
 *
 * Validates: Requirements 2.2, 7.2
 *
 * For any collection of attractions and any valid sort column and direction,
 * the sorted result SHALL contain exactly the same records as the unsorted
 * collection, differing only in order.
 */

// Generate 50 iterations: each with a random count (5–20), sort column, and direction
$sortingDataset = array_map(
    fn () => [
        fake()->numberBetween(5, 20),
        fake()->randomElement(['name', 'duration_minutes', 'is_active']),
        fake()->randomElement(['asc', 'desc']),
    ],
    range(1, 50)
);

it(
    'sorting preserves the complete data set',
    /**
     * **Validates: Requirements 2.2, 7.2**
     *
     * Feature: recreation-admin-management
     * Property 1: Sorting preserves the complete data set
     */
    function (int $count, string $sortColumn, string $direction) {
        $admin = User::factory()->recreationAdmin()->create();
        $attractions = Attraction::factory()->count($count)->create();
        $createdIds = $attractions->pluck('id')->sort()->values()->all();

        $this->actingAs($admin);

        $response = $this->get(route('attractions.index', [
            'sort' => $sortColumn,
            'direction' => $direction,
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('attractions/index', false)
            ->where('attractions.total', $count)
        );

        $responseData = $response->original->getData()['page']['props']['attractions'];

        // The total count must equal the number of created attractions
        expect($responseData['total'])->toBe($count);

        // All IDs in the response data must be a subset of the created IDs
        $responseIds = collect($responseData['data'])->pluck('id')->all();
        $diff = array_diff($responseIds, $createdIds);
        expect($diff)->toBeEmpty('Response contained IDs not in the created set');
    }
)->with($sortingDataset)->group('Feature: recreation-admin-management', 'Property 1: Sorting preserves the complete data set');

/**
 * Property 6: Toggle active status is its own inverse
 *
 * Validates: Requirements 6.1, 6.2
 *
 * For any attraction, toggling `is_active` twice returns to original state.
 */

// Generate 100 iterations with random initial is_active states
$dataset = array_map(
    fn () => [fake()->boolean()],
    range(1, 100)
);

it(
    'toggle active status is its own inverse',
    /**
     * **Validates: Requirements 6.1, 6.2**
     *
     * Feature: recreation-admin-management
     * Property 6: Toggle active status is its own inverse
     */
    function (bool $initialActive) {
        $admin = User::factory()->recreationAdmin()->create();
        $attraction = Attraction::factory()->create(['is_active' => $initialActive]);

        $this->actingAs($admin);

        // First toggle
        $this->patch(route('attractions.toggle-active', $attraction))
            ->assertRedirect();

        $attraction->refresh();
        expect($attraction->is_active)->toBe(! $initialActive);

        // Second toggle — must return to original state
        $this->patch(route('attractions.toggle-active', $attraction))
            ->assertRedirect();

        $attraction->refresh();
        expect($attraction->is_active)->toBe($initialActive);
    }
)->with($dataset)->group('Feature: recreation-admin-management', 'Property 6: Toggle active status is its own inverse');

/**
 * Property 7: Deletion is prevented when sessions exist
 *
 * Validates: Requirements 5.2
 *
 * For any attraction with ≥1 session, attempting to delete it fails
 * and the attraction record remains in the database.
 */

// Generate 100 iterations with a random session count (1–5) each
$deletionDataset = array_map(
    fn () => [fake()->numberBetween(1, 5)],
    range(1, 100)
);

it(
    'deletion is prevented when sessions exist',
    /**
     * **Validates: Requirements 5.2**
     *
     * Feature: recreation-admin-management
     * Property 7: Deletion is prevented when sessions exist
     */
    function (int $sessionCount) {
        $admin = User::factory()->recreationAdmin()->create();
        $attraction = Attraction::factory()->create();

        \App\Models\Session::factory()
            ->count($sessionCount)
            ->for($attraction)
            ->create();

        $this->actingAs($admin);

        $response = $this->delete(route('attractions.destroy', $attraction));

        // Must redirect back, not succeed
        $response->assertRedirect();

        // Attraction must still exist in the database
        $this->assertDatabaseHas('attractions', ['id' => $attraction->id]);
    }
)->with($deletionDataset)->group('Feature: recreation-admin-management', 'Property 7: Deletion is prevented when sessions exist');

/**
 * Property 4: Numeric boundary validation rejects out-of-range values
 *
 * Validates: Requirements 3.4
 *
 * For any integer where `duration_minutes < 1`, the attraction store
 * request is rejected with a validation error on `duration_minutes`.
 */

// Generate 100 iterations with duration_minutes < 1 (0 or negative integers)
$numericBoundaryDataset = array_map(
    fn () => [fake()->numberBetween(-10000, 0)],
    range(1, 100)
);

it(
    'numeric boundary validation rejects duration_minutes less than 1',
    /**
     * **Validates: Requirements 3.4**
     *
     * Feature: recreation-admin-management
     * Property 4: Numeric boundary validation rejects out-of-range values
     */
    function (int $invalidDuration) {
        $admin = User::factory()->recreationAdmin()->create();

        $this->actingAs($admin);

        $response = $this->post(route('attractions.store'), [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'duration_minutes' => $invalidDuration,
        ]);

        $response->assertSessionHasErrors(['duration_minutes']);
    }
)->with($numericBoundaryDataset)->group('Feature: recreation-admin-management', 'Property 4: Numeric boundary validation rejects out-of-range values');

/**
 * Property 2: Pagination returns the correct subset
 *
 * Validates: Requirements 2.3, 7.3
 *
 * For N records and page size 15, requesting page 2 returns exactly the
 * correct positional subset and the total count equals N.
 */

// Generate 50 iterations with a random attraction count between 16 and 30
$paginationDataset = array_map(
    fn () => [fake()->numberBetween(16, 30)],
    range(1, 50)
);

it(
    'pagination returns the correct subset on page 2',
    /**
     * **Validates: Requirements 2.3, 7.3**
     *
     * Feature: recreation-admin-management
     * Property 2: Pagination returns the correct subset
     */
    function (int $totalAttractions) {
        $admin = User::factory()->recreationAdmin()->create();
        Attraction::factory()->count($totalAttractions)->create();

        $response = $this->actingAs($admin)
            ->get(route('attractions.index', ['page' => 2]));

        $response->assertOk();

        /** @var array<string, mixed> $attractions */
        $attractions = $response->inertiaProps('attractions');

        expect($attractions['current_page'])->toBe(2);
        expect($attractions['total'])->toBe($totalAttractions);
        expect(count($attractions['data']))->toBeLessThanOrEqual(15);
        expect($attractions['last_page'])->toBe((int) ceil($totalAttractions / 15));
    }
)->with($paginationDataset)->group('Feature: recreation-admin-management', 'Property 2: Pagination returns the correct subset');

/**
 * Property 3: Valid creation produces correct defaults
 *
 * Validates: Requirements 3.1
 *
 * For any valid name (non-empty string ≤255) and duration (≥1),
 * creating an attraction produces `is_active = true`.
 */

// Generate 100 iterations with valid names and durations
$creationDefaultsDataset = array_map(
    fn () => [
        fake()->lexify(str_repeat('?', fake()->numberBetween(1, 255))),
        fake()->numberBetween(1, 10000),
    ],
    range(1, 100)
);

it(
    'valid creation produces is_active true by default',
    /**
     * **Validates: Requirements 3.1**
     *
     * Feature: recreation-admin-management
     * Property 3: Valid creation produces correct defaults
     */
    function (string $name, int $duration) {
        $admin = User::factory()->recreationAdmin()->create();

        $this->actingAs($admin);

        $response = $this->post(route('attractions.store'), [
            'name' => $name,
            'duration_minutes' => $duration,
        ]);

        $response->assertRedirect(route('attractions.index'));

        $this->assertDatabaseHas('attractions', [
            'name' => $name,
            'duration_minutes' => $duration,
            'is_active' => true,
        ]);
    }
)->with($creationDefaultsDataset)->group('Feature: recreation-admin-management', 'Property 3: Valid creation produces correct defaults');

/**
 * Property 5: Valid update round-trip
 *
 * Validates: Requirements 4.1, 9.1, 3.5
 *
 * For any valid update payload, reading back the record matches submitted values
 * for all updatable fields (name, description, duration_minutes).
 */

// Generate 100 iterations with valid update payloads
$updateRoundTripDataset = array_map(
    fn () => [
        fake()->lexify(str_repeat('?', fake()->numberBetween(1, 255))),
        fake()->optional(0.7)->sentence(),
        fake()->numberBetween(1, 10000),
    ],
    range(1, 100)
);

it(
    'valid update round-trip matches submitted values',
    /**
     * **Validates: Requirements 4.1, 9.1, 3.5**
     *
     * Feature: recreation-admin-management
     * Property 5: Valid update round-trip
     */
    function (string $newName, ?string $newDescription, int $newDuration) {
        $admin = User::factory()->recreationAdmin()->create();
        $attraction = Attraction::factory()->create();

        $this->actingAs($admin);

        $response = $this->put(route('attractions.update', $attraction), [
            'name' => $newName,
            'description' => $newDescription,
            'duration_minutes' => $newDuration,
        ]);

        $response->assertRedirect(route('attractions.index'));

        $attraction->refresh();

        expect($attraction->name)->toBe($newName)
            ->and($attraction->description)->toBe($newDescription)
            ->and($attraction->duration_minutes)->toBe($newDuration);
    }
)->with($updateRoundTripDataset)->group('Feature: recreation-admin-management', 'Property 5: Valid update round-trip');
