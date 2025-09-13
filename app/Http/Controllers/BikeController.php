<?php

namespace App\Http\Controllers;

use App\Models\Bike;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BikeController extends Controller
{

    /**
     * Display a listing of the resource.
     * Devuelve JSON para el dashboard
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

            $query = Bike::with('station')->orderBy('code');

            // Filtros opcionales
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

            if ($request->filled('station_id')) {
                $query->where('station_id', $request->station_id);
            }

            if ($request->filled('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('code', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('brand', 'LIKE', '%' . $request->search . '%');
                });
            }

            // Si es paginado o no
            if ($request->filled('paginate') && $request->paginate == 'true') {
                $bikes = $query->paginate(15);
            } else {
                $bikes = $query->get();
            }

            return response()->json([
                'success' => true,
                'bikes' => $bikes
            ]);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@index: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar bicicletas: ' . $e->getMessage()
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

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear bicicletas.'
                ], 403);
            }

            $stations = Station::where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'stations' => $stations
            ]);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@create: ' . $e->getMessage());

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

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear bicicletas.'
                ], 403);
            }

            $rules = [
                'code' => 'required|string|max:20|unique:bikes',
                'type' => 'required|in:tradicional,electrica',
                'status' => 'required|in:disponible,en_uso,en_reparacion,mantenimiento',
                'station_id' => 'nullable|exists:stations,id',
                'description' => 'nullable|string',
                'purchase_price' => 'nullable|numeric|min:0',
                'purchase_date' => 'nullable|date|before_or_equal:today',
                'last_maintenance' => 'nullable|date|before_or_equal:today',
                'is_active' => 'boolean'
            ];

            // Validación adicional para bicicletas eléctricas
            if ($request->type === 'electrica') {
                $rules['battery_level'] = 'required|integer|min:0|max:100';
            }

            $validatedData = $request->validate($rules, [
                'code.required' => 'El código de la bicicleta es obligatorio',
                'code.unique' => 'Ya existe una bicicleta con ese código',
                'type.required' => 'El tipo de bicicleta es obligatorio',
                'status.required' => 'El estado de la bicicleta es obligatorio',
                'battery_level.required' => 'El nivel de batería es obligatorio para bicicletas eléctricas',
                'battery_level.min' => 'El nivel de batería mínimo es 0%',
                'battery_level.max' => 'El nivel de batería máximo es 100%'
            ]);

            // Verificar capacidad de la estación si se asigna una
            if ($request->station_id) {
                $station = Station::find($request->station_id);
                $currentBikes = $station->bikes()->where('is_active', true)->count();

                if ($currentBikes >= $station->capacity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La estación ha alcanzado su capacidad máxima.'
                    ], 422);
                }
            }

            $bikeData = $validatedData;

            // Si no es eléctrica, asegurar que battery_level sea null
            if ($request->type !== 'electrica') {
                $bikeData['battery_level'] = null;
            }

            $bike = Bike::create($bikeData);
            $bike->load('station');

            return response()->json([
                'success' => true,
                'message' => 'Bicicleta creada exitosamente',
                'bike' => $bike
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la bicicleta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Bike $bike)
    {
        try {
            $bike->load('station');

            return response()->json([
                'success' => true,
                'bike' => $bike
            ]);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@show: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la bicicleta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Bike $bike)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar bicicletas.'
                ], 403);
            }

            $stations = Station::where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'bike' => $bike->load('station'),
                'stations' => $stations
            ]);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@edit: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al acceder al formulario de edición: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bike $bike)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar bicicletas.'
                ], 403);
            }

            $rules = [
                'code' => 'required|string|max:20|unique:bikes,code,' . $bike->id,
                'type' => 'required|in:tradicional,electrica',
                'status' => 'required|in:disponible,en_uso,en_reparacion,mantenimiento',
                'station_id' => 'nullable|exists:stations,id',
                'description' => 'nullable|string',
                'purchase_price' => 'nullable|numeric|min:0',
                'purchase_date' => 'nullable|date|before_or_equal:today',
                'last_maintenance' => 'nullable|date|before_or_equal:today',
                'is_active' => 'boolean'
            ];

            if ($request->type === 'electrica') {
                $rules['battery_level'] = 'required|integer|min:0|max:100';
            }

            $validatedData = $request->validate($rules, [
                'code.required' => 'El código de la bicicleta es obligatorio',
                'code.unique' => 'Ya existe una bicicleta con ese código',
                'type.required' => 'El tipo de bicicleta es obligatorio',
                'status.required' => 'El estado de la bicicleta es obligatorio',
                'battery_level.required' => 'El nivel de batería es obligatorio para bicicletas eléctricas'
            ]);

            // Verificar capacidad si se cambia de estación
            if ($request->station_id && $request->station_id != $bike->station_id) {
                $station = Station::find($request->station_id);
                $currentBikes = $station->bikes()->where('is_active', true)->where('id', '!=', $bike->id)->count();

                if ($currentBikes >= $station->capacity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La estación ha alcanzado su capacidad máxima.'
                    ], 422);
                }
            }

            $bikeData = $validatedData;

            if ($request->type !== 'electrica') {
                $bikeData['battery_level'] = null;
            }

            $bike->update($bikeData);
            $bike->load('station');

            return response()->json([
                'success' => true,
                'message' => 'Bicicleta actualizada exitosamente',
                'bike' => $bike->fresh()->load('station')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@update: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la bicicleta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Bike $bike)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar bicicletas.'
                ], 403);
            }

            if ($bike->status === 'en_uso') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una bicicleta que está en uso.'
                ], 422);
            }

            $bikeCode = $bike->code;
            $bike->delete();

            return response()->json([
                'success' => true,
                'message' => "Bicicleta '{$bikeCode}' eliminada exitosamente"
            ]);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@destroy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la bicicleta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle bike status
     */
    public function toggleStatus(Request $request, Bike $bike)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cambiar el estado de bicicletas.'
                ], 403);
            }

            $bike->update(['is_active' => !$bike->is_active]);
            $bike->load('station');

            $status = $bike->is_active ? 'activada' : 'desactivada';

            return response()->json([
                'success' => true,
                'message' => "Bicicleta {$status} exitosamente",
                'bike' => $bike->fresh()->load('station')
            ]);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@toggleStatus: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move bike to station
     */
    public function moveToStation(Request $request, Bike $bike)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para mover bicicletas.'
                ], 403);
            }

            $validatedData = $request->validate([
                'station_id' => 'required|exists:stations,id'
            ]);

            $station = Station::find($request->station_id);
            $currentBikes = $station->bikes()->where('is_active', true)->where('id', '!=', $bike->id)->count();

            if ($currentBikes >= $station->capacity) {
                return response()->json([
                    'success' => false,
                    'message' => 'La estación ha alcanzado su capacidad máxima.'
                ], 422);
            }

            $bike->update([
                'station_id' => $request->station_id,
                'status' => 'disponible'
            ]);

            $bike->load('station');

            return response()->json([
                'success' => true,
                'message' => 'Bicicleta movida a la estación exitosamente',
                'bike' => $bike->fresh()->load('station')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@moveToStation: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al mover la bicicleta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update battery level for electric bikes
     */
    public function updateBattery(Request $request, Bike $bike)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para actualizar la batería.'
                ], 403);
            }

            if ($bike->type !== 'electrica') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta bicicleta no es eléctrica.'
                ], 422);
            }

            $validatedData = $request->validate([
                'battery_level' => 'required|integer|min:0|max:100'
            ]);

            $bike->update(['battery_level' => $request->battery_level]);
            $bike->load('station');

            return response()->json([
                'success' => true,
                'message' => 'Nivel de batería actualizado exitosamente',
                'bike' => $bike->fresh()->load('station')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@updateBattery: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la batería: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bikes data for API/AJAX calls
     */
    public function getData(Request $request)
    {
        try {
            // Cambiar 'stations' por 'station' (singular)
            $query = Bike::with('station');

            if ($request->filled('station_id')) {
                $query->where('station_id', $request->station_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            $bikes = $query->where('is_active', true)->get();

            return response()->json([
                'success' => true,
                'bikes' => $bikes
            ]);

        } catch (\Exception $e) {
            Log::error('Error en BikeController@getData: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de bicicletas: ' . $e->getMessage()
            ], 500);
        }
    }
}
