<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class ActivityLogController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', ActivityLog::class);
        $logs = ActivityLog::with('user')->latest('logged_at')->get();
        return response()->json($logs);
    }
}