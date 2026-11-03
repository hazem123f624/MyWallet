<?php

use App\Enums\ActiveStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\Documentations\Enums\DocumentationSections;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documentations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('section')->default(DocumentationSections::API_DOCUMENTATIONS->value);
            $table->string('category')->nullable();
            $table->integer('sorting')->default(1);
            $table->mediumText('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('headers')->nullable();
            $table->json('notes')->nullable();
            $table->json('query_params')->nullable();
            $table->json('body')->nullable();
            $table->string('method')->nullable();
            $table->mediumText('route')->nullable();
            $table->json('success_response')->nullable();
            $table->json('error_response')->nullable();
            $table->boolean('developer_tested')->default(\App\Services\Documentations\Enums\ApiTestedStatus::NO->value)->index();
            $table->boolean('tester_tested')->default(\App\Services\Documentations\Enums\ApiTestedStatus::NO->value)->index();
            $table->boolean('is_active')->default(ActiveStatus::Active->value)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentations');
    }
};
