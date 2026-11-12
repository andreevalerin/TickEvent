<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->constrained('reservas')->restrictOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifas')->restrictOnDelete();
            $table->string('dni', 8);
            $table->string('codigo', 40)->unique();
            $table->string('estado', 20)->default('emitida');
            $table->timestamp('usada_en')->nullable();
            $table->foreignId('validada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reserva_id', 'dni']);
            $table->index(['estado', 'usada_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entradas');
    }
};
