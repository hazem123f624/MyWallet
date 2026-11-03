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
        Schema::create('models_translate', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->foreignId('model_id')->constrained('models')->cascadeOnDelete();
            $table->string('lang')->index();
            $table->unique(['model_id', 'lang']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('models_translate');
    }
};
