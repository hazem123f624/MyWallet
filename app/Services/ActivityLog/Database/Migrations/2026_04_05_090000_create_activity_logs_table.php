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
        $tableName = config('activity_log.table_name', 'activity_logs');

        Schema::create($tableName, function (Blueprint $table) {
            $table->id();
            $table->string('log_type')->default('admin'); // e.g., 'admin', 'user', 'system'
            $table->string('action'); // e.g., 'created', 'updated', 'deleted', 'login', 'logout'
            $table->text('description')->nullable();
            // Polymorphic subject (the thing being acted upon)
            $table->nullableMorphs('subject');
            // Polymorphic causer (the person doing the action)
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable(); // Old vs New values, or extra data
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = config('activity_log.table_name', 'activity_logs');
        Schema::dropIfExists($tableName);
    }
};
