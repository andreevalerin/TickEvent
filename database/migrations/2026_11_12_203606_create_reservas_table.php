<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('evento_id')->constrained('eventos')->restrictOnDelete();
            $table->string('estado', 20)->default('activa');
            $table->dateTime('expira_en');
            $table->timestamps();

            $table->index(['estado', 'expira_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
