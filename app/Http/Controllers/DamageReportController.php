<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DamageReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DamageReport::with(['user', 'bike', 'resolvedBy']);

        // Filtros
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('severity') && $request->severity) {
            $query->bySeverity($request->severity);
        }

        if ($request->has('bike_id') && $request->bike_id) {
            $query->forBike($request->bike_id);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->forUser($request->user_id);
        }

        // Solo mostrar reportes del usuario si no es admin
        if (!auth()->user()->is_admin) {
            $query->forUser(auth()->id());
        }

        $reports = $query->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $reports->getCollection()->map(function($report) {
                return [
                    'id' => $report->id,
                    'user' => [
                        'id' => $report->user->id,
                        'name' => $report->user->name,
                        'email' => $report->user->email,
                    ],
                    'bike' => [
                        'id' => $report->bike->id,
                        'code' => $report->bike->code,
                        'type' => $report->bike->type,
                    ],
                    'description' => $report->description,
                    'severity' => $report->severity,
                    'status' => $report->status,
                    'photos_count' => $report->photos_count,
                    'severity_color' => $report->severity_color,
                    'status_color' => $report->status_color,
                    'is_pending' => $report->is_pending,
                    'is_in_progress' => $report->is_in_progress,
                    'is_resolved' => $report->is_resolved,
                    'resolved_by' => $report->resolvedBy ? [
                        'id' => $report->resolvedBy->id,
                        'name' => $report->resolvedBy->name,
                    ] : null,
                    'resolved_at' => $report->resolved_at,
                    'resolution_time' => $report->resolution_time,
                    'created_at' => $report->created_at,
                ];
            }),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ]
        ]);
    }

    public function show(DamageReport $damageReport): JsonResponse
    {
        // Verificar autorización
        if (!auth()->user()->is_admin && $damageReport->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para ver este reporte'
            ], 403);
        }

        $damageReport->load(['user', 'bike', 'resolvedBy']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $damageReport->id,
                'user' => [
                    'id' => $damageReport->user->id,
                    'name' => $damageReport->user->name,
                    'email' => $damageReport->user->email,
                    'phone' => $damageReport->user->phone,
                ],
                'bike' => [
                    'id' => $damageReport->bike->id,
                    'code' => $damageReport->bike->code,
                    'type' => $damageReport->bike->type,
                    'status' => $damageReport->bike->status,
                    'station' => $damageReport->bike->station ? [
                        'id' => $damageReport->bike->station->id,
                        'name' => $damageReport->bike->station->name,
                        'code' => $damageReport->bike->station->code,
                    ] : null,
                ],
                'description' => $damageReport->description,
                'severity' => $damageReport->severity,
                'status' => $damageReport->status,
                'photos' => $damageReport->photos,
                'photos_count' => $damageReport->photos_count,
                'severity_color' => $damageReport->severity_color,
                'status_color' => $damageReport->status_color,
                'is_pending' => $damageReport->is_pending,
                'is_in_progress' => $damageReport->is_in_progress,
                'is_resolved' => $damageReport->is_resolved,
                'resolved_by' => $damageReport->resolvedBy ? [
                    'id' => $damageReport->resolvedBy->id,
                    'name' => $damageReport->resolvedBy->name,
                    'email' => $damageReport->resolvedBy->email,
                ] : null,
                'resolved_at' => $damageReport->resolved_at,
                'resolution_time' => $damageReport->resolution_time,
                'resolution_notes' => $damageReport->resolution_notes,
                'created_at' => $damageReport->created_at,
                'updated_at' => $damageReport->updated_at,
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bike_id' => 'required|exists:bikes,id',
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

            $report = DamageReport::create([
                'user_id' => auth()->id(),
                'bike_id' => $validated['bike_id'],
                'description' => $validated['description'],
                'severity' => $validated['severity'],
                'photos' => $photosPaths,
                'status' => 'pendiente',
            ]);

            // Cambiar estado de la bicicleta
            $report->bike->update(['status' => 'en_reparacion']);

            return response()->json([
                'success' => true,
                'data' => $report->load(['bike', 'user']),
                'message' => 'Reporte de daño creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            // Limpiar fotos subidas si hay error
            if (isset($photosPaths)) {
                foreach ($photosPaths as $path) {
                    Storage::disk('public')->delete($path);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el reporte: ' . $e->getMessage()
            ], 422);
        }
    }

    public function update(Request $request, DamageReport $damageReport): JsonResponse
    {
        // Solo admins pueden actualizar reportes
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $validated = $request->validate([
            'description' => 'sometimes|string|max:1000',
            'severity' => ['sometimes', Rule::in(['leve', 'moderado', 'grave'])],
            'status' => ['sometimes', Rule::in(['pendiente', 'en_revision', 'en_reparacion', 'resuelto'])],
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        // Si se marca como resuelto, actualizar campos de resolución
        if (isset($validated['status']) && $validated['status'] === 'resuelto') {
            $validated['resolved_by'] = auth()->id();
            $validated['resolved_at'] = now();

            // Cambiar estado de la bicicleta a disponible
            $damageReport->bike->update(['status' => 'disponible']);
        }

        $damageReport->update($validated);

        return response()->json([
            'success' => true,
            'data' => $damageReport->fresh(['user', 'bike', 'resolvedBy']),
            'message' => 'Reporte actualizado exitosamente'
        ]);
    }

    public function markAsInReview(DamageReport $damageReport): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $damageReport->markAsInReview(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $damageReport->fresh(['resolvedBy']),
            'message' => 'Reporte marcado en revisión'
        ]);
    }

    public function markAsInRepair(DamageReport $damageReport): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $damageReport->markAsInRepair(auth()->id());

        return response()->json([
            'success' => true,
            'data' => $damageReport->fresh(['resolvedBy']),
            'message' => 'Reporte marcado en reparación'
        ]);
    }

    public function resolve(Request $request, DamageReport $damageReport): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $damageReport->resolve(auth()->id(), $validated['resolution_notes'] ?? null);

        return response()->json([
            'success' => true,
            'data' => $damageReport->fresh(['user', 'bike', 'resolvedBy']),
            'message' => 'Reporte resuelto exitosamente'
        ]);
    }

    public function addPhoto(Request $request, DamageReport $damageReport): JsonResponse
    {
        // Verificar autorización
        if (!auth()->user()->is_admin && $damageReport->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($damageReport->photos_count >= 5) {
            return response()->json([
                'success' => false,
                'message' => 'Máximo 5 fotos por reporte'
            ], 422);
        }

        try {
            $path = $request->file('photo')->store('damage-reports', 'public');
            $damageReport->addPhoto($path);

            return response()->json([
                'success' => true,
                'data' => ['photo_path' => $path],
                'message' => 'Foto agregada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la foto'
            ], 422);
        }
    }

    public function removePhoto(Request $request, DamageReport $damageReport): JsonResponse
    {
        // Verificar autorización
        if (!auth()->user()->is_admin && $damageReport->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $validated = $request->validate([
            'photo_path' => 'required|string',
        ]);

        // Verificar que la foto existe en el reporte
        if (!in_array($validated['photo_path'], $damageReport->photos ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'Foto no encontrada en el reporte'
            ], 404);
        }

        try {
            // Eliminar archivo físico
            Storage::disk('public')->delete($validated['photo_path']);

            // Remover de la base de datos
            $damageReport->removePhoto($validated['photo_path']);

            return response()->json([
                'success' => true,
                'message' => 'Foto eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la foto'
            ], 422);
        }
    }

    public function getSeverityOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DamageReport::getSeverityOptions()
        ]);
    }

    public function getStatusOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DamageReport::getStatusOptions()
        ]);
    }

    public function getStatistics(Request $request): JsonResponse
    {
        if (!auth()->user()->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $period = $request->input('period', 'month');
        $stats = DamageReport::getStatistics($period);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
