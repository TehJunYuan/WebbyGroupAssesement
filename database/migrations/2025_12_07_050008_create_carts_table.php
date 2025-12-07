<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->timestamp('InsertAt')->nullable();
            $table->foreignId('InsertUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('UpdateBy')->nullable();
            $table->foreignId('UpdateUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('DeleteBy')->nullable();
            $table->foreignId('DeleteUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->tinyInteger('IsActive')->default(1);
            
            $table->index('user_id');
            $table->index('book_id');
            $table->index('IsActive');
            $table->unique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};

