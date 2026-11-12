<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_personal_acceso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['evento_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_personal_acceso');
    }
};
