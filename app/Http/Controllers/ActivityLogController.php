<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    /**
     * Display a paginated list of activity logs with optional filtering.
     */
    public function index(Request $request): Response
    {
        $action = $request->input('action');
        $subjectType = $request->input('subject_type');

        $logs = ActivityLog::query()
            ->with('user')
            ->when($action, fn ($q) => $q->where('action', $action))
            ->when($subjectType, fn ($q) => $q->where('subject_type', $subjectType))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('activity-logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['action', 'subject_type']),
        ]);
    }
}
