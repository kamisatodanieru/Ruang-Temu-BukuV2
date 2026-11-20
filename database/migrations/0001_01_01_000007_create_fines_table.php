<?php

use App\Enums\FineStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->unique()->constrained('loans')->restrictOnDelete();
            $table->foreignId('member_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('overdue_days');
            $table->unsignedInteger('fine_per_day');
            $table->unsignedBigInteger('total_amount');
            $table->string('status', 30)->default(FineStatus::Unpaid->value);
            $table->dateTime('paid_at')->nullable();
            $table->foreignId('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('waived_reason')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fines');
    }
};
