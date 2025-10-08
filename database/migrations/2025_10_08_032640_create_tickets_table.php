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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_package_id')->nullable()->constrained()->casecadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->casecadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained()->casecadeOnDelete();
            $table->enum('status', ['paid', 'booked', 'refund', 'cancelled'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
