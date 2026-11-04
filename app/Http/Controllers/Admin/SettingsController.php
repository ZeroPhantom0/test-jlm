<?php
// app/Http/Controllers/Admin/SettingsController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\MaintenanceRequest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingsController extends Controller
{
    /**
     * Redirect back to settings page preserving the active tab.
     */
    protected function backToTab(string $tab, string $key = 'success', string $message = '')
    {
        return redirect()
            ->route('admin.settings')
            ->withFragment('tab=' . $tab)
            ->with($key, $message);
    }

    /**
     * Display the admin settings page.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $personalInfo = $user->personalInformation;

        // Calculate Data Management Stats dynamically
        $stats = [
            'tenants'     => Tenant::count(),
            'rooms'       => Room::count(),
            'billing'     => Invoice::count(),
            'maintenance' => MaintenanceRequest::count(),
        ];

        // Real archive candidate counts (for the archive checkboxes)
        $archiveCandidates = [
            'maintenance' => MaintenanceRequest::where('status', 'Completed')
                                ->where('updated_at', '<', now()->subMonths(6))
                                ->count(),
            'reservations' => Reservation::where('status', 'Approved')
                                ->where('updated_at', '<', now()->subYear())
                                ->count(),
            'billing'      => Invoice::where('status', 'Paid')
                                ->where('updated_at', '<', now()->subYears(2))
                                ->count(),
            'announcements' => Announcement::where('is_published', false)
                                ->where('created_at', '<', now()->subDays(90))
                                ->count(),
        ];

        // Scan for backup files in the storage
        $backupPath = storage_path('app/backups');
        $backups = [];

        if (File::exists($backupPath)) {
            $files = File::files($backupPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'sql') {
                    $backups[] = [
                        'name' => $file->getFilename(),
                        'date' => date('M d, Y', $file->getMTime()),
                        'time' => date('H:i', $file->getMTime()),
                        'size' => round($file->getSize() / 1048576, 2) . ' MB',
                    ];
                }
            }
            // Sort newest first
            usort($backups, fn($a, $b) => strcmp($b['name'], $a['name']));
        }

        return view('admin.settings', compact('user', 'personalInfo', 'stats', 'backups', 'archiveCandidates'));
    }

    /**
     * Update the admin's general/owner info.
     */
    public function updateGeneral(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update($validated);

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'SETTINGS',
            'description'  => 'Updated General Settings for ' . $user->name,
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        return $this->backToTab('general', 'success', 'General settings updated successfully!');
    }

    /**
     * Update the admin's profile (Personal Information).
     */
    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone'           => ['nullable', 'string', 'max:20'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        // Update User account
        $user->update([
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // Update or create Personal Information record
        /** @var \App\Models\PersonalInformation $personalInfo */
        $personalInfo = $user->personalInformation()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'full_name'      => $user->name,
                'email'          => $user->email,
                'contact_number' => $user->phone,
            ]
        );

        $personalInfo->full_name = $validated['name'];
        $personalInfo->email = $validated['email'];
        $personalInfo->contact_number = $validated['phone'] ?? null;

        // Handle Profile Picture Upload
        if ($request->hasFile('profile_picture')) {
            if ($personalInfo->profile_picture && Storage::disk('public')->exists($personalInfo->profile_picture)) {
                Storage::disk('public')->delete($personalInfo->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profiles/' . $user->id, 'public');
            $personalInfo->profile_picture = $path;
        }

        $personalInfo->save();

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'SETTINGS',
            'description'  => 'Updated Admin Profile settings',
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        return $this->backToTab('profile', 'success', 'Profile updated successfully!');
    }

    /**
     * Update the admin's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        AuditLogger::log([
            'action'       => 'UPDATE',
            'module'       => 'AUTHENTICATION',
            'description'  => 'Changed Admin account password',
            'subject_type' => User::class,
            'subject_id'   => $user->id,
        ]);

        return $this->backToTab('security', 'success', 'Password updated successfully!');
    }

    /**
     * Create a database backup snapshot.
     */
    public function backupDatabase()
    {
        try {
            $filename = 'backup_' . now()->format('Ymd_His') . '.sql';
            $path = storage_path('app/backups');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $fullPath = $path . '/' . $filename;

            // Build command safely (escapeshellarg handles special chars in password)
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s 2>&1',
                escapeshellarg(config('database.connections.mysql.username')),
                escapeshellarg(config('database.connections.mysql.password')),
                escapeshellarg(config('database.connections.mysql.host')),
                escapeshellarg(config('database.connections.mysql.database')),
                escapeshellarg($fullPath)
            );

            exec($command, $output, $returnVar);

            // If mysqldump missing or failed → write a placeholder AND warn user
            if ($returnVar !== 0 || !File::exists($fullPath) || File::size($fullPath) < 100) {
                File::put(
                    $fullPath,
                    "-- Backup attempted at " . now() . "\n-- mysqldump unavailable or failed.\n-- Please install mysqldump and ensure it's in PATH.\n"
                );

                AuditLogger::log([
                    'action'      => 'CREATE',
                    'module'      => 'SYSTEM',
                    'description' => 'Backup fallback written (mysqldump unavailable): ' . $filename,
                    'status'      => 'warning',
                ]);

                return $this->backToTab(
                    'data',
                    'error',
                    'mysqldump is not available on this server. A placeholder backup was created — please configure mysqldump for real backups.'
                );
            }

            AuditLogger::log([
                'action'       => 'CREATE',
                'module'       => 'SYSTEM',
                'description'  => 'Generated database backup: ' . $filename,
                'status'       => 'success',
            ]);

            return $this->backToTab('data', 'success', 'Database backup created successfully.');
        } catch (\Throwable $e) {
            report($e);
            return $this->backToTab('data', 'error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a specific database backup.
     */
    public function downloadBackup($filename)
    {
        // Security: strip any path traversal attempts
        $filename = basename($filename);
        $path = storage_path('app/backups/' . $filename);

        if (!File::exists($path)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($path);
    }

    /**
     * Export system data (supports CSV, JSON, XLSX placeholder).
     */
    public function exportData(Request $request)
    {
        $module = $request->query('module', 'tenants');
        $format = $request->query('format', 'csv');

        // Defense-in-depth: reject unknown module/format at the controller level
        // (route-level whereIn does NOT validate query strings, only route params)
        abort_unless(in_array($module, ['tenants', 'rooms', 'billing', 'maintenance'], true), 404);
        abort_unless(in_array($format, ['csv', 'xlsx', 'json'], true), 404);

        $filename = 'export-' . $module . '-' . now()->format('Y-m-d_His');

        AuditLogger::log([
            'action'       => 'EXPORT',
            'module'       => 'SYSTEM',
            'description'  => 'Exported ' . ucfirst($module) . ' data as ' . strtoupper($format),
            'status'       => 'success',
        ]);

        // 1. Handle JSON Export
        if ($format === 'json') {
            $data = [];
            if ($module === 'tenants') {
                $data = Tenant::with(['user', 'room'])->get()->map(fn($t) => [
                    'ID' => $t->id,
                    'Name' => $t->user->name ?? '—',
                    'Email' => $t->user->email ?? '—',
                    'Phone' => $t->user->phone ?? '—',
                    'Room' => $t->room->room_number ?? '—',
                    'Status' => $t->status,
                    'Joined' => $t->created_at?->format('Y-m-d'),
                ]);
            } elseif ($module === 'rooms') {
                $data = Room::all()->map(fn($r) => [
                    'ID' => $r->id,
                    'Room Number' => $r->room_number,
                    'Type' => $r->room_type,
                    'Floor' => $r->floor,
                    'Beds' => $r->total_beds,
                    'Daily Rate' => $r->daily_rent,
                    'Status' => $r->status
                ]);
            } elseif ($module === 'billing') {
                $data = Invoice::latest()->get()->map(fn($inv) => [
                    'Transaction No' => $inv->transaction_no,
                    'Tenant' => $inv->tenant_name,
                    'Total Amount' => $inv->total_amount,
                    'Paid' => $inv->amount_paid,
                    'Balance' => $inv->balance,
                    'Status' => $inv->status,
                    'Due Date' => $inv->due_date
                ]);
            } elseif ($module === 'maintenance') {
                $data = MaintenanceRequest::with(['tenant.user', 'room'])->latest()->get()->map(fn($req) => [
                    'ID' => $req->id,
                    'Title' => $req->title,
                    'Category' => $req->category,
                    'Priority' => $req->priority,
                    'Status' => $req->status,
                    'Tenant' => $req->tenant->user->name ?? '—',
                    'Room' => $req->room->room_number ?? '—',
                    'Date' => $req->created_at?->format('Y-m-d')
                ]);
            }

            return response()->json($data, 200, [
                'Content-Disposition' => 'attachment; filename="' . $filename . '.json"',
            ]);
        }

        // 2. Handle CSV Export (Default) — xlsx falls through to CSV for now
        $filename .= '.csv';

        return response()->streamDownload(function () use ($module) {
            $handle = fopen('php://output', 'w');

            if ($module === 'tenants') {
                fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Room', 'Status', 'Joined']);
                $tenants = Tenant::with(['user', 'room'])->get();
                foreach ($tenants as $t) {
                    fputcsv($handle, [
                        $t->id,
                        $t->user->name ?? '—',
                        $t->user->email ?? '—',
                        $t->user->phone ?? '—',
                        $t->room->room_number ?? '—',
                        $t->status,
                        $t->created_at?->format('Y-m-d'),
                    ]);
                }
            } elseif ($module === 'rooms') {
                fputcsv($handle, ['ID', 'Room Number', 'Type', 'Floor', 'Beds', 'Daily Rate', 'Status']);
                $rooms = Room::all();
                foreach ($rooms as $r) {
                    fputcsv($handle, [
                        $r->id, $r->room_number, $r->room_type, $r->floor, $r->total_beds, $r->daily_rent, $r->status
                    ]);
                }
            } elseif ($module === 'billing') {
                fputcsv($handle, ['Transaction No', 'Tenant', 'Total Amount', 'Paid', 'Balance', 'Status', 'Due Date']);
                $invoices = Invoice::latest()->get();
                foreach ($invoices as $inv) {
                    fputcsv($handle, [
                        $inv->transaction_no, $inv->tenant_name, $inv->total_amount, $inv->amount_paid, $inv->balance, $inv->status, $inv->due_date
                    ]);
                }
            } elseif ($module === 'maintenance') {
                fputcsv($handle, ['ID', 'Title', 'Category', 'Priority', 'Status', 'Tenant', 'Room', 'Date']);
                $requests = MaintenanceRequest::with(['tenant.user', 'room'])->latest()->get();
                foreach ($requests as $req) {
                    fputcsv($handle, [
                        $req->id, $req->title, $req->category, $req->priority, $req->status,
                        $req->tenant->user->name ?? '—',
                        $req->room->room_number ?? '—',
                        $req->created_at?->format('Y-m-d'),
                    ]);
                }
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Archive old records (soft-mark instead of hard-delete).
     */
    public function archiveRecords(Request $request)
    {
        $types = $request->input('types', []);

        if (empty($types)) {
            return $this->backToTab('data', 'error', 'No records selected for archiving.');
        }

        $count = 0;

        DB::beginTransaction();
        try {
            if (in_array('maintenance', $types, true)) {
                $count += MaintenanceRequest::where('status', 'Completed')
                    ->where('updated_at', '<', now()->subMonths(6))
                    ->update(['status' => 'Archived']);
            }
            if (in_array('reservations', $types, true)) {
                $count += Reservation::where('status', 'Approved')
                    ->where('updated_at', '<', now()->subYear())
                    ->update(['status' => 'Archived']);
            }
            if (in_array('billing', $types, true)) {
                // Soft-archive: mark status instead of deleting
                $count += Invoice::where('status', 'Paid')
                    ->where('updated_at', '<', now()->subYears(2))
                    ->update(['status' => 'Archived']);
            }
            if (in_array('announcements', $types, true)) {
                // Soft-archive: unpublish (adjust to is_archived column if added)
                $count += Announcement::where('is_published', false)
                    ->where('created_at', '<', now()->subDays(90))
                    ->update(['is_published' => false]);
            }

            AuditLogger::log([
                'action'       => 'ARCHIVE',
                'module'       => 'SYSTEM',
                'description'  => 'Archived ' . $count . ' old records',
                'status'       => 'success',
            ]);

            DB::commit();
            return $this->backToTab('data', 'success', $count . ' records archived successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return $this->backToTab('data', 'error', 'Archiving failed: ' . $e->getMessage());
        }
    }
}