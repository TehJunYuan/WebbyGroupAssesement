<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->text('shipping_address')->nullable();
            $table->timestamp('InsertAt')->nullable();
            $table->foreignId('InsertUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('UpdateBy')->nullable();
            $table->foreignId('UpdateUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('DeleteBy')->nullable();
            $table->foreignId('DeleteUserId')->nullable()->constrained('users')->onDelete('set null');
            $table->tinyInteger('IsActive')->default(1);
            
            $table->index('user_id');
            $table->index('payment_status');
            $table->index('IsActive');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

