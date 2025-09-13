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
        Schema::create('estaciones', function (Blueprint $table) {
            $table->id();

            // Información básica
            $table->string('nombre')->unique();
            $table->text('descripcion')->nullable();
            $table->string('ubicacion'); // Nombre del área/zona

            // Coordenadas geográficas
            $table->decimal('latitud', 10, 8); // Precisión de 8 decimales
            $table->decimal('longitud', 11, 8); // Precisión de 8 decimales

            // Tipo y características
            $table->enum('tipo_estacion', ['carga', 'descanso', 'seleccion']);
            $table->integer('capacidad_total')->default(0);
            $table->integer('capacidad_disponible')->default(0);

            // Estado operativo
            $table->enum('estado', ['activa', 'inactiva', 'mantenimiento'])->default('activa');

            // Información adicional
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->text('observaciones')->nullable();

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index(['tipo_estacion']);
            $table->index(['estado']);
            $table->index(['latitud', 'longitud']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estaciones');
    }
};
