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
        Schema::create('bikes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // Código único de la bicicleta
            $table->enum('type', ['tradicional', 'electrica']); // RF-05, RF-06
            $table->enum('status', ['disponible', 'en_uso', 'en_reparacion', 'mantenimiento']); // RF-05

            // CORRECCIÓN: Especificar el nombre correcto de la tabla
            $table->foreignId('estacion_id')->nullable()->constrained('estaciones')->nullOnDelete();

            $table->integer('battery_level')->nullable(); // Solo para eléctricas (0-100)
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 8, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('last_maintenance')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index(['estacion_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bikes');
    }
};
