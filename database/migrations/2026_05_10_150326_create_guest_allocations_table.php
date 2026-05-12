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
        Schema::create('guest_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('tour_sessions')->cascadeOnDelete();
            $table->string('guest_name', 255);
            $table->unsignedInteger('pax')->default(1);
            $table->string('source', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('allocated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['session_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guest_allocations');
    }
};
