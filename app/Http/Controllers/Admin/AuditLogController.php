<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->module($request->input('module'))
            ->betweenDates($request->input('from'), $request->input('to'))
            ->latest('created_at');

        $logs = $query->paginate(20)->withQueryString();

        $modules = AuditLog::query()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('admin.audit-logs', compact('logs', 'modules'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = AuditLog::query()
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->module($request->input('module'))
            ->betweenDates($request->input('from'), $request->input('to'))
            ->latest('created_at');

        $filename = 'audit-logs-' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Timestamp', 'Action', 'Module', 'Description', 'User', 'Role', 'Status', 'IP Address',
            ]);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->action,
                        $log->module,
                        $log->description,
                        $log->user_name,
                        $log->user_role,
                        $log->status,
                        $log->ip_address,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}