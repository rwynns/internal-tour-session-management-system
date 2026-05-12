<?php

namespace App\Http\Controllers;

use App\Http\Requests\CancelGuestAllocationRequest;
use App\Http\Requests\MoveGuestAllocationRequest;
use App\Http\Requests\StoreGuestAllocationRequest;
use App\Models\GuestAllocation;
use App\Models\Session;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierDashboardController extends Controller
{
    /**
     * Store a new guest allocation for the given session.
     *
     * Uses a pessimistic row-level lock to prevent race conditions when
     * multiple cashiers attempt to allocate the last available slot.
     */
    public function store(StoreGuestAllocationRequest $request, Session $session): RedirectResponse
    {
        DB::transaction(function () use ($request, $session): void {
            /** @var Session $lockedSession */
            $lockedSession = Session::lockForUpdate()->find($session->id);

            if ($lockedSession->current_pax + $request->integer('pax') > $lockedSession->max_capacity) {
                $remaining = $lockedSession->remainingCapacity();

                throw ValidationException::withMessages([
                    'pax' => "Insufficient capacity. Requested: {$request->integer('pax')}, Available: {$remaining}, Maximum: {$lockedSession->max_capacity}",
                ]);
            }

            $allocation = GuestAllocation::create([
                'session_id' => $lockedSession->id,
                'guest_name' => $request->string('guest_name'),
                'pax' => $request->integer('pax'),
                'source' => $request->string('source')->value() ?: null,
                'notes' => $request->string('notes')->value() ?: null,
                'status' => 'active',
                'allocated_by' => $request->user()->id,
            ]);

            $lockedSession->increment('current_pax', $request->integer('pax'));

            $lockedSession->load('attraction');
            ActivityLogger::log(
                'allocated',
                'GuestAllocation',
                $allocation->id,
                "Tamu \"{$allocation->guest_name}\" ({$allocation->pax} pax) dialokasikan ke sesi \"{$lockedSession->attraction?->name}\" ({$lockedSession->start_time->format('d M Y H:i')}).",
                ['session_id' => $lockedSession->id, 'pax' => $allocation->pax, 'source' => $allocation->source],
            );
        });

        return redirect()->back();
    }

    /**
     * Cancel a guest allocation and decrement the session's current pax.
     */
    public function cancel(CancelGuestAllocationRequest $request, GuestAllocation $allocation): RedirectResponse
    {
        DB::transaction(function () use ($allocation) {
            $lockedSession = Session::lockForUpdate()->find($allocation->session_id);

            $allocation->update(['status' => 'cancelled']);
            $lockedSession->decrement('current_pax', $allocation->pax);

            $lockedSession->load('attraction');
            ActivityLogger::log(
                'cancelled',
                'GuestAllocation',
                $allocation->id,
                "Alokasi tamu \"{$allocation->guest_name}\" ({$allocation->pax} pax) di sesi \"{$lockedSession->attraction?->name}\" dibatalkan.",
                ['session_id' => $lockedSession->id, 'pax' => $allocation->pax],
            );
        });

        return redirect()->back();
    }

    /**
     * Move a guest allocation to a different session.
     *
     * Locks both source and target session rows (ordered by ID to prevent deadlocks)
     * and atomically transfers the pax between sessions.
     */
    public function move(MoveGuestAllocationRequest $request, GuestAllocation $allocation): RedirectResponse
    {
        DB::transaction(function () use ($request, $allocation): void {
            $sourceId = $allocation->session_id;
            $targetId = $request->integer('target_session_id');

            // Lock in consistent ID order to prevent deadlocks
            $firstId = min($sourceId, $targetId);
            $secondId = max($sourceId, $targetId);

            $lockedFirst = Session::lockForUpdate()->find($firstId);
            $lockedSecond = Session::lockForUpdate()->find($secondId);

            $sourceSession = $sourceId === $firstId ? $lockedFirst : $lockedSecond;
            $targetSession = $targetId === $firstId ? $lockedFirst : $lockedSecond;

            if ($targetSession->current_pax + $allocation->pax > $targetSession->max_capacity) {
                $remaining = $targetSession->remainingCapacity();

                throw ValidationException::withMessages([
                    'target_session_id' => "Insufficient capacity. Requested: {$allocation->pax}, Available: {$remaining}, Maximum: {$targetSession->max_capacity}",
                ]);
            }

            $allocation->update(['session_id' => $targetSession->id]);
            $sourceSession->decrement('current_pax', $allocation->pax);
            $targetSession->increment('current_pax', $allocation->pax);

            $sourceSession->load('attraction');
            $targetSession->load('attraction');
            ActivityLogger::log(
                'moved',
                'GuestAllocation',
                $allocation->id,
                "Tamu \"{$allocation->guest_name}\" ({$allocation->pax} pax) dipindahkan dari \"{$sourceSession->attraction?->name}\" ke \"{$targetSession->attraction?->name}\".",
                [
                    'from_session_id' => $sourceSession->id,
                    'to_session_id' => $targetSession->id,
                    'pax' => $allocation->pax,
                ],
            );
        });

        return redirect()->back();
    }
}
