
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organizador_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('titulo', 150);
            $table->string('slug', 180)->unique();
            $table->string('categoria', 30);
            $table->text('descripcion')->nullable();

            $table->dateTime('fecha_evento');
            $table->string('lugar', 150);
            $table->string('direccion', 180)->nullable();
            $table->string('ciudad', 80);

            $table->unsignedInteger('aforo_total');

            $table->string('imagen_principal')->nullable();
            $table->string('imagen_plano')->nullable();

            $table->string('estado', 20)->default('borrador');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
