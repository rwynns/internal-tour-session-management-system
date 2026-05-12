<?php

namespace Database\Seeders;

use App\Enums\SessionStatus;
use App\Models\Attraction;
use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TourSessionSeeder extends Seeder
{
    /**
     * Seed tour sessions across yesterday, today, and the next 7 days.
     * Covers a realistic mix of: past sessions, today's sessions, and upcoming sessions.
     * Also includes inactive/blocked sessions to demonstrate that business rule.
     */
    public function run(): void
    {
        $attractions = Attraction::all()->keyBy('name');

        $today = Carbon::today();

        /**
         * Session schedule definitions.
         * Each entry: [attraction_name, day_offset, HH:MM start, max_capacity, status, current_pax]
         *
         * @var array<int, array{0: string, 1: int, 2: string, 3: int, 4: SessionStatus, 5: int}>
         */
        $schedule = [
            // --- Yesterday (past sessions, should not accept new allocations) ---
            ['Museum Tour',    -1, '09:00', 20, SessionStatus::Active,   18],
            ['Batik Workshop', -1, '10:00', 15, SessionStatus::Active,   15],
            ['Feeding Session', -1, '14:00', 30, SessionStatus::Active,   22],

            // --- Today ---
            ['Museum Tour',     0, '09:00', 20, SessionStatus::Active,    5],
            ['Museum Tour',     0, '11:00', 20, SessionStatus::Active,   12],
            ['Museum Tour',     0, '14:00', 20, SessionStatus::Active,    0],
            ['Batik Workshop',  0, '09:30', 15, SessionStatus::Active,   15], // full
            ['Batik Workshop',  0, '13:00', 15, SessionStatus::Active,    8],
            ['Feeding Session', 0, '10:00', 30, SessionStatus::Active,   10],
            ['Feeding Session', 0, '15:00', 30, SessionStatus::Inactive,  0], // blocked
            ['ATV Experience',  0, '08:00', 10, SessionStatus::Active,    4],
            ['ATV Experience',  0, '10:00', 10, SessionStatus::Active,   10], // full
            ['ATV Experience',  0, '13:00', 10, SessionStatus::Active,    2],
            ['Cooking Class',   0, '09:00', 12, SessionStatus::Active,    6],
            ['Nature Walk',     0, '07:30', 25, SessionStatus::Active,   20],
            ['Nature Walk',     0, '16:00', 25, SessionStatus::Active,    0],

            // --- Tomorrow ---
            ['Museum Tour',     1, '09:00', 20, SessionStatus::Active,    0],
            ['Museum Tour',     1, '11:00', 20, SessionStatus::Active,    3],
            ['Museum Tour',     1, '14:00', 20, SessionStatus::Active,    0],
            ['Batik Workshop',  1, '09:30', 15, SessionStatus::Active,    0],
            ['Batik Workshop',  1, '13:00', 15, SessionStatus::Active,    0],
            ['Feeding Session', 1, '10:00', 30, SessionStatus::Active,    0],
            ['Feeding Session', 1, '15:00', 30, SessionStatus::Active,    0],
            ['ATV Experience',  1, '08:00', 10, SessionStatus::Active,    0],
            ['ATV Experience',  1, '13:00', 10, SessionStatus::Active,    0],
            ['Cooking Class',   1, '09:00', 12, SessionStatus::Active,    0],
            ['Nature Walk',     1, '07:30', 25, SessionStatus::Active,    0],

            // --- Day +2 ---
            ['Museum Tour',     2, '09:00', 20, SessionStatus::Active,    0],
            ['Museum Tour',     2, '14:00', 20, SessionStatus::Active,    0],
            ['Batik Workshop',  2, '10:00', 15, SessionStatus::Active,    0],
            ['Feeding Session', 2, '10:00', 30, SessionStatus::Active,    0],
            ['ATV Experience',  2, '08:00', 10, SessionStatus::Inactive,  0], // maintenance
            ['ATV Experience',  2, '13:00', 10, SessionStatus::Active,    0],
            ['Cooking Class',   2, '09:00', 12, SessionStatus::Active,    0],
            ['Nature Walk',     2, '07:30', 25, SessionStatus::Active,    0],

            // --- Day +3 through +7 (lighter schedule) ---
            ['Museum Tour',     3, '09:00', 20, SessionStatus::Active,    0],
            ['Museum Tour',     3, '14:00', 20, SessionStatus::Active,    0],
            ['Batik Workshop',  3, '10:00', 15, SessionStatus::Active,    0],
            ['Feeding Session', 3, '10:00', 30, SessionStatus::Active,    0],
            ['ATV Experience',  3, '13:00', 10, SessionStatus::Active,    0],

            ['Museum Tour',     4, '09:00', 20, SessionStatus::Active,    0],
            ['Museum Tour',     4, '14:00', 20, SessionStatus::Active,    0],
            ['Batik Workshop',  4, '10:00', 15, SessionStatus::Active,    0],
            ['Feeding Session', 4, '10:00', 30, SessionStatus::Active,    0],
            ['Nature Walk',     4, '07:30', 25, SessionStatus::Active,    0],

            ['Museum Tour',     5, '09:00', 20, SessionStatus::Active,    0],
            ['Batik Workshop',  5, '10:00', 15, SessionStatus::Active,    0],
            ['Feeding Session', 5, '10:00', 30, SessionStatus::Active,    0],
            ['ATV Experience',  5, '08:00', 10, SessionStatus::Active,    0],
            ['Cooking Class',   5, '09:00', 12, SessionStatus::Active,    0],

            ['Museum Tour',     6, '09:00', 20, SessionStatus::Active,    0],
            ['Museum Tour',     6, '14:00', 20, SessionStatus::Active,    0],
            ['Batik Workshop',  6, '10:00', 15, SessionStatus::Active,    0],
            ['Feeding Session', 6, '10:00', 30, SessionStatus::Active,    0],
            ['Nature Walk',     6, '07:30', 25, SessionStatus::Active,    0],

            ['Museum Tour',     7, '09:00', 20, SessionStatus::Active,    0],
            ['Batik Workshop',  7, '10:00', 15, SessionStatus::Active,    0],
            ['Feeding Session', 7, '10:00', 30, SessionStatus::Active,    0],
            ['ATV Experience',  7, '13:00', 10, SessionStatus::Active,    0],
            ['Cooking Class',   7, '09:00', 12, SessionStatus::Active,    0],
        ];

        foreach ($schedule as [$attractionName, $dayOffset, $startHour, $maxCapacity, $status, $currentPax]) {
            $attraction = $attractions->get($attractionName);

            if (! $attraction) {
                continue;
            }

            [$hour, $minute] = explode(':', $startHour);
            $startTime = $today->copy()->addDays($dayOffset)->setHour((int) $hour)->setMinute((int) $minute)->setSecond(0);
            $endTime = $startTime->copy()->addMinutes($attraction->duration_minutes);

            Session::firstOrCreate(
                [
                    'attraction_id' => $attraction->id,
                    'start_time' => $startTime,
                ],
                [
                    'end_time' => $endTime,
                    'max_capacity' => $maxCapacity,
                    'current_pax' => $currentPax,
                    'status' => $status,
                ]
            );
        }
    }
}
