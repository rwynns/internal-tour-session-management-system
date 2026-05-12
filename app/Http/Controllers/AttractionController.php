<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttractionRequest;
use App\Http\Requests\UpdateAttractionRequest;
use App\Models\Attraction;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttractionController extends Controller
{
    /**
     * Display a listing of attractions with sorting and pagination.
     */
    public function index(Request $request): Response
    {
        $sort = $request->query('sort', 'name');
        $direction = $request->query('direction', 'asc');

        $allowedSorts = ['name', 'duration_minutes', 'is_active', 'created_at'];
        if (! in_array($sort, $allowedSorts, strict: true)) {
            $sort = 'name';
        }

        if (! in_array($direction, ['asc', 'desc'], strict: true)) {
            $direction = 'asc';
        }

        $attractions = Attraction::query()
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('attractions/index', [
            'attractions' => $attractions,
        ]);
    }

    /**
     * Show the form for creating a new attraction.
     */
    public function create(): Response
    {
        return Inertia::render('attractions/create');
    }

    /**
     * Store a newly created attraction in storage.
     */
    public function store(StoreAttractionRequest $request): RedirectResponse
    {
        Attraction::create($request->validated());

        ActivityLogger::log('created', 'Attraction', null, "Attraction \"{$request->name}\" dibuat.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attraction created successfully.']);

        return to_route('attractions.index');
    }

    /**
     * Show the form for editing the specified attraction.
     */
    public function edit(Attraction $attraction): Response
    {
        return Inertia::render('attractions/edit', [
            'attraction' => $attraction,
        ]);
    }

    /**
     * Update the specified attraction in storage.
     */
    public function update(UpdateAttractionRequest $request, Attraction $attraction): RedirectResponse
    {
        $attraction->update($request->validated());

        ActivityLogger::log('updated', 'Attraction', $attraction->id, "Attraction \"{$attraction->name}\" diperbarui.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attraction updated successfully.']);

        return to_route('attractions.index');
    }

    /**
     * Remove the specified attraction from storage.
     */
    public function destroy(Attraction $attraction): RedirectResponse
    {
        if ($attraction->sessions()->exists()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => 'Cannot delete attraction with existing sessions.']);

            return back();
        }

        $attraction->delete();

        ActivityLogger::log('deleted', 'Attraction', $attraction->id, "Attraction \"{$attraction->name}\" dihapus.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attraction deleted successfully.']);

        return to_route('attractions.index');
    }

    /**
     * Toggle the active status of the specified attraction.
     */
    public function toggleActive(Attraction $attraction): RedirectResponse
    {
        $attraction->update(['is_active' => ! $attraction->is_active]);

        $status = $attraction->is_active ? 'diaktifkan' : 'dinonaktifkan';
        ActivityLogger::log('updated', 'Attraction', $attraction->id, "Attraction \"{$attraction->name}\" {$status}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attraction status updated successfully.']);

        return back();
    }
}
