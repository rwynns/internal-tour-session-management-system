<?php

namespace Database\Seeders;

use App\Models\Attraction;
use Illuminate\Database\Seeder;

class AttractionSeeder extends Seeder
{
    /**
     * Seed demo attractions that represent realistic tour/activity offerings.
     *
     * @var array<int, array{name: string, description: string, duration_minutes: int, is_active: bool}>
     */
    private array $attractions = [
        [
            'name' => 'Museum Tour',
            'description' => 'Guided tour through the main museum galleries covering local history, culture, and artifacts. Suitable for all ages.',
            'duration_minutes' => 90,
            'is_active' => true,
        ],
        [
            'name' => 'Batik Workshop',
            'description' => 'Hands-on batik making workshop where guests learn traditional wax-resist dyeing techniques and create their own fabric piece.',
            'duration_minutes' => 120,
            'is_active' => true,
        ],
        [
            'name' => 'Feeding Session',
            'description' => 'Interactive animal feeding experience at the wildlife area. Guests can feed and interact with selected animals under staff supervision.',
            'duration_minutes' => 45,
            'is_active' => true,
        ],
        [
            'name' => 'ATV Experience',
            'description' => 'Off-road ATV adventure through designated trails. Helmets and safety gear provided. Minimum age 12 years.',
            'duration_minutes' => 60,
            'is_active' => true,
        ],
        [
            'name' => 'Cooking Class',
            'description' => 'Learn to prepare traditional local dishes with our experienced chefs. Includes tasting session at the end.',
            'duration_minutes' => 150,
            'is_active' => true,
        ],
        [
            'name' => 'Nature Walk',
            'description' => 'Guided nature walk through the botanical garden and surrounding green areas. Learn about local flora and fauna.',
            'duration_minutes' => 75,
            'is_active' => true,
        ],
        [
            'name' => 'Photography Tour',
            'description' => 'Curated photography walk to the most scenic spots on the property. Tips and guidance from our in-house photographer.',
            'duration_minutes' => 90,
            'is_active' => false,
        ],
    ];

    public function run(): void
    {
        foreach ($this->attractions as $data) {
            Attraction::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
