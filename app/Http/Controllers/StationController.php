<?php

namespace App\Http\Controllers;

use App\Models\Estacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class StationController extends Controller
{
    /**
     * Mostrar listado de estaciones
     * Para usuarios: solo estaciones activas
     * Para admins: todas las estaciones
     */
    public function index(Request $request)
    {
        try {
            $query = Estacion::query();

            // Si no es admin, solo mostrar estaciones activas
            // Cambiar hasPermissionTo() por is_admin
            if (!auth()->user()->is_admin) {
                $query->activas();
            }

            // Filtros
            if ($request->has('tipo') && $request->tipo != '') {
                $query->porTipo($request->tipo);
            }

            if ($request->has('estado') && $request->estado != '') {
                $query->porEstado($request->estado);
            }

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('nombre', 'LIKE', "%{$search}%")
                        ->orWhere('ubicacion', 'LIKE', "%{$search}%")
                        ->orWhere('direccion', 'LIKE', "%{$search}%");
                });
            }

            // Ordenamiento
            $sortField = $request->get('sort', 'nombre');
            $sortDirection = $request->get('direction', 'asc');

            $validSortFields = ['nombre', 'tipo_estacion', 'estado', 'capacidad_total', 'created_at'];
            if (in_array($sortField, $validSortFields)) {
                $query->orderBy($sortField, $sortDirection);
            }

            $estaciones = $query->paginate(15);

            // Si es una petición AJAX, devolver solo los datos
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $estaciones,
                    'tipos' => Estacion::getTiposEstacion(),
                    'estados' => Estacion::getEstados()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $estaciones,
                'tipos' => Estacion::getTiposEstacion(),
                'estados' => Estacion::getEstados()
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva estación (solo admin)
     */
    public function store(Request $request)
    {
        try {
            // Verificar que el usuario sea admin
            if (!auth()->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            // Validación
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:estaciones,nombre',
                'descripcion' => 'nullable|string|max:500',
                'ubicacion' => 'required|string|max:255',
                'latitud' => 'required|numeric|between:-90,90',
                'longitud' => 'required|numeric|between:-180,180',
                'tipo_estacion' => 'required|in:' . implode(',', array_keys(Estacion::getTiposEstacion())),
                'capacidad_total' => 'required|integer|min:1|max:100',
                'estado' => 'required|in:' . implode(',', array_keys(Estacion::getEstados())),
                'direccion' => 'nullable|string|max:500',
                'telefono' => 'nullable|string|max:20',
                'observaciones' => 'nullable|string|max:1000'
            ], [
                'nombre.required' => 'El nombre de la estación es obligatorio',
                'nombre.unique' => 'Ya existe una estación con este nombre',
                'ubicacion.required' => 'La ubicación es obligatoria',
                'latitud.required' => 'La latitud es obligatoria',
                'latitud.between' => 'La latitud debe estar entre -90 y 90',
                'longitud.required' => 'La longitud es obligatoria',
                'longitud.between' => 'La longitud debe estar entre -180 y 180',
                'tipo_estacion.required' => 'El tipo de estación es obligatorio',
                'tipo_estacion.in' => 'El tipo de estación no es válido',
                'capacidad_total.required' => 'La capacidad total es obligatoria',
                'capacidad_total.min' => 'La capacidad debe ser al menos 1',
                'capacidad_total.max' => 'La capacidad no puede ser mayor a 100',
                'estado.required' => 'El estado es obligatorio',
                'estado.in' => 'El estado no es válido'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Crear la estación
            DB::beginTransaction();

            $data = $request->all();
            // Inicializar capacidad disponible igual a capacidad total
            $data['capacidad_disponible'] = $data['capacidad_total'];

            $estacion = Estacion::create($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estación creada exitosamente',
                'data' => $estacion
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la estación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una estación específica
     */
    public function show($id)
    {
        try {
            $estacion = Estacion::findOrFail($id);

            // Si no es admin y la estación no está activa, no permitir acceso
            if (!auth()->user()->is_admin && !$estacion->estaActiva()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Estación no disponible'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $estacion
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la estación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar estación (solo admin)
     */
    public function update(Request $request, $id)
    {
        try {
            // Verificar que el usuario sea admin
            if (!auth()->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $estacion = Estacion::findOrFail($id);

            // Validación
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255|unique:estaciones,nombre,' . $id,
                'descripcion' => 'nullable|string|max:500',
                'ubicacion' => 'required|string|max:255',
                'latitud' => 'required|numeric|between:-90,90',
                'longitud' => 'required|numeric|between:-180,180',
                'tipo_estacion' => 'required|in:' . implode(',', array_keys(Estacion::getTiposEstacion())),
                'capacidad_total' => 'required|integer|min:1|max:100',
                'capacidad_disponible' => 'required|integer|min:0',
                'estado' => 'required|in:' . implode(',', array_keys(Estacion::getEstados())),
                'direccion' => 'nullable|string|max:500',
                'telefono' => 'nullable|string|max:20',
                'observaciones' => 'nullable|string|max:1000'
            ], [
                'capacidad_disponible.min' => 'La capacidad disponible no puede ser negativa'
            ]);

            // Validar que capacidad disponible no sea mayor a capacidad total
            $validator->after(function ($validator) use ($request) {
                if ($request->capacidad_disponible > $request->capacidad_total) {
                    $validator->errors()->add('capacidad_disponible', 'La capacidad disponible no puede ser mayor a la capacidad total');
                }
            });

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $estacion->update($request->all());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estación actualizada exitosamente',
                'data' => $estacion->fresh()
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la estación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar estación (solo admin)
     */
    public function destroy($id)
    {
        try {
            // Verificar que el usuario sea admin
            if (!auth()->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $estacion = Estacion::findOrFail($id);

            DB::beginTransaction();

            // Soft delete
            $estacion->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estación eliminada exitosamente'
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la estación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estaciones para mapa (solo activas)
     */
    public function paraMapas()
    {
        try {
            $estaciones = Estacion::activas()
                ->select('id', 'nombre', 'tipo_estacion', 'latitud', 'longitud', 'capacidad_total', 'capacidad_disponible', 'direccion')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $estaciones
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estaciones para el mapa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de estaciones (solo admin)
     */
    public function estadisticas()
    {
        try {
            // Verificar que el usuario sea admin
            if (!auth()->user()->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para realizar esta acción'
                ], 403);
            }

            $stats = [
                'total_estaciones' => Estacion::count(),
                'estaciones_activas' => Estacion::where('estado', Estacion::ESTADO_ACTIVA)->count(),
                'estaciones_inactivas' => Estacion::where('estado', Estacion::ESTADO_INACTIVA)->count(),
                'estaciones_mantenimiento' => Estacion::where('estado', Estacion::ESTADO_MANTENIMIENTO)->count(),
                'por_tipo' => [
                    'carga' => Estacion::where('tipo_estacion', Estacion::TIPO_CARGA)->count(),
                    'descanso' => Estacion::where('tipo_estacion', Estacion::TIPO_DESCANSO)->count(),
                    'seleccion' => Estacion::where('tipo_estacion', Estacion::TIPO_SELECCION)->count(),
                ],
                'capacidad_total' => Estacion::sum('capacidad_total'),
                'capacidad_disponible' => Estacion::sum('capacidad_disponible')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
