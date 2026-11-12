<?php
// app/Http/Controllers/Admin/HistoryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\HistoryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Load every event; category / search filtering + pagination run client-side
        // so switching filters never hides records the server already dropped.
        $events  = $this->history->build();
        $metrics = $this->history->metrics();

        return view('admin.history', compact('events', 'metrics', 'filters'));
    }

    /**
     * Export the filtered history as a CSV file.
     */
    public function export(Request $request): StreamedResponse
    {
        // Get the exact same filters the user selected on the UI
        $filters = $request->only(['search', 'category']);

        // Fetch the filtered events
        $events = $this->history->build($filters);

        $filename = 'history-' . ($filters['category'] ?? 'all') . '-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($events) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM so Excel decodes ₱ correctly
            fwrite($handle, "\xEF\xBB\xBF");

            // Add CSV Headers
            fputcsv($handle, [
                'Date',
                'Category',
                'Title',
                'Tenant',
                'Room',
                'Description',
                'Status',
                'Amount'
            ]);

            // Loop through the events and write rows
            foreach ($events as $event) {
                fputcsv($handle, [
                    $event['date'],
                    ucfirst(str_replace('-', ' ', $event['category'])),
                    $event['title'],
                    $event['tenant'],
                    $event['room'],
                    $event['desc'],
                    ucfirst($event['status']),
                    $event['amount'] ?? '—',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
