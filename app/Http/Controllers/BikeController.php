<?php

namespace App\Http\Controllers;

use App\Models\Bike;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BikeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Bike::with(['station']);

        // Filtros
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('station_id')) {
            $query->where('station_id', $request->station_id);
        }

        if ($request->filled('search')) {
            $query->where('code', 'LIKE', '%' . $request->search . '%');
        }

        $bikes = $query->paginate(15);
        $stations = Station::where('is_active', true)->get();

        return view('bikes.index', compact('bikes', 'stations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('bikes.index')
                ->with('error', 'No tienes permisos para crear bicicletas.');
        }

        $stations = Station::where('is_active', true)->get();
        return view('bikes.create', compact('stations'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('bikes.index')
                ->with('error', 'No tienes permisos para crear bicicletas.');
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

        $request->validate($rules);

        // Verificar capacidad de la estación si se asigna una
        if ($request->station_id) {
            $station = Station::find($request->station_id);
            $currentBikes = $station->bikes()->where('is_active', true)->count();

            if ($currentBikes >= $station->capacity) {
                return redirect()->back()
                    ->with('error', 'La estación ha alcanzado su capacidad máxima.')
                    ->withInput();
            }
        }

        $bikeData = $request->all();

        // Si no es eléctrica, asegurar que battery_level sea null
        if ($request->type !== 'electrica') {
            $bikeData['battery_level'] = null;
        }

        Bike::create($bikeData);

        return redirect()->route('bikes.index')
            ->with('success', 'Bicicleta creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bike $bike)
    {
        $bike->load('station');
        return view('bikes.show', compact('bike'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Bike $bike)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('bikes.index')
                ->with('error', 'No tienes permisos para editar bicicletas.');
        }

        $stations = Station::where('is_active', true)->get();
        return view('bikes.edit', compact('bike', 'stations'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bike $bike)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('bikes.index')
                ->with('error', 'No tienes permisos para editar bicicletas.');
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

        $request->validate($rules);

        // Verificar capacidad si se cambia de estación
        if ($request->station_id && $request->station_id != $bike->station_id) {
            $station = Station::find($request->station_id);
            $currentBikes = $station->bikes()->where('is_active', true)->where('id', '!=', $bike->id)->count();

            if ($currentBikes >= $station->capacity) {
                return redirect()->back()
                    ->with('error', 'La estación ha alcanzado su capacidad máxima.')
                    ->withInput();
            }
        }

        $bikeData = $request->all();

        if ($request->type !== 'electrica') {
            $bikeData['battery_level'] = null;
        }

        $bike->update($bikeData);

        return redirect()->route('bikes.index')
            ->with('success', 'Bicicleta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bike $bike)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('bikes.index')
                ->with('error', 'No tienes permisos para eliminar bicicletas.');
        }

        if ($bike->status === 'en_uso') {
            return redirect()->route('bikes.index')
                ->with('error', 'No se puede eliminar una bicicleta que está en uso.');
        }

        $bike->delete();

        return redirect()->route('bikes.index')
            ->with('success', 'Bicicleta eliminada exitosamente.');
    }

    /**
     * Toggle bike status
     */
    public function toggleStatus(Bike $bike)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('bikes.index')
                ->with('error', 'No tienes permisos para cambiar el estado de bicicletas.');
        }

        $bike->update(['is_active' => !$bike->is_active]);

        $status = $bike->is_active ? 'activada' : 'desactivada';
        return redirect()->route('bikes.index')
            ->with('success', "Bicicleta {$status} exitosamente.");
    }

    /**
     * Move bike to station
     */
    public function moveToStation(Request $request, Bike $bike)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('bikes.index')
                ->with('error', 'No tienes permisos para mover bicicletas.');
        }

        $request->validate([
            'station_id' => 'required|exists:stations,id'
        ]);

        $station = Station::find($request->station_id);
        $currentBikes = $station->bikes()->where('is_active', true)->where('id', '!=', $bike->id)->count();

        if ($currentBikes >= $station->capacity) {
            return redirect()->back()
                ->with('error', 'La estación ha alcanzado su capacidad máxima.');
        }

        $bike->update([
            'station_id' => $request->station_id,
            'status' => 'disponible'
        ]);

        return redirect()->route('bikes.index')
            ->with('success', 'Bicicleta movida a la estación exitosamente.');
    }

    /**
     * Update battery level for electric bikes
     */
    public function updateBattery(Request $request, Bike $bike)
    {
        if (Auth::user()->role !== 'admin') {
            return redirect()->route('bikes.index')
                ->with('error', 'No tienes permisos para actualizar la batería.');
        }

        if ($bike->type !== 'electrica') {
            return redirect()->back()
                ->with('error', 'Esta bicicleta no es eléctrica.');
        }

        $request->validate([
            'battery_level' => 'required|integer|min:0|max:100'
        ]);

        $bike->update(['battery_level' => $request->battery_level]);

        return redirect()->back()
            ->with('success', 'Nivel de batería actualizado exitosamente.');
    }

    /**
     * Get bikes data for API/AJAX calls
     */
    public function getData(Request $request)
    {
        $query = Bike::with('station');

        if ($request->filled('station_id')) {
            $query->where('station_id', $request->station_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bikes = $query->where('is_active', true)->get();

        return response()->json($bikes);
    }
}
