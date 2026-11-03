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
        Schema::create('media_folders', function (Blueprint $blueprint) {
            $blueprint->bigIncrements('id');
            $blueprint->string('name');
            $blueprint->string('category')->default('general');
            $blueprint->unsignedBigInteger('related_id')->nullable();
            $blueprint->string('related_type')->nullable();
            $blueprint->timestamps();

            $blueprint->index(['related_id', 'related_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_folders');
    }
};
