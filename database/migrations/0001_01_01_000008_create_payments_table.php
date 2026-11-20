<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id', 100)->unique();
            $table->foreignId('fine_id')->constrained('fines')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('gross_amount');
            $table->string('snap_token')->nullable();
            $table->string('payment_type', 50)->nullable();
            $table->string('transaction_id', 100)->nullable();
            $table->string('status', 30)->default(PaymentStatus::Pending->value);
            $table->json('payment_details')->nullable();
            $table->dateTime('settled_at')->nullable();
            $table->timestamps();

            $table->index(['fine_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
