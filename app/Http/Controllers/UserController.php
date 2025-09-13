<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UserController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            return response()->json([
                'success' => true,
                'user' => Auth::user(),
                'role' => Auth::user()->role
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Credenciales incorrectas'
        ], 401);
    }
    public function register(Request $request)
    {
        // Validación de los datos
        $request->validate([
            'name' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'dpi' => 'required|string|size:13|unique:users',
            'fecha_nacimiento' => 'required|date|before:today',
            'telefono' => 'nullable|string|max:15',
            'password' => 'required|string|min:6',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ], [
            'name.required' => 'El nombre es obligatorio',
            'apellidos.required' => 'Los apellidos son obligatorios',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe tener un formato válido',
            'email.unique' => 'Este email ya está registrado',
            'dpi.required' => 'El DPI es obligatorio',
            'dpi.size' => 'El DPI debe tener exactamente 13 dígitos',
            'dpi.unique' => 'Este DPI ya está registrado',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'password.required' => 'La contraseña es obligatoria',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'foto.image' => 'El archivo debe ser una imagen',
            'foto.mimes' => 'La imagen debe ser de tipo: jpeg, png, jpg, gif',
            'foto.max' => 'La imagen no debe superar los 2MB',
        ]);

        // Manejar la subida de foto
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('fotos_perfil', 'public');
        }

        // Crear el usuario con rol 'user' por defecto
        $user = User::create([
            'name' => $request->name,
            'apellidos' => $request->apellidos,
            'email' => $request->email,
            'dpi' => $request->dpi,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password),
            'foto' => $fotoPath,
            'role' => 'user' // Siempre será user en el registro público
        ]);

        Auth::login($user);

        return response()->json([
            'success' => true,
            'user' => $user,
            'role' => $user->role
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['success' => true]);
    }

    public function dashboard()
    {
        return view('dashboard');
    }

    public function getUser()
    {
        return response()->json([
            'user' => Auth::user(),
            'role' => Auth::user()->role ?? null
        ]);
    }

    public function getUsersCatalog(Request $request)
    {
        // Solo admin puede ver el catálogo
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $query = User::query();

        // Búsqueda por nombre, apellidos, email o DPI
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('apellidos', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('dpi', 'like', "%{$search}%");
            });
        }

        $users = $query->select([
            'id', 'name', 'apellidos', 'email', 'dpi',
            'fecha_nacimiento', 'telefono', 'foto', 'role', 'created_at'
        ])->orderBy('created_at', 'desc')->get();

        // Agregar URL completa para las fotos
        $users->transform(function ($user) {
            if ($user->foto) {
                $user->foto_url = Storage::url($user->foto);
            }
            return $user;
        });

        return response()->json(['users' => $users]);
    }

    public function createAdmin(Request $request)
    {
        // Solo un admin puede crear otro admin
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'dpi' => 'required|string|size:13|unique:users',
            'fecha_nacimiento' => 'required|date|before:today',
            'telefono' => 'nullable|string|max:15',
            'password' => 'required|string|min:6',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'apellidos.required' => 'Los apellidos son obligatorios',
            'email.unique' => 'Este email ya está registrado',
            'dpi.unique' => 'Este DPI ya está registrado',
            'dpi.size' => 'El DPI debe tener exactamente 13 dígitos',
            'fecha_nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
        ]);

        // Manejar la subida de foto
        $fotoPath = null;
        if ($request->hasFile('foto')) {
            $fotoPath = $request->file('foto')->store('fotos_perfil', 'public');
        }

        $admin = User::create([
            'name' => $request->name,
            'apellidos' => $request->apellidos,
            'email' => $request->email,
            'dpi' => $request->dpi,
            'fecha_nacimiento' => $request->fecha_nacimiento,
            'telefono' => $request->telefono,
            'password' => Hash::make($request->password),
            'foto' => $fotoPath,
            'role' => 'admin' // Explícitamente asignar admin
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Administrador creado exitosamente',
            'admin' => $admin
        ]);
    }
}
