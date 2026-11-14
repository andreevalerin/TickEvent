<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Evento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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

    public function create()
    {
        return view('admin.usuarios.form', [
            'usuario' => new User(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validarUsuario($request);

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => Hash::make($datos['password']),
        ]);
        $usuario->forceFill(['rol' => $datos['rol']])->save();

        return redirect()
            ->route('admin.usuarios.index')
            ->with('estado', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario)
    {
        $this->autorizarCuentaEditable($usuario);

        return view('admin.usuarios.form', compact('usuario'));
    }

    public function update(Request $request, User $usuario)
    {
        $this->autorizarCuentaEditable($usuario);
        $datos = $this->validarUsuario($request, $usuario);

        DB::transaction(function () use ($usuario, $datos) {
            $usuarioBloqueado = User::whereKey($usuario->id)
                ->lockForUpdate()
                ->firstOrFail();
            $usuarioBloqueado->name = $datos['name'];
            $usuarioBloqueado->email = $datos['email'];

            if (filled($datos['password'] ?? null)) {
                $usuarioBloqueado->password = Hash::make($datos['password']);
            }

            $usuarioBloqueado->forceFill(['rol' => $datos['rol']])->save();

            if ($datos['rol'] !== 'personal_acceso') {
                $usuarioBloqueado->eventosAsignadosAcceso()->detach();
            }
        }, 3);

        return redirect()
            ->route('admin.usuarios.index')
            ->with('estado', 'Usuario actualizado correctamente.');
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
            'eventos.*' => [
                'integer',
                'distinct',
                Rule::exists('eventos', 'id')->whereNot('estado', 'retirado'),
            ],
        ]);

        DB::transaction(function () use ($usuario, $datos) {
            $usuarioBloqueado = User::whereKey($usuario->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($usuarioBloqueado->rol !== 'personal_acceso') {
                throw ValidationException::withMessages([
                    'eventos' => 'Solo el personal de acceso puede recibir eventos asignados.',
                ]);
            }

            $usuarioBloqueado->eventosAsignadosAcceso()->sync($datos['eventos'] ?? []);
        }, 3);

        return back()->with('estado', 'Eventos de acceso actualizados.');
    }

    private function validarUsuario(Request $request, ?User $usuario = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'rol' => ['required', 'in:cliente,organizador,personal_acceso'],
        ]);
    }

    private function autorizarCuentaEditable(User $usuario): void
    {
        abort_if($usuario->rol === 'administrador', 403);
    }
}
