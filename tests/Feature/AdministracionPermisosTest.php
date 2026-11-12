<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\Tarifa;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('Resumen de TickEvent');

        $this->actingAs($administrador)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Usuarios y permisos');

        $this->actingAs($administrador)
            ->get(route('admin.eventos.index'))
            ->assertOk()
            ->assertSee('Eventos');
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
