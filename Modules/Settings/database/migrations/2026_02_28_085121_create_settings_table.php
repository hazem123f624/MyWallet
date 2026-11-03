<?php

use App\Enums\ActiveStatus;
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
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->string('type')->index();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->json('data')->nullable();
            $table->foreignId('setting_id')->nullable()->constrained('settings')->cascadeOnDelete();
            $table->boolean('is_active')->default(ActiveStatus::Active)->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
