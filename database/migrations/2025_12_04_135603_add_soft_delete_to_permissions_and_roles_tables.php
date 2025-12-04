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
        $tableNames = config('permission.table_names');
        
        // Add columns to permissions table
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->tinyInteger('IsActive')->default(1)->after('guard_name');
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
            $table->index('IsActive');
        });
        
        // Add columns to roles table
        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->tinyInteger('IsActive')->default(1)->after('guard_name');
            $table->timestamp('deleted_at')->nullable()->after('updated_at');
            $table->index('IsActive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->dropIndex(['IsActive']);
            $table->dropColumn(['IsActive', 'deleted_at']);
        });
        
        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->dropIndex(['IsActive']);
            $table->dropColumn(['IsActive', 'deleted_at']);
        });
    }
};
