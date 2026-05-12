<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Enums\UserRole;
use App\Models\Attraction;
use App\Models\GuestAllocation;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the dashboard based on the authenticated user's role.
     */
    public function __invoke(Request $request): Response
    {
        return match ($request->user()->role) {
            UserRole::Cashier => $this->cashierDashboard($request),
            default => $this->adminDashboard(),
        };
    }

    /**
     * Recreation Admin dashboard: occupancy overview for today.
     */
    private function adminDashboard(): Response
    {
        $today = Carbon::today();

        $todaySessions = Session::query()
            ->with('attraction')
            ->whereDate('start_time', $today)
            ->orderBy('start_time')
            ->get();

        $totalAttractionsActive = Attraction::where('is_active', true)->count();
        $totalGuestsToday = $todaySessions->sum('current_pax');
        $totalCapacityToday = $todaySessions->sum('max_capacity');
        $fullSessionsToday = $todaySessions->filter(fn ($s) => $s->current_pax >= $s->max_capacity)->count();

        $todayAllocations = GuestAllocation::query()
            ->whereDate('created_at', $today)
            ->where('status', 'active')
            ->count();

        $upcomingSessions = Session::query()
            ->with('attraction')
            ->where('start_time', '>', now())
            ->whereDate('start_time', $today)
            ->where('status', SessionStatus::Active)
            ->orderBy('start_time')
            ->get();

        return Inertia::render('dashboard', [
            'stats' => [
                'active_attractions' => $totalAttractionsActive,
                'sessions_today' => $todaySessions->count(),
                'guests_today' => $totalGuestsToday,
                'capacity_today' => $totalCapacityToday,
                'full_sessions' => $fullSessionsToday,
                'allocations_today' => $todayAllocations,
            ],
            'todaySessions' => $todaySessions,
            'upcomingSessions' => $upcomingSessions,
        ]);
    }

    /**
     * Cashier dashboard: session board with allocation capabilities.
     * Supports filtering by attraction and searching by guest name.
     */
    private function cashierDashboard(Request $request): Response
    {
        $attractionId = $request->input('attraction_id');
        $search = $request->input('search');

        $query = Session::query()
            ->with(['attraction', 'activeAllocations'])
            ->whereDate('start_time', '>=', today())
            ->orderBy('start_time');

        if ($attractionId) {
            $query->where('attraction_id', $attractionId);
        }

        if ($search) {
            $query->whereHas('activeAllocations', function ($q) use ($search) {
                $q->where('guest_name', 'like', "%{$search}%");
            });
        }

        $sessions = $query->get();
        $attractions = Attraction::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('cashier/dashboard', [
            'sessions' => $sessions,
            'attractions' => $attractions,
            'filters' => $request->only(['attraction_id', 'search']),
        ]);
    }
}
