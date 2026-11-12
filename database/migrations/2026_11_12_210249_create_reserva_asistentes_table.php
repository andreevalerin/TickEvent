<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reserva_asistentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')
                ->constrained('reservas')
                ->restrictOnDelete();
            $table->string('dni', 8);
            $table->timestamps();

            $table->unique(['reserva_id', 'dni']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reserva_asistentes');
    }
};
