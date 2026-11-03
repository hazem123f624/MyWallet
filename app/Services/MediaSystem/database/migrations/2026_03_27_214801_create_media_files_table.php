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
        Schema::create('media_files', function (Blueprint $blueprint) {
            $blueprint->bigIncrements('id');
            $blueprint->unsignedBigInteger('media_folder_id')->nullable();
            $blueprint->string('path');
            $blueprint->string('extension');
            $blueprint->string('mime_type');
            $blueprint->string('original_name')->nullable();
            $blueprint->unsignedBigInteger('size')->nullable();
            $blueprint->json('variants')->nullable();
            $blueprint->timestamps();

            $blueprint->foreign('media_folder_id')
                ->references('id')
                ->on('media_folders')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
