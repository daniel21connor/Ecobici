<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StationController extends Controller
{
    /**
     * Display a listing of the resource.
     * Siempre devuelve JSON para el dashboard
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $stations = Station::with(['bikes' => function ($query) {
                $query->where('is_active', true);
            }])->get();

            return response()->json([
                'success' => true,
                'stations' => $stations
            ]);

        } catch (\Exception $e) {
            Log::error('Error en StationController@index: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las estaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear estaciones.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'Formulario de creación disponible'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en StationController@create: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al acceder al formulario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear estaciones.'
                ], 403);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:stations',
                'code' => 'required|string|max:10|unique:stations',
                'description' => 'nullable|string',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address' => 'nullable|string|max:255',
                'type' => 'required|in:carga,descanso,seleccion',
                'capacity' => 'required|integer|min:1|max:100',
                'is_active' => 'boolean'
            ], [
                'name.required' => 'El nombre de la estación es obligatorio',
                'name.unique' => 'Ya existe una estación con ese nombre',
                'code.required' => 'El código de la estación es obligatorio',
                'code.unique' => 'Ya existe una estación con ese código',
                'latitude.required' => 'La latitud es obligatoria',
                'longitude.required' => 'La longitud es obligatoria',
                'type.required' => 'El tipo de estación es obligatorio',
                'capacity.required' => 'La capacidad es obligatoria',
                'capacity.min' => 'La capacidad mínima es 1',
                'capacity.max' => 'La capacidad máxima es 100'
            ]);

            $station = Station::create($validatedData);

            // Cargar la relación con bikes
            $station->load(['bikes' => function ($query) {
                $query->where('is_active', true);
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Estación creada exitosamente',
                'station' => $station
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en StationController@store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la estación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Station $station)
    {
        try {
            $station->load(['bikes' => function ($query) {
                $query->where('is_active', true);
            }]);

            // Estadísticas de la estación
            $stats = [
                'total_bikes' => $station->bikes->count(),
                'available_bikes' => $station->bikes->where('status', 'disponible')->count(),
                'in_use_bikes' => $station->bikes->where('status', 'en_uso')->count(),
                'maintenance_bikes' => $station->bikes->whereIn('status', ['en_reparacion', 'mantenimiento'])->count(),
                'traditional_bikes' => $station->bikes->where('type', 'tradicional')->count(),
                'electric_bikes' => $station->bikes->where('type', 'electrica')->count(),
            ];

            return response()->json([
                'success' => true,
                'station' => $station,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error en StationController@show: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la estación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Station $station)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar estaciones.'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'station' => $station
            ]);

        } catch (\Exception $e) {
            Log::error('Error en StationController@edit: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al acceder al formulario de edición: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Station $station)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar estaciones.'
                ], 403);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:stations,name,' . $station->id,
                'code' => 'required|string|max:10|unique:stations,code,' . $station->id,
                'description' => 'nullable|string',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'address' => 'nullable|string|max:255',
                'type' => 'required|in:carga,descanso,seleccion',
                'capacity' => 'required|integer|min:1|max:100',
                'is_active' => 'boolean'
            ], [
                'name.required' => 'El nombre de la estación es obligatorio',
                'name.unique' => 'Ya existe una estación con ese nombre',
                'code.required' => 'El código de la estación es obligatorio',
                'code.unique' => 'Ya existe una estación con ese código',
                'latitude.required' => 'La latitud es obligatoria',
                'longitude.required' => 'La longitud es obligatoria',
                'type.required' => 'El tipo de estación es obligatorio',
                'capacity.required' => 'La capacidad es obligatoria',
                'capacity.min' => 'La capacidad mínima es 1',
                'capacity.max' => 'La capacidad máxima es 100'
            ]);

            $station->update($validatedData);

            // Cargar la relación con bikes actualizada
            $station->load(['bikes' => function ($query) {
                $query->where('is_active', true);
            }]);

            return response()->json([
                'success' => true,
                'message' => 'Estación actualizada exitosamente',
                'station' => $station->fresh()->load(['bikes' => function ($query) {
                    $query->where('is_active', true);
                }])
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en StationController@update: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la estación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Station $station)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar estaciones.'
                ], 403);
            }

            // Verificar si la estación tiene bicicletas asignadas
            if ($station->bikes()->where('is_active', true)->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la estación porque tiene bicicletas asignadas.'
                ], 422);
            }

            $stationName = $station->name;
            $station->delete();

            return response()->json([
                'success' => true,
                'message' => "Estación '{$stationName}' eliminada exitosamente"
            ]);

        } catch (\Exception $e) {
            Log::error('Error en StationController@destroy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la estación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle station status
     */
    public function toggleStatus(Request $request, Station $station)
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cambiar el estado de estaciones.'
                ], 403);
            }

            $station->update(['is_active' => !$station->is_active]);
            $status = $station->is_active ? 'activada' : 'desactivada';

            // Cargar la relación con bikes
            $station->load(['bikes' => function ($query) {
                $query->where('is_active', true);
            }]);

            return response()->json([
                'success' => true,
                'message' => "Estación {$status} exitosamente",
                'station' => $station->fresh()->load(['bikes' => function ($query) {
                    $query->where('is_active', true);
                }])
            ]);

        } catch (\Exception $e) {
            Log::error('Error en StationController@toggleStatus: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stations data for API/AJAX calls
     */
    public function getData()
    {
        try {
            $stations = Station::with(['bikes' => function ($query) {
                $query->where('is_active', true);
            }])
                ->where('is_active', true)
                ->get()
                ->map(function ($station) {
                    $totalBikes = $station->bikes->count();
                    $availableSlots = max(0, $station->capacity - $totalBikes);

                    return [
                        'id' => $station->id,
                        'name' => $station->name,
                        'code' => $station->code,
                        'type' => $station->type,
                        'latitude' => $station->latitude,
                        'longitude' => $station->longitude,
                        'capacity' => $station->capacity,
                        'available_bikes' => $station->bikes->where('status', 'disponible')->count(),
                        'total_bikes' => $totalBikes,
                        'available_slots' => $availableSlots, // ← AGREGADO
                        'is_active' => $station->is_active,   // ← AGREGADO
                    ];
                });

            return response()->json([
                'success' => true,
                'stations' => $stations
            ]);

        } catch (\Exception $e) {
            Log::error('Error en StationController@getData: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de estaciones: ' . $e->getMessage()
            ], 500);
        }
    }
}
