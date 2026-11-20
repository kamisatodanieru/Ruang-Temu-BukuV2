<?php

use App\Enums\LoanStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('book_id')->constrained('books')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('requested_at');
            $table->dateTime('borrowed_at')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->dateTime('rejected_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('status', 20)->default(LoanStatus::Pending->value);
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'status']);
            $table->index(['book_id', 'status']);
            $table->index(['due_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
