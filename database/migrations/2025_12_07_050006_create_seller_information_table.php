<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('seller_name')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->text('seller_address')->nullable();
            $table->text('phone_number')->nullable();
            $table->text('tax_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('InsertAt')->nullable();
            $table->foreignId('InsertUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('UpdateBy')->nullable();
            $table->foreignId('UpdateUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('DeleteBy')->nullable();
            $table->foreignId('DeleteUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->tinyInteger('IsActive')->default(1);
            
            $table->index('user_id');
            $table->index('IsActive');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_information');
    }
};

