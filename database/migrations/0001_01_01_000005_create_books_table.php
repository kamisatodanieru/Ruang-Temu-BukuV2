<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->string('title', 255)->index();
            $table->string('author', 150)->index();
            $table->string('publisher', 150)->nullable();
            $table->string('isbn', 30)->nullable()->unique();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->unsignedInteger('total_stock');
            $table->unsignedInteger('available_stock')->index();
            $table->string('cover_path', 255)->nullable();
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
