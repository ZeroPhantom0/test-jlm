<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Render the page shell. The first page of rows is fetched by JS,
     * but we also pass the first page server-side for instant paint /
     * no-JS fallback.
     */
    public function index(Request $request)
    {
        $order = $request->input('order', 'newest');

        $logs = AuditLog::query()
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->module($request->input('module'))
            ->betweenDates($request->input('from'), $request->input('to'))
            ->orderByDate($order)
            ->paginate(20)
            ->withQueryString();

        $modules = AuditLog::query()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('admin.audit-logs', compact('logs', 'modules', 'order'));
    }

    /**
     * JSON endpoint for AJAX filtering / sorting / pagination.
     * GET /admin/audit-logs/data
     */
    public function data(Request $request)
    {
        $order = $request->input('order', 'newest');

        $query = AuditLog::query()
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->module($request->input('module'))
            ->betweenDates($request->input('from'), $request->input('to'))
            ->orderByDate($order);

        $logs = $query->paginate(20)->withQueryString();

        return response()->json([
            'data' => $logs->getCollection()->map(fn (AuditLog $log) => $this->transform($log))->values(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'per_page'     => $logs->perPage(),
                'total'        => $logs->total(),
                'from'         => $logs->firstItem() ?? 0,
                'to'           => $logs->lastItem() ?? 0,
            ],
            'links' => [
                'prev' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = AuditLog::query()
            ->search($request->input('q'))
            ->status($request->input('status'))
            ->module($request->input('module'))
            ->betweenDates($request->input('from'), $request->input('to'))
            ->orderByDate($request->input('order', 'newest'));

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

    /**
     * Normalise one AuditLog into the shape the JS table renderer expects.
     * Mirrors the Blade markup so the row looks identical server- and
     * client-rendered.
     */
    private function transform(AuditLog $log): array
    {
        $statusMap = [
            'success' => ['bg-emerald-50 text-emerald-700 border-emerald-200', 'fa-solid fa-circle-check', 'Success'],
            'error'   => ['bg-rose-50 text-rose-700 border-rose-200', 'fa-solid fa-circle-xmark', 'Failed'],
            'warning' => ['bg-amber-50 text-amber-700 border-amber-200', 'fa-solid fa-triangle-exclamation', 'Warning'],
        ];

        $moduleColors = [
            'slate'  => 'bg-slate-100 text-slate-700',
            'green'  => 'bg-green-100 text-green-700',
            'blue'   => 'bg-blue-100 text-blue-700',
            'teal'   => 'bg-teal-100 text-teal-700',
            'indigo' => 'bg-indigo-100 text-indigo-700',
            'pink'   => 'bg-pink-100 text-pink-700',
            'amber'  => 'bg-amber-100 text-amber-700',
            'purple' => 'bg-violet-100 text-violet-700',
        ];

        $avatarColors = [
            'purple' => 'bg-violet-100 text-violet-600',
            'teal'   => 'bg-teal-100 text-teal-600',
            'gray'   => 'bg-slate-200 text-slate-500',
        ];

        $status = $statusMap[$log->status] ?? [
            'bg-slate-50 text-slate-700 border-slate-200',
            'fa-solid fa-circle-info',
            ucfirst($log->status ?? 'Unknown'),
        ];

        return [
            'id'           => $log->id,
            'date'         => $log->created_at?->format('M j, Y'),
            'time'         => $log->created_at?->format('g:i A'),
            'action'       => $log->action,
            'action_icon'  => $log->action_icon,
            'module'       => $log->module,
            'module_class' => $moduleColors[$log->module_color] ?? $moduleColors['slate'],
            'description'  => $log->description,
            'user_name'    => $log->user_name,
            'user_role'    => ucfirst($log->user_role),
            'initial'      => $log->initial,
            'avatar_class' => $avatarColors[$log->avatar_color] ?? $avatarColors['gray'],
            'status_class' => $status[0],
            'status_icon'  => $status[1],
            'status_text'  => $status[2],
        ];
    }
}