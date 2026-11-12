
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Publico\HomeController;
use App\Http\Controllers\Publico\EventoController;
use App\Http\Controllers\Publico\PaginaController;
use App\Http\Controllers\Auth\AutenticacionController;
use App\Http\Controllers\Compra\ReservaController;
use App\Http\Controllers\Compra\PagoController;
use App\Http\Controllers\Compra\EntradaController;
use App\Http\Controllers\Compra\ValidacionEntradaController;
use App\Http\Controllers\Organizador\EventoController as EventoOrganizadorController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UsuarioController as AdminUsuarioController;
use App\Http\Controllers\Admin\EventoController as AdminEventoController;


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

Route::get('/eventos/{evento}/comprar', [EventoController::class, 'comprar'])
    ->name('eventos.comprar');

Route::redirect('/home', '/');


Route::middleware('guest')->group(function () {
    Route::get('/registrarse', [AutenticacionController::class, 'mostrarRegistro'])
        ->name('registro');

    Route::post('/registrarse', [AutenticacionController::class, 'registrar'])
        ->name('registro.guardar');

    Route::get('/iniciar-sesion', [AutenticacionController::class, 'mostrarLogin'])
        ->name('login');

    Route::post('/iniciar-sesion', [AutenticacionController::class, 'ingresar'])
        ->middleware('throttle:5,1')
        ->name('login.ingresar');
});

Route::post('/cerrar-sesion', [AutenticacionController::class, 'salir'])
    ->middleware('auth')
    ->name('logout');


Route::middleware('auth')->group(function () {
    Route::post('/eventos/{evento}/reservar', [ReservaController::class, 'guardar'])
    ->middleware('auth')
    ->name('reservas.guardar');

    Route::get('/reservas/{reserva}/pago', [ReservaController::class, 'pago'])
    ->name('reservas.pago');

    Route::post('/reservas/{reserva}/pago', [PagoController::class, 'procesar'])
        ->middleware('throttle:5,1')
        ->name('reservas.pago.procesar');

    Route::get('/reservas/{reserva}', [ReservaController::class, 'mostrar'])
        ->name('reservas.mostrar');

    Route::post('/reservas/{reserva}/cancelar', [ReservaController::class, 'cancelar'])
        ->name('reservas.cancelar');

    Route::get('/mis-entradas', [EntradaController::class, 'index'])
        ->name('entradas.index');

    Route::get('/mis-entradas/{entrada}', [EntradaController::class, 'show'])
        ->name('entradas.show');

    Route::get('/validar-entradas', [ValidacionEntradaController::class, 'mostrar'])
        ->name('entradas.validar');

    Route::post('/validar-entradas', [ValidacionEntradaController::class, 'validar'])
        ->middleware('throttle:30,1')
        ->name('entradas.validar.procesar');
});

Route::middleware(['auth', 'rol:organizador'])->prefix('organizador')->name('organizador.')->group(function () {
    Route::get('/eventos', [EventoOrganizadorController::class, 'index'])
        ->name('eventos.index');
    Route::get('/eventos/crear', [EventoOrganizadorController::class, 'create'])
        ->name('eventos.create');
    Route::post('/eventos', [EventoOrganizadorController::class, 'store'])
        ->name('eventos.store');
    Route::get('/eventos/{evento}/editar', [EventoOrganizadorController::class, 'edit'])
        ->name('eventos.edit');
    Route::put('/eventos/{evento}', [EventoOrganizadorController::class, 'update'])
        ->name('eventos.update');
    Route::delete('/eventos/{evento}', [EventoOrganizadorController::class, 'destroy'])
        ->name('eventos.destroy');
});

Route::middleware(['auth', 'rol:administrador'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])
        ->name('dashboard');
    Route::get('/usuarios', [AdminUsuarioController::class, 'index'])
        ->name('usuarios.index');
    Route::patch('/usuarios/{usuario}/rol', [AdminUsuarioController::class, 'actualizarRol'])
        ->name('usuarios.rol');
    Route::put('/usuarios/{usuario}/eventos-acceso', [AdminUsuarioController::class, 'actualizarEventosAcceso'])
        ->name('usuarios.eventos-acceso');
    Route::get('/eventos', [AdminEventoController::class, 'index'])
        ->name('eventos.index');
    Route::patch('/eventos/{evento}/estado', [AdminEventoController::class, 'actualizarEstado'])
        ->name('eventos.estado');
});
