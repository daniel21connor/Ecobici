<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class StationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Station::with(['bikes' => function($q) {
            $q->where('is_active', true);
        }])->active();

        // Filtro por tipo
        if ($request->has('type') && $request->type) {
            $query->ofType($request->type);
        }

        // Búsqueda por nombre o código
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $stations = $query->get()->map(function($station) {
            return [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'description' => $station->description,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'address' => $station->address,
                'type' => $station->type,
                'capacity' => $station->capacity,
                'available_bikes_count' => $station->available_bikes_count,
                'total_bikes' => $station->total_bikes,
                'occupancy_percentage' => $station->occupancy_percentage,
                'is_active' => $station->is_active,
                'created_at' => $station->created_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stations
        ]);
    }

    public function show(Station $station): JsonResponse
    {
        $station->load(['bikes' => function($q) {
            $q->where('is_active', true);
        }]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'description' => $station->description,
                'latitude' => $station->latitude,
                'longitude' => $station->longitude,
                'address' => $station->address,
                'type' => $station->type,
                'capacity' => $station->capacity,
                'available_bikes_count' => $station->available_bikes_count,
                'total_bikes' => $station->total_bikes,
                'occupancy_percentage' => $station->occupancy_percentage,
                'is_active' => $station->is_active,
                'bikes' => $station->bikes->map(function($bike) {
                    return [
                        'id' => $bike->id,
                        'code' => $bike->code,
                        'type' => $bike->type,
                        'status' => $bike->status,
                        'battery_level' => $bike->battery_level,
                        'is_available' => $bike->is_available,
                        'can_be_rented' => $bike->canBeRented(),
                    ];
                })
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:stations',
            'code' => 'required|string|max:10|unique:stations',
            'description' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'type' => ['required', Rule::in(['carga', 'descanso', 'seleccion'])],
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        $station = Station::create($validated);

        return response()->json([
            'success' => true,
            'data' => $station,
            'message' => 'Estación creada exitosamente'
        ], 201);
    }

    public function update(Request $request, Station $station): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:stations,name,' . $station->id,
            'code' => 'sometimes|string|max:10|unique:stations,code,' . $station->id,
            'description' => 'nullable|string',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
            'address' => 'nullable|string|max:500',
            'type' => ['sometimes', Rule::in(['carga', 'descanso', 'seleccion'])],
            'capacity' => 'sometimes|integer|min:1|max:100',
            'is_active' => 'sometimes|boolean',
        ]);

        $station->update($validated);

        return response()->json([
            'success' => true,
            'data' => $station->fresh(),
            'message' => 'Estación actualizada exitosamente'
        ]);
    }

    public function destroy(Station $station): JsonResponse
    {
        // Verificar que no tenga bicicletas asignadas
        if ($station->bikes()->where('is_active', true)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la estación porque tiene bicicletas asignadas'
            ], 422);
        }

        $station->delete();

        return response()->json([
            'success' => true,
            'message' => 'Estación eliminada exitosamente'
        ]);
    }

    public function getAvailableBikes(Station $station, Request $request): JsonResponse
    {
        $type = $request->input('type');
        $bikes = $station->getAvailableBikesByType($type);

        return response()->json([
            'success' => true,
            'data' => $bikes->map(function($bike) {
                return [
                    'id' => $bike->id,
                    'code' => $bike->code,
                    'type' => $bike->type,
                    'battery_level' => $bike->battery_level,
                    'battery_status' => $bike->battery_status,
                    'can_be_rented' => $bike->canBeRented(),
                ];
            })
        ]);
    }

    public function getTypeOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Station::getTypeOptions()
        ]);
    }

    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_stations' => Station::active()->count(),
            'by_type' => [
                'carga' => Station::active()->ofType('carga')->count(),
                'descanso' => Station::active()->ofType('descanso')->count(),
                'seleccion' => Station::active()->ofType('seleccion')->count(),
            ],
            'total_capacity' => Station::active()->sum('capacity'),
            'total_bikes' => Station::active()->withCount('bikes')->get()->sum('bikes_count'),
            'average_occupancy' => Station::active()->get()->avg('occupancy_percentage'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
