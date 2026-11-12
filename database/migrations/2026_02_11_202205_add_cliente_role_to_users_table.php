<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // public function up(): void
    // {
    //     // Modificar el enum para incluir PACIENTE
    //     DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'ASISTENTE', 'CLIENTE') DEFAULT 'ASISTENTE'");
    // }

    // /**
    //  * Reverse the migrations.
    //  */
    // public function down(): void
    // {
    //     // Revertir a solo ADMIN y ASISTENTE
    //     // Nota: Esto fallará si hay usuarios con rol PACIENTE
    //     DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'ASISTENTE') DEFAULT 'ASISTENTE'");
    // }
public function up(): void
        {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'ASISTENTE', 'CLIENTE') DEFAULT 'ASISTENTE'");
    }

    public function down(): void
    {
        // 1) Re-mapear los registros PACIENTE a un valor permitido
        DB::table('users')
            ->where('role', 'CLIENTE')
            ->update(['role' => 'ASISTENTE']);

        // 2) Ahora sí, reducir el ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('ADMIN', 'ASISTENTE') DEFAULT 'ASISTENTE'");
    }
};
