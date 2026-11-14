@extends('layouts.organizador')
@section('titulo', 'Perfil de organizador - TickEvent')
@section('contenido')
<div class="panel-rol-contenido">
    <header class="admin-titulo"><div><span class="admin-etiqueta">Panel del organizador</span><h1>Perfil</h1><p>Información de la cuenta organizadora autenticada.</p></div></header>
    <section class="admin-panel organizador-perfil">
        <div class="admin-avatar organizador-avatar">{{ strtoupper(substr($usuario->name, 0, 2)) }}</div>
        <div><span>Nombre</span><strong>{{ $usuario->name }}</strong></div><div><span>Correo</span><strong>{{ $usuario->email }}</strong></div><div><span>Rol</span><strong>Organizador</strong></div><div><span>Eventos registrados</span><strong>{{ $totales['eventos'] }}</strong></div><div><span>Eventos publicados</span><strong>{{ $totales['publicados'] }}</strong></div>
    </section>
</div>
@endsection
