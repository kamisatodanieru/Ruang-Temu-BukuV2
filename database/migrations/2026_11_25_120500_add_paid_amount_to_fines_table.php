<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->unsignedBigInteger('paid_amount')->default(0)->after('total_amount');
        });

        // Sinkronisasi data historis agar paid_amount dan total_amount kumulatif tetap konsisten
        $fines = DB::table('fines')->get();
        foreach ($fines as $fine) {
            $settledSum = (int) DB::table('payments')
                ->where('fine_id', $fine->id)
                ->where('status', 'paid')
                ->sum('gross_amount');

            if ($fine->status === 'paid') {
                $paidAmount = max((int) $fine->total_amount, $settledSum);
                $totalAmount = max((int) $fine->total_amount, $paidAmount);
            } else {
                $paidAmount = $settledSum;
                $totalAmount = $settledSum > 0
                    ? (int) $fine->total_amount + $settledSum
                    : (int) $fine->total_amount;
            }

            $rate = max(1, (int) $fine->fine_per_day);
            $overdueDays = max((int) $fine->overdue_days, (int) ceil($totalAmount / $rate));

            DB::table('fines')
                ->where('id', $fine->id)
                ->update([
                    'overdue_days' => $overdueDays,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('fines', function (Blueprint $table) {
            $table->dropColumn('paid_amount');
        });
    }
};
