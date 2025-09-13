<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bike;
use App\Models\Estacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar la tabla antes de insertar (opcional)
        // Bike::truncate();

        // Obtener todas las estaciones disponibles
        $estaciones = Estacion::all();

        if ($estaciones->isEmpty()) {
            $this->command->warn('No hay estaciones en la base de datos. Asegúrate de ejecutar el EstacionSeeder primero.');
            return;
        }

        $bikes = [];
        $batchSize = 100; // Insertar en lotes para mejor rendimiento

        // Generar bicicletas tradicionales
        $this->command->info('Generando bicicletas tradicionales...');
        for ($i = 1; $i <= 150; $i++) {
            $bikes[] = $this->generateBikeData('tradicional', $i, $estaciones);
        }

        // Generar bicicletas eléctricas
        $this->command->info('Generando bicicletas eléctricas...');
        for ($i = 151; $i <= 200; $i++) {
            $bikes[] = $this->generateBikeData('electrica', $i, $estaciones);
        }

        // Insertar en lotes
        $chunks = array_chunk($bikes, $batchSize);
        foreach ($chunks as $chunk) {
            DB::table('bikes')->insert($chunk);
        }

        $this->command->info('Se han creado ' . count($bikes) . ' bicicletas exitosamente.');

        // Mostrar estadísticas
        $this->showStatistics();
    }

    /**
     * Generar datos para una bicicleta
     */
    private function generateBikeData(string $type, int $index, $estaciones): array
    {
        $isElectric = $type === 'electrica';

        // Generar código único
        $prefix = $isElectric ? 'BE' : 'BT'; // BE = Bike Electric, BT = Bike Traditional
        $code = $prefix . str_pad($index, 4, '0', STR_PAD_LEFT);

        // Estados posibles con probabilidades realistas
        $statusOptions = [
            'disponible' => 70,    // 70% disponibles
            'en_uso' => 15,        // 15% en uso
            'en_reparacion' => 10, // 10% en reparación
            'mantenimiento' => 5   // 5% en mantenimiento
        ];

        $status = $this->getWeightedRandomValue($statusOptions);

        // Asignar estación (null si está en reparación o mantenimiento)
        $estacionId = null;
        if (in_array($status, ['disponible', 'en_uso'])) {
            $estacionId = $estaciones->random()->id;
        }

        // Nivel de batería solo para bicicletas eléctricas
        $batteryLevel = null;
        if ($isElectric) {
            if ($status === 'disponible') {
                // Bicicletas disponibles tienen mejor carga
                $batteryLevel = fake()->numberBetween(30, 100);
            } elseif ($status === 'en_uso') {
                // Las que están en uso tienen carga variable
                $batteryLevel = fake()->numberBetween(20, 90);
            } else {
                // Las que están en reparación/mantenimiento pueden tener carga baja
                $batteryLevel = fake()->numberBetween(0, 60);
            }
        }

        // Fechas realistas
        $purchaseDate = fake()->dateTimeBetween('-3 years', '-6 months');
        $lastMaintenance = fake()->dateTimeBetween($purchaseDate, 'now');

        // Precios según el tipo
        $purchasePrice = $isElectric
            ? fake()->randomFloat(2, 800, 1500)    // Bicicletas eléctricas más caras
            : fake()->randomFloat(2, 200, 600);    // Bicicletas tradicionales más baratas

        // Descripciones variadas
        $descriptions = [
            'Bicicleta en excelente estado',
            'Unidad con desgaste normal por uso',
            'Bicicleta recientemente adquirida',
            'Unidad con mantenimiento al día',
            'Bicicleta popular entre usuarios',
            null, null, null // Algunas sin descripción
        ];

        // Estado activo (95% activas)
        $isActive = fake()->boolean(95);

        return [
            'code' => $code,
            'type' => $type,
            'status' => $status,
            'estacion_id' => $estacionId,
            'battery_level' => $batteryLevel,
            'description' => fake()->randomElement($descriptions),
            'purchase_price' => $purchasePrice,
            'purchase_date' => $purchaseDate,
            'last_maintenance' => $lastMaintenance,
            'is_active' => $isActive,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Seleccionar valor aleatorio basado en pesos/probabilidades
     */
    private function getWeightedRandomValue(array $weightedValues): string
    {
        $totalWeight = array_sum($weightedValues);
        $randomWeight = mt_rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($weightedValues as $value => $weight) {
            $currentWeight += $weight;
            if ($randomWeight <= $currentWeight) {
                return $value;
            }
        }

        return array_key_first($weightedValues);
    }

    /**
     * Mostrar estadísticas de las bicicletas creadas
     */
    private function showStatistics(): void
    {
        $this->command->info("\n=== ESTADÍSTICAS DE BICICLETAS ===");

        // Por tipo
        $tradicionales = Bike::where('type', 'tradicional')->count();
        $electricas = Bike::where('type', 'electrica')->count();

        $this->command->info("Tradicionales: {$tradicionales}");
        $this->command->info("Eléctricas: {$electricas}");

        // Por estado
        $this->command->info("\n--- Por Estado ---");
        $statuses = Bike::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        foreach ($statuses as $status) {
            $this->command->info(ucfirst($status->status) . ": {$status->total}");
        }

        // Activas vs inactivas
        $activas = Bike::where('is_active', true)->count();
        $inactivas = Bike::where('is_active', false)->count();

        $this->command->info("\n--- Estado General ---");
        $this->command->info("Activas: {$activas}");
        $this->command->info("Inactivas: {$inactivas}");

        // Distribución por estaciones (solo las asignadas)
        $this->command->info("\n--- Distribución por Estaciones ---");
        $distribucion = Bike::select('estaciones.nombre', DB::raw('count(*) as total'))
            ->join('estaciones', 'bikes.estacion_id', '=', 'estaciones.id')
            ->groupBy('estaciones.id', 'estaciones.name')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        foreach ($distribucion as $dist) {
            $this->command->info("{$dist->name}: {$dist->total} bicicletas");
        }
    }
}
