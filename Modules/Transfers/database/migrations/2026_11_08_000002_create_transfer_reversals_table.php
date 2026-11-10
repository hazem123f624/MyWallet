<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transfer_reversals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('transfer_id')->unique()->constrained('transfers')->cascadeOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 18, 2);
            $table->string('currency', 10);
            $table->string('provider_reference')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transfer_reversals');
    }
};
