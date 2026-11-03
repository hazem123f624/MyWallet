<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Allow OTP records to be created without a parent (otpable) model,
     * e.g. registration flows that store the OTP keyed only by identifier.
     */
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->string('otpable_type')->nullable()->change();
            $table->unsignedBigInteger('otpable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->string('otpable_type')->nullable(false)->change();
            $table->unsignedBigInteger('otpable_id')->nullable(false)->change();
        });
    }
};
