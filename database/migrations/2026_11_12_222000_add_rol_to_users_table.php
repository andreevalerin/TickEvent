<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)->default('cliente')->index();
        });

        DB::table('users')
            ->whereIn('id', DB::table('eventos')->select('organizador_id'))
            ->update(['rol' => 'organizador']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['rol']);
            $table->dropColumn('rol');
        });
    }
};
