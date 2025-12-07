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
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('stock_quantity')->default(0);
            $table->string('cover_image')->nullable();
            $table->foreignId('category_id')->constrained('book_categories')->onDelete('restrict');
            $table->foreignId('seller_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('InsertAt')->nullable();
            $table->foreignId('InsertUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('UpdateBy')->nullable();
            $table->foreignId('UpdateUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('DeleteBy')->nullable();
            $table->foreignId('DeleteUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->tinyInteger('IsActive')->default(1);
            
            $table->index('IsActive');
            $table->index('category_id');
            $table->index('seller_id');
            $table->index('title');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};

