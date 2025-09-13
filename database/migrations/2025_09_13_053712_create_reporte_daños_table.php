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
        Schema::create('damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bike_id')->constrained()->cascadeOnDelete();
            $table->text('description'); // RF-08
            $table->enum('severity', ['leve', 'moderado', 'grave'])->default('leve');
            $table->enum('status', ['pendiente', 'en_revision', 'en_reparacion', 'resuelto'])->default('pendiente');
            $table->json('photos')->nullable(); // Array de rutas de fotos
            $table->foreignId('resolved_by')->nullable()->constrained('users'); // Admin que resolvió
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['bike_id', 'status']);
            $table->index(['user_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_reports');
    }
};
