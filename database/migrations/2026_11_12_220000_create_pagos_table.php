<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reserva_id')->unique()->constrained('reservas')->restrictOnDelete();
            $table->string('proveedor', 30)->default('culqi');
            $table->string('estado', 30);
            $table->unsignedInteger('monto_centimos');
            $table->string('moneda', 3)->default('PEN');
            $table->string('proveedor_pago_id', 80)->nullable()->unique();
            $table->string('codigo_respuesta', 80)->nullable();
            $table->string('mensaje', 500)->nullable();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('procesado_en')->nullable();
            $table->timestamps();

            $table->index(['estado', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
