<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use App\Models\Bike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DamageReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Obtener todos los reportes (admin) o del usuario actual
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $query = DamageReport::with(['bike.station', 'user']);

            // Si no es admin, solo sus reportes
            if ($user->role !== 'admin') {
                $query->where('user_id', $user->id);
            }

            // Aplicar filtros
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('severity')) {
                $query->where('severity', $request->severity);
            }

            if ($request->filled('bike_id')) {
                $query->where('bike_id', $request->bike_id);
            }

            $reports = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'reports' => $reports
            ]);

        } catch (\Exception $e) {
            Log::error('Error en DamageReportController@index: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar reportes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo reporte
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $validatedData = $request->validate([
                'bike_id' => 'required|exists:bikes,id',
                'description' => 'required|string|max:1000',
                'severity' => 'required|in:leve,moderado,grave',
                'photos.*' => 'nullable|image|max:2048'
            ]);

            $reportData = [
                'user_id' => $user->id,
                'bike_id' => $validatedData['bike_id'],
                'description' => $validatedData['description'],
                'severity' => $validatedData['severity'],
                'status' => 'pendiente'
            ];

            $report = DamageReport::create($reportData);

            // Procesar fotos si existen
            if ($request->hasFile('photos')) {
                $photos = [];
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store('damage_reports', 'public');
                    $photos[] = $path;
                }
                $report->photos = $photos;
                $report->save();
            }

            $report->load(['bike.station', 'user']);

            return response()->json([
                'success' => true,
                'message' => 'Reporte creado exitosamente',
                'report' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Error en DamageReportController@store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un reporte específico
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $query = DamageReport::with(['user', 'bike', 'resolver']);

            // Si no es admin, solo puede ver sus propios reportes
            if ($user->role !== 'admin') {
                $query->byUser($user->id);
            }

            $report = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'report' => $report
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reporte no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el estado de un reporte (solo admin)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para actualizar reportes'
                ], 403);
            }

            $validated = $request->validate([
                'status' => ['required', Rule::in(['pendiente', 'en_revision', 'en_reparacion', 'resuelto'])],
                'resolution_notes' => 'nullable|string|max:500'
            ]);

            $report = DamageReport::findOrFail($id);

            // Si se está marcando como resuelto
            if ($validated['status'] === 'resuelto') {
                $report->markAsResolved($user, $validated['resolution_notes'] ?? null);
            } else {
                $report->update([
                    'status' => $validated['status'],
                    'resolution_notes' => $validated['resolution_notes'] ?? $report->resolution_notes
                ]);
            }

            $report->load(['user', 'bike', 'resolver']);

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'report' => $report
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reporte no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un reporte
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $query = DamageReport::query();

            // Si no es admin, solo puede eliminar sus propios reportes
            if ($user->role !== 'admin') {
                $query->byUser($user->id);
            }

            $report = $query->findOrFail($id);

            // Eliminar fotos del storage
            if ($report->photos) {
                foreach ($report->photos as $photo) {
                    Storage::disk('public')->delete($photo);
                }
            }

            $report->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reporte no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de reportes (admin)
     */
    public function statistics()
    {
        try {
            $user = Auth::user();

            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver estadísticas'
                ], 403);
            }

            $stats = [
                'total_reports' => DamageReport::count(),
                'pending_reports' => DamageReport::where('status', 'pendiente')->count(),
                'resolved_reports' => DamageReport::where('status', 'resuelto')->count(),
                'by_severity' => [
                    'leve' => DamageReport::where('severity', 'leve')->count(),
                    'moderado' => DamageReport::where('severity', 'moderado')->count(),
                    'grave' => DamageReport::where('severity', 'grave')->count()
                ],
                'by_status' => [
                    'pendiente' => DamageReport::where('status', 'pendiente')->count(),
                    'en_revision' => DamageReport::where('status', 'en_revision')->count(),
                    'en_reparacion' => DamageReport::where('status', 'en_reparacion')->count(),
                    'resuelto' => DamageReport::where('status', 'resuelto')->count()
                ],
                'recent_reports' => DamageReport::with(['user', 'bike'])
                    ->latest()
                    ->take(5)
                    ->get()
            ];

            return response()->json([
                'success' => true,
                'statistics' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener bicicletas disponibles para reportar
     */
    public function getAvailableBikes()
    {
        try {
            $user = Auth::user();

            if ($user->role === 'admin') {
                $bikes = Bike::with('user')->get();
            } else {
                $bikes = Bike::where('user_id', $user->id)->get();
            }

            return response()->json([
                'success' => true,
                'bikes' => $bikes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener bicicletas: ' . $e->getMessage()
            ], 500);
        }
    }
}
