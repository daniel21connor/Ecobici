<?php

namespace App\Http\Controllers;

use App\Models\BikeUsageHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BikeUsageHistoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = BikeUsageHistory::with(['user', 'bike', 'startStation', 'endStation']);

        // Filtros
        if ($request->has('user_id') && $request->user_id) {
            $query->forUser($request->user_id);
        }

        if ($request->has('bike_id') && $request->bike_id) {
            $query->forBike($request->bike_id);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        // Solo mostrar historial del usuario autenticado si no es admin
        if (!auth()->user()->is_admin) {
            $query->forUser(auth()->id());
        }

        $usages = $query->latest('start_time')
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $usages->getCollection()->map(function($usage) {
                return [
                    'id' => $usage->id,
                    'user' => [
                        'id' => $usage->user->id,
                        'name' => $usage->user->name,
                        'email' => $usage->user->email,
                    ],
                    'bike' => [
                        'id' => $usage->bike->id,
                        'code' => $usage->bike->code,
                        'type' => $usage->bike->type,
                    ],
                    'start_station' => [
                        'id' => $usage->startStation->id,
                        'name' => $usage->startStation->name,
                        'code' => $usage->startStation->code,
                    ],
                    'end_station' => $usage->endStation ? [
                        'id' => $usage->endStation->id,
                        'name' => $usage->endStation->name,
                        'code' => $usage->endStation->code,
                    ] : null,
                    'start_time' => $usage->start_time,
                    'end_time' => $usage->end_time,
                    'duration_minutes' => $usage->duration_minutes,
                    'duration_formatted' => $usage->duration_formatted,
                    'distance_km' => $usage->distance_km,
                    'status' => $usage->status,
                    'is_active' => $usage->is_active,
                    'is_completed' => $usage->is_completed,
                    'route' => $usage->route,
                    'notes' => $usage->notes,
                    'created_at' => $usage->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $usages->currentPage(),
                'last_page' => $usages->lastPage(),
                'per_page' => $usages->perPage(),
                'total' => $usages->total(),
            ]
        ]);
    }

    public function show(BikeUsageHistory $usage): JsonResponse
    {
        // Verificar que el usuario puede ver este registro
        if (!auth()->user()->is_admin && $usage->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para ver este registro'
            ], 403);
        }

        $usage->load(['user', 'bike', 'startStation', 'endStation']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $usage->id,
                'user' => [
                    'id' => $usage->user->id,
                    'name' => $usage->user->name,
                    'email' => $usage->user->email,
                    'dpi' => $usage->user->dpi,
                ],
                'bike' => [
                    'id' => $usage->bike->id,
                    'code' => $usage->bike->code,
                    'type' => $usage->bike->type,
                    'battery_level' => $usage->bike->battery_level,
                ],
                'start_station' => [
                    'id' => $usage->startStation->id,
                    'name' => $usage->startStation->name,
                    'code' => $usage->startStation->code,
                    'address' => $usage->startStation->address,
                    'coordinates' => [
                        'lat' => $usage->startStation->latitude,
                        'lng' => $usage->startStation->longitude,
                    ],
                ],
                'end_station' => $usage->endStation ? [
                    'id' => $usage->endStation->id,
                    'name' => $usage->endStation->name,
                    'code' => $usage->endStation->code,
                    'address' => $usage->endStation->address,
                    'coordinates' => [
                        'lat' => $usage->endStation->latitude,
                        'lng' => $usage->endStation->longitude,
                    ],
                ] : null,
                'start_time' => $usage->start_time,
                'end_time' => $usage->end_time,
                'duration_minutes' => $usage->duration_minutes,
                'duration_formatted' => $usage->duration_formatted,
                'distance_km' => $usage->distance_km,
                'status' => $usage->status,
                'is_active' => $usage->is_active,
                'is_completed' => $usage->is_completed,
                'route' => $usage->route,
                'notes' => $usage->notes,
                'created_at' => $usage->created_at,
                'updated_at' => $usage->updated_at,
            ]
        ]);
    }

    public function getCurrentUsage(): JsonResponse
    {
        $usage = BikeUsageHistory::with(['bike', 'startStation'])
            ->active()
            ->forUser(auth()->id())
            ->first();

        if (!$usage) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'No hay uso activo'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $usage->id,
                'bike' => [
                    'id' => $usage->bike->id,
                    'code' => $usage->bike->code,
                    'type' => $usage->bike->type,
                    'battery_level' => $usage->bike->battery_level,
                ],
                'start_station' => [
                    'id' => $usage->startStation->id,
                    'name' => $usage->startStation->name,
                    'code' => $usage->startStation->code,
                ],
                'start_time' => $usage->start_time,
                'current_duration' => now()->diffInMinutes($usage->start_time),
            ]
        ]);
    }

    public function complete(Request $request, BikeUsageHistory $usage): JsonResponse
    {
        // Verificar autorización
        if (!auth()->user()->is_admin && $usage->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para completar este uso'
            ], 403);
        }

        $validated = $request->validate([
            'end_station_id' => 'required|exists:stations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $usage->complete($validated['end_station_id'], $validated['notes'] ?? null);

            return response()->json([
                'success' => true,
                'data' => $usage->fresh(['endStation']),
                'message' => 'Uso completado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request, BikeUsageHistory $usage): JsonResponse
    {
        // Verificar autorización
        if (!auth()->user()->is_admin && $usage->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para cancelar este uso'
            ], 403);
        }

        // Solo se puede cancelar un uso activo
        if (!$usage->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar usos activos'
            ], 422);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $usage->cancel($validated['reason'] ?? null);

            // Cambiar estado de la bicicleta a disponible
            $usage->bike->update(['status' => 'disponible']);

            return response()->json([
                'success' => true,
                'data' => $usage->fresh(),
                'message' => 'Uso cancelado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getUserStats(Request $request): JsonResponse
    {
        $period = $request->input('period', 'month');
        $userId = auth()->user()->is_admin && $request->has('user_id') ?
            $request->user_id : auth()->id();

        $stats = BikeUsageHistory::getUserStats($userId, $period);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function getStatusOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => BikeUsageHistory::getStatusOptions()
        ]);
    }
}
