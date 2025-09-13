<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_distance', 10, 2)->default(0); // Total de km recorridos
            $table->decimal('total_co2_saved', 10, 2)->default(0); // Total CO2 ahorrado
            $table->integer('total_green_points')->default(0); // Total puntos verdes
            $table->integer('completed_routes')->default(0); // Rutas completadas
            $table->integer('ranking_position')->nullable(); // Posición en el ranking
            $table->date('last_updated')->default(now()); // Última actualización
            $table->timestamps();

            // Índices para optimización
            $table->index('total_distance');
            $table->index('ranking_position');
            $table->unique('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_rankings');
    }
};
