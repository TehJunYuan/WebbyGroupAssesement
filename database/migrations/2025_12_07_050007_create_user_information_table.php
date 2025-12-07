<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->string('full_name')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->text('address')->nullable();
            $table->text('phone_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->foreignId('gender_id')->nullable()->constrained('genders')->onDelete('set null');
            $table->timestamp('InsertAt')->nullable();
            $table->foreignId('InsertUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('UpdateBy')->nullable();
            $table->foreignId('UpdateUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('DeleteBy')->nullable();
            $table->foreignId('DeleteUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->tinyInteger('IsActive')->default(1);
            
            $table->index('user_id');
            $table->index('gender_id');
            $table->index('IsActive');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_information');
    }
};

