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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('action', 50)->index(); // login, logout, create, update, delete, status_change, etc.
            $table->string('module', 50)->index(); // auth, users, roles, branches, pos, inventory, finance, hr, settings
            $table->unsignedBigInteger('record_id')->nullable()->index();
            $table->string('record_type', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('category', 50)->default('general');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, boolean, integer, json
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('audit_logs');
    }
};
