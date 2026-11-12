<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_reserva', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->restrictOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifas')->restrictOnDelete();
            $table->unsignedTinyInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('cargo_servicio_unitario', 10, 2);
            $table->timestamps();

            $table->unique(['reserva_id', 'tarifa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_reserva');
    }
};
