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
        Schema::table('users', function (Blueprint $table) {
            $table->string('dpi', 13)->unique()->after('email');
            $table->string('apellidos')->after('name');
            $table->date('fecha_nacimiento')->nullable()->after('apellidos');
            $table->string('telefono', 15)->nullable()->after('fecha_nacimiento');
            $table->string('foto')->nullable()->after('telefono');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dpi', 'apellidos', 'fecha_nacimiento', 'telefono', 'foto']);
        });
    }
};
