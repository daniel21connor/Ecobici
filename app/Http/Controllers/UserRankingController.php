<?php

namespace App\Http\Controllers;

use App\Models\UserRanking;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserRankingController extends Controller
{
    /**
     * Obtener el ranking completo de usuarios
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 50);
            $limit = min($limit, 100); // Máximo 100 usuarios

            // Actualizar rankings antes de mostrar
            UserRanking::updateAllRankings();

            $rankings = UserRanking::getRanking($limit);
            $generalStats = UserRanking::getGeneralStats();

            return response()->json([
                'success' => true,
                'data' => [
                    'rankings' => $rankings,
                    'general_stats' => $generalStats,
                    'updated_at' => now()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener ranking: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el ranking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener el ranking con posición del usuario actual
     */
    public function getUserRanking(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $limit = $request->get('limit', 20);

            // Actualizar estadísticas del usuario actual
            UserRanking::updateUserStats($user->id);
            UserRanking::updateAllRankings();

            // Obtener posición del usuario
            $userPosition = UserRanking::getUserPosition($user->id);
            $userStats = UserRanking::where('user_id', $user->id)->first();

            // Obtener top rankings
            $topRankings = UserRanking::getRanking($limit);

            // Si el usuario no está en el top, obtener algunos rankings alrededor de su posición
            $nearbyRankings = [];
            if ($userPosition && $userPosition > $limit) {
                $nearbyRankings = UserRanking::with(['user:id,name,apellidos,email'])
                    ->orderBy('ranking_position')
                    ->skip(max(0, $userPosition - 3))
                    ->take(6)
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_position' => $userPosition,
                    'user_stats' => $userStats,
                    'top_rankings' => $topRankings,
                    'nearby_rankings' => $nearbyRankings,
                    'general_stats' => UserRanking::getGeneralStats()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener ranking del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tu posición en el ranking'
            ], 500);
        }
    }

    /**
     * Forzar actualización de rankings
     */
    public function updateRankings(): JsonResponse
    {
        try {
            UserRanking::updateAllRankings();

            return response()->json([
                'success' => true,
                'message' => 'Rankings actualizados correctamente',
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar rankings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los rankings'
            ], 500);
        }
    }

    /**
     * Obtener estadísticas detalladas de un usuario
     */
    public function getUserStats(Request $request, $userId = null): JsonResponse
    {
        try {
            $targetUserId = $userId ?? $request->user()->id;

            // Verificar permisos - solo admins pueden ver stats de otros usuarios
            if ($userId && $request->user()->id != $userId && $request->user()->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver estas estadísticas'
                ], 403);
            }

            // Actualizar estadísticas del usuario
            UserRanking::updateUserStats($targetUserId);

            $userStats = UserRanking::with('user:id,name,apellidos,email')
                ->where('user_id', $targetUserId)
                ->first();

            if (!$userStats) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado o sin estadísticas'
                ], 404);
            }

            // Obtener histórico de rutas completadas
            $recentRoutes = \App\Models\Route::where('user_id', $targetUserId)
                ->where('completed', true)
                ->orderByDesc('updated_at')
                ->take(10)
                ->get(['name', 'distance', 'co2_saved', 'green_points', 'updated_at']);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_stats' => $userStats,
                    'recent_routes' => $recentRoutes
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas del usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas'
            ], 500);
        }
    }

    /**
     * Obtener ranking por periodo (mes, año, etc.)
     */
    public function getRankingByPeriod(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'all_time'); // all_time, month, year
            $limit = $request->get('limit', 20);

            $query = UserRanking::with(['user:id,name,apellidos,email']);

            // Filtrar por período si es necesario
            switch ($period) {
                case 'month':
                    $query->where('last_updated', '>=', now()->subMonth());
                    break;
                case 'year':
                    $query->where('last_updated', '>=', now()->subYear());
                    break;
                // 'all_time' no necesita filtro adicional
            }

            $rankings = $query->orderByDesc('total_distance')
                ->orderByDesc('total_co2_saved')
                ->limit($limit)
                ->get()
                ->map(function ($ranking, $index) {
                    $ranking->period_position = $index + 1;
                    return $ranking;
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'rankings' => $rankings,
                    'period' => $period,
                    'total_users' => $rankings->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener ranking por período: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el ranking'
            ], 500);
        }
    }
}
