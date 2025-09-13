<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_distance',
        'total_co2_saved',
        'total_green_points',
        'completed_routes',
        'ranking_position',
        'last_updated'
    ];

    protected $casts = [
        'total_distance' => 'decimal:2',
        'total_co2_saved' => 'decimal:2',
        'last_updated' => 'date'
    ];

    /**
     * Relación con el usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Actualizar estadísticas de un usuario específico
     */
    public static function updateUserStats($userId)
    {
        $stats = DB::table('routes')
            ->where('user_id', $userId)
            ->where('completed', true)
            ->selectRaw('
                COUNT(*) as completed_routes,
                COALESCE(SUM(distance), 0) as total_distance,
                COALESCE(SUM(co2_saved), 0) as total_co2_saved,
                COALESCE(SUM(green_points), 0) as total_green_points
            ')
            ->first();

        return static::updateOrCreate(
            ['user_id' => $userId],
            [
                'total_distance' => $stats->total_distance ?? 0,
                'total_co2_saved' => $stats->total_co2_saved ?? 0,
                'total_green_points' => $stats->total_green_points ?? 0,
                'completed_routes' => $stats->completed_routes ?? 0,
                'last_updated' => now()
            ]
        );
    }

    /**
     * Actualizar todos los rankings y posiciones
     */
    public static function updateAllRankings()
    {
        // Actualizar estadísticas para todos los usuarios con rutas
        $usersWithRoutes = DB::table('routes')
            ->select('user_id')
            ->distinct()
            ->get();

        foreach ($usersWithRoutes as $user) {
            static::updateUserStats($user->user_id);
        }

        // Actualizar posiciones del ranking
        $rankings = static::orderByDesc('total_distance')
            ->orderByDesc('total_co2_saved')
            ->get();

        foreach ($rankings as $index => $ranking) {
            $ranking->update(['ranking_position' => $index + 1]);
        }
    }

    /**
     * Obtener el ranking completo
     */
    public static function getRanking($limit = 50)
    {
        return static::with(['user:id,name,apellidos,email'])
            ->orderBy('ranking_position')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener la posición de un usuario específico
     */
    public static function getUserPosition($userId)
    {
        $ranking = static::where('user_id', $userId)->first();
        return $ranking ? $ranking->ranking_position : null;
    }

    /**
     * Obtener estadísticas generales del ranking
     */
    public static function getGeneralStats()
    {
        return [
            'total_users' => static::count(),
            'total_distance' => static::sum('total_distance'),
            'total_co2_saved' => static::sum('total_co2_saved'),
            'total_routes' => static::sum('completed_routes'),
            'average_distance_per_user' => static::avg('total_distance')
        ];
    }
}
