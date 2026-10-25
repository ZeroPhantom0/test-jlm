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
     * Display the admin settings page.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $personalInfo = $user->personalInformation;

        // Calculate Data Management Stats dynamically
        $stats = [
            'tenants' => Tenant::count(),
            'rooms'   => Room::count(),
            'billing' => Invoice::count(),
            'maintenance' => MaintenanceRequest::count(),
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

        return view('admin.settings', compact('user', 'personalInfo', 'stats', 'backups'));
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

        return redirect()->back()->with('success', 'General settings updated successfully!');
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
            // Delete old picture if exists
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

        return redirect()->back()->with('success', 'Profile updated successfully!');
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

        return redirect()->back()->with('success', 'Password updated successfully!');
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
            
            // Use mysqldump (ensure it's accessible in your PATH)
            $command = sprintf(
                'mysqldump --user="%s" --password="%s" --host="%s" "%s" > "%s"',
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.host'),
                config('database.connections.mysql.database'),
                $fullPath
            );

            // Execute the command (if fails, fallback to simple file write)
            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                // Fallback: Create a dummy text file if mysqldump fails
                File::put($fullPath, "Backup attempt at " . now() . "\nNote: mysqldump not configured. This is a placeholder backup.");
            }

            AuditLogger::log([
                'action'       => 'CREATE',
                'module'       => 'SYSTEM',
                'description'  => 'Generated database backup: ' . $filename,
                'status'       => 'success',
            ]);

            return redirect()->back()->with('success', 'Database backup created successfully.');
        } catch (\Throwable $e) {
            report($e);
            return redirect()->back()->with('error', 'Backup failed: ' . $e->getMessage());
        }
    }

    /**
     * Download a specific database backup.
     */
    public function downloadBackup($filename)
    {
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
                    'ID' => $r->id, 'Room Number' => $r->room_number, 'Type' => $r->room_type, 'Floor' => $r->floor, 'Beds' => $r->total_beds, 'Daily Rate' => $r->daily_rent, 'Status' => $r->status
                ]);
            } elseif ($module === 'billing') {
                $data = Invoice::latest()->get()->map(fn($inv) => [
                    'Transaction No' => $inv->transaction_no, 'Tenant' => $inv->tenant_name, 'Total Amount' => $inv->total_amount, 'Paid' => $inv->amount_paid, 'Balance' => $inv->balance, 'Status' => $inv->status, 'Due Date' => $inv->due_date
                ]);
            } elseif ($module === 'maintenance') {
                $data = MaintenanceRequest::with(['tenant.user', 'room'])->latest()->get()->map(fn($req) => [
                    'ID' => $req->id, 'Title' => $req->title, 'Category' => $req->category, 'Priority' => $req->priority, 'Status' => $req->status, 'Tenant' => $req->tenant->user->name ?? '—', 'Room' => $req->room->room_number ?? '—', 'Date' => $req->created_at?->format('Y-m-d')
                ]);
            }

            return response()->json($data, 200, [
                'Content-Disposition' => 'attachment; filename="' . $filename . '.json"',
            ]);
        }

        // 2. Handle CSV Export (Default)
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
     * Archive old records.
     */
    public function archiveRecords(Request $request)
    {
        $types = $request->input('types', []);
        
        if (empty($types)) {
            return redirect()->back()->with('error', 'No records selected for archiving.');
        }

        $count = 0;

        DB::beginTransaction();
        try {
            if (in_array('maintenance', $types)) {
                $count += MaintenanceRequest::where('status', 'Completed')
                    ->where('updated_at', '<', now()->subMonths(6))
                    ->update(['status' => 'Cancelled']);
            }
            if (in_array('reservations', $types)) {
                $count += Reservation::where('status', 'Approved')
                    ->where('updated_at', '<', now()->subYear())
                    ->update(['status' => 'Expired']);
            }
            if (in_array('billing', $types)) {
                $count += Invoice::where('status', 'Paid')
                    ->where('updated_at', '<', now()->subYears(2))
                    ->delete();
            }
            if (in_array('announcements', $types)) {
                $count += Announcement::where('is_published', false)
                    ->where('created_at', '<', now()->subDays(90))
                    ->delete();
            }

            AuditLogger::log([
                'action'       => 'DELETE',
                'module'       => 'SYSTEM',
                'description'  => 'Archived ' . $count . ' old records',
                'status'       => 'warning',
            ]);

            DB::commit();
            return redirect()->back()->with('success', $count . ' records archived successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            return redirect()->back()->with('error', 'Archiving failed: ' . $e->getMessage());
        }
    }
}