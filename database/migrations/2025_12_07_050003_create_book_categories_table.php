<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->text('Description')->nullable();
            $table->timestamp('InsertAt')->nullable();
            $table->foreignId('InsertUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('UpdateBy')->nullable();
            $table->foreignId('UpdateUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('DeleteBy')->nullable();
            $table->foreignId('DeleteUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->tinyInteger('IsActive')->default(1);
            
            $table->index('IsActive');
            $table->index('name');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_categories');
    }
};

