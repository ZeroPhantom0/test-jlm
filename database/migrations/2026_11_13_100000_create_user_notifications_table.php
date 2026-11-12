<?php
// database/migrations/2026_11_13_100000_create_user_notifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per recipient. Written when someone else does something
        // the recipient should know about — never for the actor themself.
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // recipient
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);            // reservation | payment | maintenance | room-change | stay-extension | announcement
            $table->string('title');
            $table->string('description', 500)->nullable();
            $table->string('icon', 80)->nullable();
            $table->string('color', 80)->nullable();
            $table->string('link')->nullable();
            $table->string('dedupe_key', 120)->nullable(); // same event never notifies twice
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'dedupe_key']);
            $table->index(['user_id', 'read_at', 'created_at']);
        });

        $this->backfillPendingForAdmins();
    }

    /**
     * The old bell showed everything currently pending. Carry those items
     * over so admins don't lose sight of requests still waiting on them.
     */
    private function backfillPendingForAdmins(): void
    {
        $adminIds = DB::table('users')->where('role', 'admin')->pluck('id');
        if ($adminIds->isEmpty()) return;

        $items = [];

        foreach (DB::table('reservations')->where('status', 'Pending')->get() as $r) {
            $items[] = ['reservation', "res-created-{$r->id}", 'New reservation request',
                "{$r->tenant_name} requested Room {$r->desired_room}", 'fa-regular fa-calendar-check',
                'text-emerald-600 bg-emerald-50', '/admin/reservations', $r->created_at];
        }
        foreach (DB::table('invoices')->where('status', 'Pending')->whereNull('deleted_at')->whereNotNull('receipt_path')->get() as $i) {
            $items[] = ['payment', "inv-submitted-{$i->id}", 'Payment submitted — ₱' . number_format((float) $i->amount_paid, 2),
                "{$i->tenant_name} · {$i->transaction_no}", 'fa-solid fa-money-bill-transfer',
                'text-emerald-600 bg-emerald-50', '/admin/billing', $i->updated_at ?? $i->created_at];
        }
        foreach (DB::table('maintenance_requests')->where('status', 'Pending')->get() as $m) {
            $items[] = ['maintenance', "mnt-created-{$m->id}", "New maintenance request ({$m->priority})",
                $m->title, 'fa-solid fa-wrench', 'text-rose-600 bg-rose-50', '/admin/services/maintenance', $m->created_at];
        }
        foreach (DB::table('room_change_requests')->where('status', 'Pending')->get() as $rc) {
            $items[] = ['room-change', "rc-created-{$rc->id}", 'New room change request',
                "{$rc->current_room} → {$rc->requested_room}", 'fa-solid fa-house', 'text-indigo-600 bg-indigo-50',
                '/admin/services/room-change', $rc->created_at];
        }
        foreach (DB::table('stay_extension_requests')->where('status', 'Pending')->get() as $se) {
            $items[] = ['stay-extension', "se-created-{$se->id}", 'New stay extension request',
                "+{$se->extension_days} day(s) · {$se->reference_no}", 'fa-regular fa-calendar-plus',
                'text-purple-600 bg-purple-50', '/admin/services/stay-extension', $se->created_at];
        }

        $rows = [];
        foreach ($adminIds as $adminId) {
            foreach ($items as [$type, $key, $title, $desc, $icon, $color, $link, $at]) {
                $rows[] = [
                    'user_id' => $adminId, 'actor_id' => null, 'type' => $type, 'title' => $title,
                    'description' => mb_substr((string) $desc, 0, 500), 'icon' => $icon, 'color' => $color,
                    'link' => $link, 'dedupe_key' => $key, 'read_at' => null,
                    'created_at' => $at ?? now(), 'updated_at' => $at ?? now(),
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('user_notifications')->insertOrIgnore($chunk);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
