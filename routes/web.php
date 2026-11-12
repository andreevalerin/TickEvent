
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Publico\HomeController;
use App\Http\Controllers\Publico\EventoController;
use App\Http\Controllers\Publico\PaginaController;


Route::get('/', [HomeController::class, 'index'])
    ->name('inicio');


Route::get('/eventos', [EventoController::class, 'index'])
    ->name('eventos.index');

Route::get('/eventos/conciertos', [EventoController::class, 'conciertos'])
    ->name('conciertos.index');

Route::get('/eventos/partidos', [EventoController::class, 'partidos'])
    ->name('partidos.index');


Route::get('/contacto', [PaginaController::class, 'contacto'])
    ->name('contacto');

Route::get('/politicas/cancelaciones', [PaginaController::class, 'cancelaciones'])
    ->name('politicas.cancelaciones');

Route::get('/politicas/reembolsos', [PaginaController::class, 'reembolsos'])
    ->name('politicas.reembolsos');

Route::get('/politicas/cambios-de-fecha', [PaginaController::class, 'cambiosFecha'])
    ->name('politicas.cambios-fecha');


Route::get('/eventos/{evento}', [EventoController::class, 'show'])
    ->where('evento', '[a-z0-9-]+')
    ->name('eventos.show');


Route::redirect('/home', '/');
