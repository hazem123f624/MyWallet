<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Denormalised rollup of the folder's contents: the sum of size (bytes) of the
 * current (non-trashed) media_files inside it. Maintained automatically by
 * MediaFileObserver so storage usage can be read straight off the folder
 * instead of scanning every file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_folders', function (Blueprint $table): void {
            $table->unsignedBigInteger('size')->default(0)->after('type');
        });

        // Backfill existing folders from their current files.
        DB::statement(
            'UPDATE media_folders mf SET size = COALESCE(('.
            'SELECT SUM(f.size) FROM media_files f '.
            'WHERE f.media_folder_id = mf.id AND f.deleted_at IS NULL'.
            '), 0)'
        );
    }

    public function down(): void
    {
        Schema::table('media_folders', function (Blueprint $table): void {
            $table->dropColumn('size');
        });
    }
};
