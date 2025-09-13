<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    // Removemos el constructor con middleware ya que lo manejamos en web.php

    /**
     * Mostrar todas las rutas del usuario
     */
    public function index()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $routes = $user->routes()->latest()->get();

            // Calcular estadísticas del usuario
            $totalRoutes = $routes->count();
            $completedRoutes = $routes->where('completed', true)->count();
            $totalDistance = $routes->where('completed', true)->sum('distance');
            $totalCO2Saved = $routes->where('completed', true)->sum('co2_saved');
            $totalGreenPoints = $routes->where('completed', true)->sum('green_points');

            $stats = [
                'total_routes' => $totalRoutes,
                'completed_routes' => $completedRoutes,
                'total_distance' => round($totalDistance, 2),
                'total_co2_saved' => round($totalCO2Saved, 2),
                'total_green_points' => $totalGreenPoints
            ];

            return response()->json([
                'success' => true,
                'routes' => $routes,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error en RouteController@index: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las rutas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva ruta
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'start_point' => 'required|string|max:255',
                'end_point' => 'required|string|max:255',
                'distance' => 'required|numeric|min:0.1'
            ], [
                'name.required' => 'El nombre de la ruta es obligatorio',
                'start_point.required' => 'El punto de inicio es obligatorio',
                'end_point.required' => 'El punto de destino es obligatorio',
                'distance.required' => 'La distancia es obligatoria',
                'distance.numeric' => 'La distancia debe ser un número',
                'distance.min' => 'La distancia mínima es 0.1 km'
            ]);

            $route = Route::create([
                'user_id' => Auth::id(),
                'name' => $request->name,
                'start_point' => $request->start_point,
                'end_point' => $request->end_point,
                'distance' => $request->distance,
                'completed' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ruta creada exitosamente',
                'route' => $route
            ]);

        } catch (\Exception $e) {
            Log::error('Error en RouteController@store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al crear la ruta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar ruta como completada
     */
    public function complete(Route $route)
    {
        try {
            // Verificar que la ruta pertenece al usuario actual
            if ($route->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para completar esta ruta'
                ], 403);
            }

            if ($route->completed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta ruta ya fue completada'
                ], 400);
            }

            // Verificar si el modelo tiene el método completeRoute
            if (method_exists($route, 'completeRoute')) {
                $route->completeRoute();
            } else {
                // Fallback manual si no existe el método
                $route->completed = true;
                $route->completed_at = now();

                // Calcular CO2 ahorrado (aprox 0.21 kg por km en bicicleta vs auto)
                $route->co2_saved = round($route->distance * 0.21, 2);

                // Calcular puntos verdes (10 puntos por km)
                $route->green_points = round($route->distance * 10);

                $route->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Ruta completada exitosamente',
                'route' => $route->fresh(),
                'rewards' => [
                    'co2_saved' => $route->co2_saved,
                    'green_points' => $route->green_points
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en RouteController@complete: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al completar la ruta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una ruta
     */
    public function destroy(Route $route)
    {
        try {
            // Verificar que la ruta pertenece al usuario actual
            if ($route->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado para eliminar esta ruta'
                ], 403);
            }

            $routeName = $route->name;
            $route->delete();

            return response()->json([
                'success' => true,
                'message' => "Ruta '{$routeName}' eliminada exitosamente"
            ]);

        } catch (\Exception $e) {
            Log::error('Error en RouteController@destroy: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la ruta: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener insignias del usuario basadas en sus logros
     */
    public function getBadges()
    {
        try {
            $user = Auth::user();
            $routes = $user->routes()->where('completed', true)->get();

            $totalDistance = $routes->sum('distance');
            $totalRoutes = $routes->count();
            $totalCO2Saved = $routes->sum('co2_saved');

            $badges = [];

            // Insignias por distancia
            if ($totalDistance >= 100) {
                $badges[] = [
                    'name' => 'Maratonista Verde',
                    'icon' => '🏃‍♂️',
                    'description' => '100+ km recorridos',
                    'category' => 'distance'
                ];
            } elseif ($totalDistance >= 50) {
                $badges[] = [
                    'name' => 'Ciclista Comprometido',
                    'icon' => '🚴‍♂️',
                    'description' => '50+ km recorridos',
                    'category' => 'distance'
                ];
            } elseif ($totalDistance >= 10) {
                $badges[] = [
                    'name' => 'Explorador Verde',
                    'icon' => '🌱',
                    'description' => '10+ km recorridos',
                    'category' => 'distance'
                ];
            }

            // Insignias por rutas completadas
            if ($totalRoutes >= 50) {
                $badges[] = [
                    'name' => 'Navegador Experto',
                    'icon' => '🧭',
                    'description' => '50+ rutas completadas',
                    'category' => 'routes'
                ];
            } elseif ($totalRoutes >= 20) {
                $badges[] = [
                    'name' => 'Planificador Pro',
                    'icon' => '📍',
                    'description' => '20+ rutas completadas',
                    'category' => 'routes'
                ];
            } elseif ($totalRoutes >= 5) {
                $badges[] = [
                    'name' => 'Aventurero',
                    'icon' => '⭐',
                    'description' => '5+ rutas completadas',
                    'category' => 'routes'
                ];
            }

            // Insignias por CO2 ahorrado
            if ($totalCO2Saved >= 20) {
                $badges[] = [
                    'name' => 'Guardián del Planeta',
                    'icon' => '🌍',
                    'description' => '20+ kg CO₂ ahorrados',
                    'category' => 'environmental'
                ];
            } elseif ($totalCO2Saved >= 10) {
                $badges[] = [
                    'name' => 'Eco Warrior',
                    'icon' => '💚',
                    'description' => '10+ kg CO₂ ahorrados',
                    'category' => 'environmental'
                ];
            } elseif ($totalCO2Saved >= 2) {
                $badges[] = [
                    'name' => 'Amigo del Ambiente',
                    'icon' => '🌿',
                    'description' => '2+ kg CO₂ ahorrados',
                    'category' => 'environmental'
                ];
            }

            return response()->json([
                'success' => true,
                'badges' => $badges,
                'stats' => [
                    'total_distance' => round($totalDistance, 2),
                    'total_routes' => $totalRoutes,
                    'total_co2_saved' => round($totalCO2Saved, 2)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en RouteController@getBadges: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las insignias: ' . $e->getMessage()
            ], 500);
        }
    }
}
