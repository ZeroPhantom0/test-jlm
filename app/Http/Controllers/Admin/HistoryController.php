<?php
// app/Http/Controllers/Admin/HistoryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HistoryService;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __construct(
        protected HistoryService $history
    ) {}

    /**
     * Display the unified activity history.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category']);

        $events  = $this->history->build($filters);
        $metrics = $this->history->metrics();

        return view('admin.history', compact('events', 'metrics', 'filters'));
    }
}