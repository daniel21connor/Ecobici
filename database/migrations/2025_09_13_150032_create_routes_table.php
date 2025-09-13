<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Nombre de la ruta
            $table->string('start_point'); // Punto de inicio
            $table->string('end_point'); // Punto de destino
            $table->decimal('distance', 8, 2); // Distancia en km
            $table->decimal('co2_saved', 8, 2)->default(0); // CO2 reducido en kg
            $table->integer('green_points')->default(0); // Puntos verdes obtenidos
            $table->boolean('completed')->default(false); // Si la ruta fue completada
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('routes');
    }
};
