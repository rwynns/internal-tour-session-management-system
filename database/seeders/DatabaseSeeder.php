<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --- Users ---
        User::firstOrCreate(
            ['email' => 'recreation_adm@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::RecreationAdmin,
            ]
        );

        User::firstOrCreate(
            ['email' => 'cashier@gmail.com'],
            [
                'name' => 'Cashier',
                'password' => Hash::make('password'),
                'role' => UserRole::Cashier,
            ]
        );

        // --- Demo data (order matters: attractions → sessions → allocations) ---
        $this->call([
            AttractionSeeder::class,
            TourSessionSeeder::class,
            GuestAllocationSeeder::class,
        ]);
    }
}
