<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSessionRequest;
use App\Http\Requests\UpdateSessionRequest;
use App\Models\Attraction;
use App\Models\Session;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    /**
     * Display a listing of sessions with sorting and filtering.
     */
    public function index(Request $request): Response
    {
        $sort = $request->input('sort', 'start_time');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = ['start_time', 'end_time', 'max_capacity', 'current_pax', 'status'];
        if (! in_array($sort, $allowedSorts, strict: true)) {
            $sort = 'start_time';
        }

        if (! in_array($direction, ['asc', 'desc'], strict: true)) {
            $direction = 'asc';
        }

        $query = Session::with(['attraction', 'activeAllocations']);

        if ($request->filled('attraction_id')) {
            $query->where('attraction_id', $request->attraction_id);
        }

        $sessions = $query->orderBy($sort, $direction)->paginate(15)->withQueryString();

        $attractions = Attraction::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('sessions/index', [
            'sessions' => $sessions,
            'attractions' => $attractions,
            'filters' => $request->only(['attraction_id', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new session.
     */
    public function create(): Response
    {
        return Inertia::render('sessions/create', [
            'attractions' => Attraction::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created session in storage.
     */
    public function store(StoreSessionRequest $request): RedirectResponse
    {
        Session::create($request->validated());

        ActivityLogger::log('created', 'Session', null, "Session baru untuk \"{$request->input('attraction_id')}\" dibuat.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session created successfully.']);

        return to_route('sessions.index');
    }

    /**
     * Show the form for editing the specified session.
     */
    public function edit(Session $session): Response
    {
        return Inertia::render('sessions/edit', [
            'session' => $session,
            'attractions' => Attraction::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified session in storage.
     */
    public function update(UpdateSessionRequest $request, Session $session): RedirectResponse
    {
        $session->update($request->validated());

        ActivityLogger::log('updated', 'Session', $session->id, "Session \"{$session->attraction?->name}\" ({$session->start_time->format('d M Y H:i')}) diperbarui.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session updated successfully.']);

        return to_route('sessions.index');
    }

    /**
     * Remove the specified session from storage.
     * Prevents deletion if the session has active guest allocations.
     */
    public function destroy(Session $session): RedirectResponse
    {
        $session->load('attraction');

        if ($session->activeAllocations()->exists()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Session tidak dapat dihapus karena masih memiliki alokasi tamu aktif.',
            ]);

            return back();
        }

        $label = "{$session->attraction?->name} ({$session->start_time->format('d M Y H:i')})";

        $session->delete();

        ActivityLogger::log('deleted', 'Session', $session->id, "Session \"{$label}\" dihapus.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Session deleted successfully.']);

        return to_route('sessions.index');
    }

    /**
     * Update the status of the specified session.
     */
    public function updateStatus(Request $request, Session $session): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $session->update(['status' => $request->status]);

        $status = $request->status === 'active' ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLogger::log('updated', 'Session', $session->id, "Session \"{$session->attraction?->name}\" {$status}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Status session berhasil diperbarui.']);

        return back();
    }
}
