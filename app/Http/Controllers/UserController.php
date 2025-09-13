<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user'
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
}
