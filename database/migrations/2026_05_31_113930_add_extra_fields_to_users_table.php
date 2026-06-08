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
            $table->string('phone')->nullable()->unique()->after('email');

            $table->enum('role', [
                'customer',
                'admin'
            ])->default('customer')->after('password');

            $table->boolean('is_active')->default(true)->after('role');

            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);

            $table->dropUnique(['phone']);

            $table->dropColumn([
                'phone',
                'role',
                'is_active'
            ]);
        });
    }
};
