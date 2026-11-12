<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validaciones_entrada', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrada_id')->nullable()->constrained('entradas')->nullOnDelete();
            $table->foreignId('operador_id')->constrained('users')->restrictOnDelete();
            $table->char('codigo_hash', 64);
            $table->string('resultado', 20);
            $table->string('motivo', 150);
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['resultado', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validaciones_entrada');
    }
};
