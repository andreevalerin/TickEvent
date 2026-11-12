<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UsuarioController extends Controller
{
    public function index()
    {
        $usuarios = User::with('eventosAsignadosAcceso')->orderBy('name')->get();
        $eventos = Evento::where('estado', '!=', 'retirado')
            ->orderBy('fecha_evento')
            ->get();

        return view('admin.usuarios.index', compact('usuarios', 'eventos'));
    }

    public function actualizarRol(Request $request, User $usuario)
    {
        $datos = $request->validate([
            'rol' => ['required', 'in:cliente,organizador,personal_acceso'],
        ]);

        if ($usuario->rol === 'administrador') {
            throw ValidationException::withMessages([
                'rol' => 'Las cuentas administradoras no pueden modificarse desde este formulario.',
            ]);
        }

        DB::transaction(function () use ($usuario, $datos) {
            $usuarioBloqueado = User::whereKey($usuario->id)->lockForUpdate()->firstOrFail();
            $usuarioBloqueado->forceFill(['rol' => $datos['rol']])->save();

            if ($datos['rol'] !== 'personal_acceso') {
                $usuarioBloqueado->eventosAsignadosAcceso()->detach();
            }
        }, 3);

        return back()->with('estado', 'Rol actualizado correctamente.');
    }

    public function actualizarEventosAcceso(Request $request, User $usuario)
    {
        if ($usuario->rol !== 'personal_acceso') {
            throw ValidationException::withMessages([
                'eventos' => 'Solo el personal de acceso puede recibir eventos asignados.',
            ]);
        }

        $datos = $request->validate([
            'eventos' => ['nullable', 'array'],
            'eventos.*' => ['integer', 'distinct', 'exists:eventos,id'],
        ]);

        $usuario->eventosAsignadosAcceso()->sync($datos['eventos'] ?? []);

        return back()->with('estado', 'Eventos de acceso actualizados.');
    }
}
