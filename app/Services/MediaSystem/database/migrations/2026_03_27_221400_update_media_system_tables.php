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
        Schema::table('media_folders', function (Blueprint $table) {
            if (! Schema::hasColumn('media_folders', 'deleted_at')) {
                $table->softDeletes();
            }

            // Performance Indexes
            $table->index('name');
        });

        Schema::table('media_files', function (Blueprint $table) {
            if (! Schema::hasColumn('media_files', 'disk')) {
                $table->string('disk')->nullable()->after('media_folder_id');
            }
            if (! Schema::hasColumn('media_files', 'visibility')) {
                $table->enum('visibility', ['public', 'private'])->default('public')->after('disk');
            }
            if (! Schema::hasColumn('media_files', 'tags')) {
                $table->json('tags')->nullable()->after('variants');
            }
            if (! Schema::hasColumn('media_files', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('tags');
            }
            if (! Schema::hasColumn('media_files', 'version')) {
                $table->integer('version')->default(1)->after('parent_id');
            }
            if (! Schema::hasColumn('media_files', 'is_current')) {
                $table->boolean('is_current')->default(true)->after('version');
            }
            if (! Schema::hasColumn('media_files', 'deleted_at')) {
                $table->softDeletes();
            }
//
//            // Performance Indexes
//            $table->index('disk');
//            $table->index('visibility');
//            $table->index('parent_id');
//            $table->index('is_current');
//            $table->index('path');
//            $table->index('extension');
//            $table->index('mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_folders', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex('name');
        });

        Schema::table('media_files', function (Blueprint $table) {
            $table->dropColumn(['disk', 'visibility', 'tags', 'parent_id', 'version', 'is_current']);
            $table->dropSoftDeletes();
            // $table->dropIndex(['disk', 'visibility', 'parent_id', 'is_current', 'path', 'extension', 'mime_type', 'original_name']);
        });
    }
};
