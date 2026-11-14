<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * pending_amount = a payment the tenant submitted that the admin has not
 * verified yet. It is NOT part of amount_paid until it's verified, so a
 * second (remaining-balance) payment no longer shows as double in Billing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'pending_amount')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->decimal('pending_amount', 12, 2)->default(0)->after('amount_paid');
            });
        }

        // Fix submissions made with the old logic: the remaining balance was
        // added to amount_paid at submit time, so paid + balance > total.
        // Move that unverified amount back out of amount_paid.
        DB::table('invoices')
            ->where('status', 'Pending')
            ->whereRaw('amount_paid + balance > total_amount + 0.005')
            ->where('balance', '>', 0)
            ->update([
                'pending_amount' => DB::raw('balance'),
                'amount_paid'    => DB::raw('GREATEST(0, amount_paid - balance)'),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'pending_amount')) {
            DB::table('invoices')->where('pending_amount', '>', 0)
                ->update(['amount_paid' => DB::raw('amount_paid + pending_amount')]);

            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('pending_amount');
            });
        }
    }
};
