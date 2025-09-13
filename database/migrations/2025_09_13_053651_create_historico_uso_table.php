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
        Schema::create('bike_usage_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bike_id')->constrained()->cascadeOnDelete();
            $table->foreignId('start_station_id')->constrained('stations');
            $table->foreignId('end_station_id')->nullable()->constrained('stations');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_minutes')->nullable(); // Calculado automáticamente
            $table->decimal('distance_km', 8, 2)->nullable(); // Opcional para futuras implementaciones
            $table->enum('status', ['activo', 'completado', 'cancelado']); // RF-07
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'start_time']);
            $table->index(['bike_id', 'start_time']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bike_usage_history');
    }
};
