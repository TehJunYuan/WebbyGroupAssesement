<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('seller_approval_status', ['pending', 'approved', 'rejected'])->nullable()->after('email_verified_at');
            $table->timestamp('seller_approved_at')->nullable()->after('seller_approval_status');
            $table->foreignId('seller_approved_by')->nullable()->after('seller_approved_at')->constrained('users')->onDelete('set null');
            $table->text('seller_rejection_reason')->nullable()->after('seller_approved_by');
            $table->timestamp('seller_applied_at')->nullable()->after('seller_rejection_reason');
            $table->index('seller_approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['seller_approved_by']);
            $table->dropIndex(['seller_approval_status']);
            $table->dropColumn([
                'seller_approval_status',
                'seller_approved_at',
                'seller_approved_by',
                'seller_rejection_reason',
                'seller_applied_at',
            ]);
        });
    }
};
