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
            $table->string('username', 50)->unique()->nullable()->after('name');
            $table->string('phone', 30)->nullable()->after('email');
            $table->foreignId('branch_id')->nullable()->after('password')->constrained('branches')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('branch_id')->constrained('departments')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('department_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('user_branch_access', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->primary(['user_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_branch_access');
        Schema::dropIfExists('user_roles');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'username',
                'phone',
                'branch_id',
                'department_id',
                'is_active',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
