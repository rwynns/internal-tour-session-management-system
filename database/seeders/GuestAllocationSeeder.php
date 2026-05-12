<?php

namespace Database\Seeders;

use App\Models\GuestAllocation;
use App\Models\Session;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GuestAllocationSeeder extends Seeder
{
    /**
     * Seed guest allocations for sessions that already have current_pax > 0.
     * Allocations are created to match the current_pax values set in TourSessionSeeder.
     *
     * @var array<int, array{guest_name: string, pax: int, source: string, notes: string|null}>
     */
    private array $guestPool = [
        ['guest_name' => 'Budi Santoso',      'pax' => 2, 'source' => 'walk-in',      'notes' => null],
        ['guest_name' => 'Siti Rahayu',       'pax' => 3, 'source' => 'travel-agent', 'notes' => 'Group from Panorama Tours'],
        ['guest_name' => 'Ahmad Fauzi',       'pax' => 1, 'source' => 'phone',        'notes' => null],
        ['guest_name' => 'Dewi Kusuma',       'pax' => 4, 'source' => 'walk-in',      'notes' => null],
        ['guest_name' => 'Rudi Hartono',      'pax' => 2, 'source' => 'travel-agent', 'notes' => 'Prepaid via Garuda Wisata'],
        ['guest_name' => 'Rina Wulandari',    'pax' => 1, 'source' => 'phone',        'notes' => 'Wheelchair accessible needed'],
        ['guest_name' => 'Hendra Wijaya',     'pax' => 5, 'source' => 'travel-agent', 'notes' => 'Family package'],
        ['guest_name' => 'Lestari Putri',     'pax' => 2, 'source' => 'walk-in',      'notes' => null],
        ['guest_name' => 'Agus Setiawan',     'pax' => 3, 'source' => 'phone',        'notes' => null],
        ['guest_name' => 'Yuni Astuti',       'pax' => 1, 'source' => 'walk-in',      'notes' => null],
        ['guest_name' => 'Bambang Purnomo',   'pax' => 4, 'source' => 'travel-agent', 'notes' => 'Corporate group - PT Maju Jaya'],
        ['guest_name' => 'Fitri Handayani',   'pax' => 2, 'source' => 'phone',        'notes' => null],
        ['guest_name' => 'Doni Prasetyo',     'pax' => 1, 'source' => 'walk-in',      'notes' => null],
        ['guest_name' => 'Mega Sari',         'pax' => 3, 'source' => 'travel-agent', 'notes' => 'Honeymoon package'],
        ['guest_name' => 'Wahyu Nugroho',     'pax' => 2, 'source' => 'phone',        'notes' => null],
        ['guest_name' => 'Indah Permata',     'pax' => 5, 'source' => 'travel-agent', 'notes' => 'School group - SD Harapan Bangsa'],
        ['guest_name' => 'Rizky Maulana',     'pax' => 1, 'source' => 'walk-in',      'notes' => null],
        ['guest_name' => 'Citra Dewi',        'pax' => 2, 'source' => 'phone',        'notes' => null],
        ['guest_name' => 'Fajar Hidayat',     'pax' => 3, 'source' => 'walk-in',      'notes' => null],
        ['guest_name' => 'Nadia Safitri',     'pax' => 4, 'source' => 'travel-agent', 'notes' => 'Group from Jaya Tour & Travel'],
    ];

    public function run(): void
    {
        $cashier = User::where('role', 'cashier')->first();
        $admin = User::where('role', 'recreation_admin')->first();
        $allocatedBy = $cashier ?? $admin;

        if (! $allocatedBy) {
            $this->command->warn('No users found. Run DatabaseSeeder first.');

            return;
        }

        // Only seed allocations for sessions that have current_pax > 0
        $sessions = Session::where('current_pax', '>', 0)->with('attraction')->get();

        $guestIndex = 0;

        foreach ($sessions as $session) {
            $remainingPax = $session->current_pax;

            while ($remainingPax > 0) {
                $guest = $this->guestPool[$guestIndex % count($this->guestPool)];
                $pax = min($guest['pax'], $remainingPax);

                // Skip if this exact allocation already exists
                $exists = GuestAllocation::where('session_id', $session->id)
                    ->where('guest_name', $guest['guest_name'])
                    ->where('status', 'active')
                    ->exists();

                if (! $exists) {
                    GuestAllocation::create([
                        'session_id' => $session->id,
                        'guest_name' => $guest['guest_name'],
                        'pax' => $pax,
                        'source' => $guest['source'],
                        'notes' => $guest['notes'],
                        'status' => 'active',
                        'allocated_by' => $allocatedBy->id,
                    ]);
                }

                $remainingPax -= $pax;
                $guestIndex++;
            }
        }

        // Add a few cancelled allocations to demonstrate that status as well
        $this->seedCancelledAllocations($allocatedBy->id);
    }

    /**
     * Seed a handful of cancelled allocations for demo purposes.
     */
    private function seedCancelledAllocations(int $allocatedById): void
    {
        $futureSessions = Session::where('start_time', '>', Carbon::now())
            ->where('status', 'active')
            ->limit(3)
            ->get();

        $cancelledGuests = [
            ['guest_name' => 'Tono Subekti',   'pax' => 2, 'source' => 'phone',   'notes' => 'Cancelled due to illness'],
            ['guest_name' => 'Wati Susanti',    'pax' => 1, 'source' => 'walk-in', 'notes' => null],
            ['guest_name' => 'Eko Prasetyo',    'pax' => 3, 'source' => 'travel-agent', 'notes' => 'Group cancelled trip'],
        ];

        foreach ($futureSessions as $index => $session) {
            $guest = $cancelledGuests[$index] ?? $cancelledGuests[0];

            $exists = GuestAllocation::where('session_id', $session->id)
                ->where('guest_name', $guest['guest_name'])
                ->where('status', 'cancelled')
                ->exists();

            if (! $exists) {
                GuestAllocation::create([
                    'session_id' => $session->id,
                    'guest_name' => $guest['guest_name'],
                    'pax' => $guest['pax'],
                    'source' => $guest['source'],
                    'notes' => $guest['notes'],
                    'status' => 'cancelled',
                    'allocated_by' => $allocatedById,
                ]);
            }
        }
    }
}
