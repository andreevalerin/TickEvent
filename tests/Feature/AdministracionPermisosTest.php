<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdministracionPermisosTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_registro_publico_siempre_crea_clientes_aunque_envien_otro_rol(): void
    {
        $respuesta = $this->post(route('registro.guardar'), [
            'name' => 'Usuario nuevo',
            'email' => 'nuevo@tickevent.test',
            'password' => 'clave-segura-123',
            'password_confirmation' => 'clave-segura-123',
            'rol' => 'administrador',
        ]);

        $respuesta->assertRedirect(route('inicio'));
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@tickevent.test',
            'rol' => 'cliente',
        ]);

        $this->get('/organizadores/registrarse')->assertNotFound();
    }

    public function test_un_usuario_no_administrador_no_puede_asignar_roles(): void
    {
        $cliente = User::factory()->create();
        $objetivo = User::factory()->create();

        $this->actingAs($cliente)
            ->patch(route('admin.usuarios.rol', $objetivo), [
                'rol' => 'organizador',
            ])
            ->assertForbidden();

        $this->assertSame('cliente', $objetivo->fresh()->rol);
    }

    public function test_solo_el_administrador_puede_abrir_el_panel_administrativo(): void
    {
        $cliente = User::factory()->create();
        $administrador = $this->crearUsuarioConRol('administrador');

        $this->actingAs($cliente)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($administrador)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard');

        $this->actingAs($administrador)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Usuarios y permisos');

        $this->actingAs($administrador)
            ->get(route('admin.eventos.index'))
            ->assertOk()
            ->assertSee('Eventos');

        $this->actingAs($administrador)
            ->get(route('admin.entradas.index'))
            ->assertOk()
            ->assertSee('Entradas');

        $this->actingAs($administrador)
            ->get(route('admin.ventas.index'))
            ->assertOk()
            ->assertSee('Ventas');

        foreach (['admin.dashboard', 'admin.eventos.index', 'admin.entradas.index', 'admin.ventas.index', 'admin.usuarios.index'] as $ruta) {
            $this->actingAs($cliente)
                ->get(route($ruta))
                ->assertForbidden();
        }
    }

    public function test_el_administrador_asigna_organizadores_y_personal_de_acceso(): void
    {
        $administrador = $this->crearUsuarioConRol('administrador');
        $organizador = User::factory()->create();
        $personal = User::factory()->create();

        $this->actingAs($administrador)
            ->patch(route('admin.usuarios.rol', $organizador), [
                'rol' => 'organizador',
            ])
            ->assertRedirect();

        $this->actingAs($administrador)
            ->patch(route('admin.usuarios.rol', $personal), [
                'rol' => 'personal_acceso',
            ])
            ->assertRedirect();

        $this->assertSame('organizador', $organizador->fresh()->rol);
        $this->assertSame('personal_acceso', $personal->fresh()->rol);
    }

    public function test_el_login_redirige_cada_rol_a_su_panel(): void
    {
        $administrador = $this->crearUsuarioConRol('administrador');
        $organizador = $this->crearUsuarioConRol('organizador');
        $personal = $this->crearUsuarioConRol('personal_acceso');
        $cliente = User::factory()->create();
        $evento = $this->crearEvento($organizador);
        $personal->eventosAsignadosAcceso()->attach($evento->id);

        $destinos = [
            [$administrador, route('admin.dashboard')],
            [$organizador, route('organizador.eventos.index')],
            [$personal, route('entradas.validar')],
            [$cliente, route('entradas.index')],
        ];

        foreach ($destinos as [$usuario, $destino]) {
            $usuario->forceFill(['password' => Hash::make('clave-segura-123')])->save();
            $this->post(route('login.ingresar'), [
                'email' => $usuario->email,
                'password' => 'clave-segura-123',
            ])->assertRedirect($destino);
            $this->post(route('logout'))->assertRedirect(route('inicio'));
        }
    }

    public function test_el_administrador_crea_y_actualiza_usuarios_sin_permitir_administradores(): void
    {
        $administrador = $this->crearUsuarioConRol('administrador');

        $this->actingAs($administrador)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Organizador nuevo',
                'email' => 'organizador@tickevent.test',
                'password' => 'clave-segura-123',
                'password_confirmation' => 'clave-segura-123',
                'rol' => 'organizador',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $usuario = User::where('email', 'organizador@tickevent.test')->firstOrFail();
        $this->assertSame('organizador', $usuario->rol);

        $this->actingAs($administrador)
            ->put(route('admin.usuarios.update', $usuario), [
                'name' => 'Personal actualizado',
                'email' => 'personal@tickevent.test',
                'password' => '',
                'password_confirmation' => '',
                'rol' => 'personal_acceso',
            ])
            ->assertRedirect(route('admin.usuarios.index'));

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'name' => 'Personal actualizado',
            'email' => 'personal@tickevent.test',
            'rol' => 'personal_acceso',
        ]);

        $this->actingAs($administrador)
            ->put(route('admin.usuarios.update', $usuario), [
                'name' => 'Intento administrador',
                'email' => 'personal@tickevent.test',
                'password' => '',
                'password_confirmation' => '',
                'rol' => 'administrador',
            ])
            ->assertSessionHasErrors('rol');

        $this->assertSame('personal_acceso', $usuario->fresh()->rol);
    }

    public function test_un_cliente_no_puede_crear_ni_editar_usuarios_desde_rutas_administrativas(): void
    {
        $cliente = User::factory()->create();
        $objetivo = User::factory()->create();

        $this->actingAs($cliente)
            ->get(route('admin.usuarios.create'))
            ->assertForbidden();

        $this->actingAs($cliente)
            ->put(route('admin.usuarios.update', $objetivo), [
                'name' => 'Cambio no autorizado',
                'email' => $objetivo->email,
                'rol' => 'organizador',
            ])
            ->assertForbidden();

        $this->assertSame('cliente', $objetivo->fresh()->rol);
    }

    public function test_ningun_formulario_de_permisos_puede_crear_administradores(): void
    {
        $administrador = $this->crearUsuarioConRol('administrador');
        $cliente = User::factory()->create();

        $this->actingAs($administrador)
            ->patch(route('admin.usuarios.rol', $cliente), [
                'rol' => 'administrador',
            ])
            ->assertSessionHasErrors('rol');

        $this->assertSame('cliente', $cliente->fresh()->rol);

        $this->actingAs($administrador)
            ->patch(route('admin.usuarios.rol', $administrador), [
                'rol' => 'cliente',
            ])
            ->assertSessionHasErrors('rol');

        $this->assertSame('administrador', $administrador->fresh()->rol);
    }

    public function test_solo_el_administrador_asigna_eventos_al_personal_de_acceso(): void
    {
        $administrador = $this->crearUsuarioConRol('administrador');
        $organizador = $this->crearUsuarioConRol('organizador');
        $personal = $this->crearUsuarioConRol('personal_acceso');
        $evento = $this->crearEvento($organizador);

        $this->actingAs($organizador)
            ->put(route('admin.usuarios.eventos-acceso', $personal), [
                'eventos' => [$evento->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('evento_personal_acceso', 0);

        $this->actingAs($administrador)
            ->put(route('admin.usuarios.eventos-acceso', $personal), [
                'eventos' => [$evento->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('evento_personal_acceso', [
            'evento_id' => $evento->id,
            'usuario_id' => $personal->id,
        ]);
    }

    public function test_no_se_puede_asignar_un_evento_retirado_al_personal_de_acceso(): void
    {
        $administrador = $this->crearUsuarioConRol('administrador');
        $organizador = $this->crearUsuarioConRol('organizador');
        $personal = $this->crearUsuarioConRol('personal_acceso');
        $evento = $this->crearEvento($organizador);
        $evento->update(['estado' => 'retirado']);

        $this->actingAs($administrador)
            ->put(route('admin.usuarios.eventos-acceso', $personal), [
                'eventos' => [$evento->id],
            ])
            ->assertSessionHasErrors('eventos.0');

        $this->assertDatabaseCount('evento_personal_acceso', 0);
    }

    public function test_el_administrador_retira_un_evento_sin_eliminar_sus_datos(): void
    {
        $administrador = $this->crearUsuarioConRol('administrador');
        $organizador = $this->crearUsuarioConRol('organizador');
        $evento = $this->crearEvento($organizador);
        $zona = Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'General',
            'capacidad' => 100,
            'activo' => true,
        ]);
        $tarifa = Tarifa::create([
            'zona_id' => $zona->id,
            'nombre' => 'Regular',
            'precio' => 80,
            'activo' => true,
        ]);

        $this->actingAs($administrador)
            ->patch(route('admin.eventos.estado', $evento), [
                'estado' => 'retirado',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('eventos', [
            'id' => $evento->id,
            'estado' => 'retirado',
        ]);
        $this->assertDatabaseHas('zonas', [
            'id' => $zona->id,
            'activo' => false,
        ]);
        $this->assertDatabaseHas('tarifas', [
            'id' => $tarifa->id,
            'activo' => false,
        ]);

        $this->actingAs($administrador)
            ->patch(route('admin.eventos.estado', $evento), [
                'estado' => 'publicado',
            ])
            ->assertSessionHasErrors('estado');

        $this->assertSame('retirado', $evento->fresh()->estado);
    }

    public function test_no_se_publica_un_evento_con_una_zona_activa_sin_tarifa_activa(): void
    {
        $administrador = $this->crearUsuarioConRol('administrador');
        $organizador = $this->crearUsuarioConRol('organizador');
        $evento = $this->crearEvento($organizador);
        $evento->update(['estado' => 'borrador']);
        Zona::create([
            'evento_id' => $evento->id,
            'nombre' => 'Zona sin tarifa',
            'capacidad' => 100,
            'activo' => true,
        ]);

        $this->actingAs($administrador)
            ->patch(route('admin.eventos.estado', $evento), [
                'estado' => 'publicado',
            ])
            ->assertSessionHasErrors('estado');

        $this->assertSame('borrador', $evento->fresh()->estado);
    }

    private function crearUsuarioConRol(string $rol): User
    {
        $usuario = User::factory()->create();
        $usuario->forceFill(['rol' => $rol])->save();

        return $usuario;
    }

    private function crearEvento(User $organizador): Evento
    {
        return Evento::create([
            'organizador_id' => $organizador->id,
            'titulo' => 'Evento administrado',
            'slug' => 'evento-admin-'.Str::lower(Str::random(10)),
            'categoria' => 'concierto',
            'fecha_evento' => now()->addMonth(),
            'lugar' => 'Estadio de prueba',
            'ciudad' => 'Lima',
            'aforo_total' => 100,
            'estado' => 'publicado',
        ]);
    }
}
