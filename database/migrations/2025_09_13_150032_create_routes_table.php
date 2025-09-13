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

            // Coordenadas para el punto de inicio
            $table->decimal('start_latitude', 10, 8)->nullable();
            $table->decimal('start_longitude', 11, 8)->nullable();

            // Coordenadas para el punto de destino
            $table->decimal('end_latitude', 10, 8)->nullable();
            $table->decimal('end_longitude', 11, 8)->nullable();

            // Puntos de la ruta en formato JSON (array de coordenadas)
            $table->json('route_points')->nullable();

            // Información adicional de la ruta
            $table->integer('estimated_time')->nullable(); // en minutos
            $table->text('route_description')->nullable();
        });

    }

    public function down()
    {
        Schema::table('routes', function (Blueprint $table) {
            $table->dropColumn([
                'start_latitude',
                'start_longitude',
                'end_latitude',
                'end_longitude',
                'route_points',
                'estimated_time',
                'route_description'
            ]);
        });
    }
};
