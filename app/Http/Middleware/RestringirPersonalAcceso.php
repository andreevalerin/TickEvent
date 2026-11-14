<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestringirPersonalAcceso
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (!$usuario || $usuario->rol !== 'personal_acceso') {
            return $next($request);
        }

        if ($request->routeIs('entradas.validar', 'entradas.validar.procesar', 'logout')) {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return redirect()->route('entradas.validar');
        }

        abort(403);
    }
}
