<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AutenticacionController extends Controller
{
    public function mostrarRegistro()
    {
        return view('auth.registro');
    }

    public function registrar(Request $request)
    {
        $datos = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password'])
        ]);

        Auth::login($usuario);

        $request->session()->regenerate();

        return redirect()->route('inicio');
    }

    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function ingresar(Request $request)
    {
        $datos = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($datos)) {
            $request->session()->regenerate();

            return redirect()->intended(route('inicio'));
        }

        return back()
            ->withErrors([
                'email' => 'Correo o contraseña incorrectos.'
            ])
            ->onlyInput('email');
    }

    public function salir(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('inicio');
    }
}
