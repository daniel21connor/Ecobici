<?php

namespace App\Http\Controllers;

use App\Models\Bike;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class BikeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Bike::with('station')->active();

        // Filtros
        if ($request->has('type') && $request->type) {
            $query->ofType($request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('station_id') && $request->station_id) {
            $query->inStation($request->station_id);
        }

        if ($request->has('available') && $request->boolean('available')) {
            $query->available();
        }

        // Búsqueda por código
        if ($request->has('search') && $request->search) {
            $query->where('code', 'LIKE', "%{$request->search}%");
        }

        $bikes = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $bikes->getCollection()->map(function($bike) {
                return [
                    'id' => $bike->id,
                    'code' => $bike->code,
                    'type' => $bike->type,
                    'status' => $bike->status,
                    'battery_level' => $bike->battery_level,
                    'battery_status' => $bike->battery_status,
                    'is_available' => $bike->is_available,
                    'is_electric' => $bike->is_electric,
                    'can_be_rented' => $bike->canBeRented(),
                    'total_usage_time' => $bike->total_usage_time,
                    'station' => $bike->station ? [
                        'id' => $bike->station->id,
                        'name' => $bike->station->name,
                        'code' => $bike->station->code,
                        'type' => $bike->station->type,
                    ] : null,
                    'created_at' => $bike->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $bikes->currentPage(),
                'last_page' => $bikes->lastPage(),
                'per_page' => $bikes->perPage(),
                'total' => $bikes->total(),
            ]
        ]);
    }

    public function show(Bike $bike): JsonResponse
    {
        $bike->load(['station', 'currentUsage.user', 'activeDamageReports']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $bike->id,
                'code' => $bike->code,
                'type' => $bike->type,
                'status' => $bike->status,
                'battery_level' => $bike->battery_level,
                'battery_status' => $bike->battery_status,
                'description' => $bike->description,
                'purchase_price' => $bike->purchase_price,
                'purchase_date' => $bike->purchase_date,
                'last_maintenance' => $bike->last_maintenance,
                'is_available' => $bike->is_available,
                'is_electric' => $bike->is_electric,
                'can_be_rented' => $bike->canBeRented(),
                'total_usage_time' => $bike->total_usage_time,
                'station' => $bike->station ? [
                    'id' => $bike->station->id,
                    'name' => $bike->station->name,
                    'code' => $bike->station->code,
                    'type' => $bike->station->type,
                ] : null,
                'current_usage' => $bike->currentUsage ? [
                    'id' => $bike->currentUsage->id,
                    'user' => $bike->currentUsage->user->name,
                    'start_time' => $bike->currentUsage->start_time,
                    'start_station' => $bike->currentUsage->startStation->name,
                ] : null,
                'active_damage_reports' => $bike->activeDamageReports->map(function($report) {
                    return [
                        'id' => $report->id,
                        'description' => $report->description,
                        'severity' => $report->severity,
                        'status' => $report->status,
                        'created_at' => $report->created_at,
                    ];
                }),
                'created_at' => $bike->created_at,
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:20|unique:bikes',
            'type' => ['required', Rule::in(['tradicional', 'electrica'])],
            'station_id' => 'required|exists:stations,id',
            'battery_level' => 'nullable|integer|between:0,100',
            'description' => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'last_maintenance' => 'nullable|date',
        ]);

        // Verificar capacidad de la estación
        $station = Station::find($validated['station_id']);
        if (!$station->canAcceptBike()) {
            return response()->json([
                'success' => false,
                'message' => 'La estación ha alcanzado su capacidad máxima'
            ], 422);
        }

        // Establecer valores por defecto
        $validated['status'] = 'disponible';
        $validated['is_active'] = true;

        // Para bicicletas eléctricas, establecer nivel de batería por defecto
        if ($validated['type'] === 'electrica' && !isset($validated['battery_level'])) {
            $validated['battery_level'] = 100;
        }

        $bike = Bike::create($validated);

        return response()->json([
            'success' => true,
            'data' => $bike->load('station'),
            'message' => 'Bicicleta registrada exitosamente'
        ], 201);
    }

    public function update(Request $request, Bike $bike): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|max:20|unique:bikes,code,' . $bike->id,
            'type' => ['sometimes', Rule::in(['tradicional', 'electrica'])],
            'status' => ['sometimes', Rule::in(['disponible', 'en_uso', 'en_reparacion', 'mantenimiento'])],
            'station_id' => 'sometimes|exists:stations,id',
            'battery_level' => 'nullable|integer|between:0,100',
            'description' => 'nullable|string',
            'purchase_price' => 'nullable|numeric|min:0',
            'purchase_date' => 'nullable|date',
            'last_maintenance' => 'nullable|date',
            'is_active' => 'sometimes|boolean',
        ]);

        // Si se cambia la estación, verificar capacidad
        if (isset($validated['station_id']) && $validated['station_id'] !== $bike->station_id) {
            $station = Station::find($validated['station_id']);
            if (!$station->canAcceptBike()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La estación destino ha alcanzado su capacidad máxima'
                ], 422);
            }
        }

        $bike->update($validated);

        return response()->json([
            'success' => true,
            'data' => $bike->fresh()->load('station'),
            'message' => 'Bicicleta actualizada exitosamente'
        ]);
    }

    public function destroy(Bike $bike): JsonResponse
    {
        // Verificar que no esté en uso
        if ($bike->status === 'en_uso') {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar una bicicleta en uso'
            ], 422);
        }

        $bike->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Bicicleta desactivada exitosamente'
        ]);
    }

    public function rent(Request $request, Bike $bike): JsonResponse
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,id',
        ]);

        try {
            $usage = $bike->startUsage(
                auth()->id(),
                $validated['station_id']
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'usage_id' => $usage->id,
                    'bike' => [
                        'id' => $bike->id,
                        'code' => $bike->code,
                        'type' => $bike->type,
                        'battery_level' => $bike->battery_level,
                    ],
                    'start_time' => $usage->start_time,
                    'start_station' => $usage->startStation->name,
                ],
                'message' => 'Bicicleta alquilada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function returnBike(Request $request, Bike $bike): JsonResponse
    {
        $validated = $request->validate([
            'station_id' => 'required|exists:stations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            // Verificar capacidad de la estación de retorno
            $station = Station::find($validated['station_id']);
            if (!$station->canAcceptBike()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La estación de retorno ha alcanzado su capacidad máxima'
                ], 422);
            }

            $usage = $bike->endUsage(
                $validated['station_id'],
                $validated['notes'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'usage_id' => $usage->id,
                    'duration' => $usage->duration_formatted,
                    'end_station' => $usage->endStation->name,
                ],
                'message' => 'Bicicleta devuelta exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function reportDamage(Request $request, Bike $bike): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:1000',
            'severity' => ['required', Rule::in(['leve', 'moderado', 'grave'])],
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            // Procesar fotos si las hay
            $photosPaths = [];
            if (isset($validated['photos'])) {
                foreach ($validated['photos'] as $photo) {
                    $path = $photo->store('damage-reports', 'public');
                    $photosPaths[] = $path;
                }
            }

            $report = $bike->reportDamage(
                auth()->id(),
                $validated['description'],
                $validated['severity'],
                $photosPaths
            );

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Reporte de daño creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function getTypeOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Bike::getTypeOptions()
        ]);
    }

    public function getStatusOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Bike::getStatusOptions()
        ]);
    }

    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_bikes' => Bike::active()->count(),
            'by_type' => [
                'tradicional' => Bike::active()->ofType('tradicional')->count(),
                'electrica' => Bike::active()->ofType('electrica')->count(),
            ],
            'by_status' => [
                'disponible' => Bike::active()->where('status', 'disponible')->count(),
                'en_uso' => Bike::active()->where('status', 'en_uso')->count(),
                'en_reparacion' => Bike::active()->where('status', 'en_reparacion')->count(),
                'mantenimiento' => Bike::active()->where('status', 'mantenimiento')->count(),
            ],
            'average_usage_time' => Bike::active()->avg('total_usage_time'),
            'low_battery_count' => Bike::active()
                ->where('type', 'electrica')
                ->where('battery_level', '<=', 20)
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
