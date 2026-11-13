<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CatalogoPublico
{
    public function eventos(string $busqueda = '', ?string $categoria = null): Builder
    {
        return Evento::where('estado', 'publicado')
            ->withMin(['tarifas as precio_desde' => function (Builder $consulta): void {
                $consulta->where('tarifas.activo', true)->where('zonas.activo', true);
            }], 'precio')
            ->when($categoria, fn (Builder $consulta): Builder => $consulta->where('categoria', $categoria))
            ->when($busqueda !== '', function (Builder $consulta) use ($busqueda): void {
                $consulta->where(function (Builder $filtro) use ($busqueda): void {
                    $filtro->where('titulo', 'like', '%'.$busqueda.'%')
                        ->orWhere('lugar', 'like', '%'.$busqueda.'%')
                        ->orWhere('ciudad', 'like', '%'.$busqueda.'%');
                });
            })
            ->orderBy('fecha_evento')
            ->orderBy('id');
    }

    /** @return Collection<int, array{id: int, zona: string, tarifa: string, precio: string, disponibles: int}> */
    public function sectores(Evento $evento): Collection
    {
        $evento->loadMissing('zonas.tarifas');

        $ocupadas = DB::table('detalle_reserva as d')
            ->join('reservas as r', 'r.id', '=', 'd.reserva_id')
            ->join('tarifas as t', 't.id', '=', 'd.tarifa_id')
            ->where('r.evento_id', $evento->id)
            ->where(function (QueryBuilder $consulta): void {
                $consulta->whereIn('r.estado', ['confirmada', 'procesando_pago'])
                    ->orWhere(function (QueryBuilder $activas): void {
                        $activas->where('r.estado', 'activa')->where('r.expira_en', '>', now());
                    });
            })
            ->groupBy('t.zona_id')
            ->selectRaw('t.zona_id, SUM(d.cantidad) as cantidad')
            ->pluck('cantidad', 't.zona_id');

        $disponiblesEvento = $evento->fecha_evento->isPast()
            ? 0
            : max(0, $evento->aforo_total - (int) $ocupadas->sum());

        return $evento->zonas->where('activo', true)->sortBy('id')
            ->flatMap(function (Zona $zona) use ($ocupadas, $disponiblesEvento): Collection {
                $disponibles = max(0, min($disponiblesEvento, $zona->capacidad - (int) $ocupadas->get($zona->id, 0)));

                return $zona->tarifas->where('activo', true)->sortBy('id')->map(fn (Tarifa $tarifa): array => [
                    'id' => $tarifa->id,
                    'zona' => $zona->nombre,
                    'tarifa' => $tarifa->nombre,
                    'precio' => $tarifa->precio,
                    'disponibles' => $disponibles,
                ]);
            })->values();
    }
}
