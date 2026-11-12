<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->date('date'); // Índice se crea más abajo
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', [
                'PENDIENTE',
                'CONFIRMADA',
                'CANCELADA',
                'NO_SHOW',
                'COMPLETADA',
                'REQUIERE_REAGENDAR'
            ])->default('PENDIENTE');
            $table->text('notes')->nullable();
            $table->string('reason', 500)->nullable();
            // Puede ser null si el cliente agendó por su cuenta
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            // Flag para saber si fue agendada por el cliente
            $table->boolean('booked_by_client')->default(false);
            $table->timestamps();
            
            // Índices (client_id y created_by ya tienen índice por foreignId)
            $table->index('date');
            $table->index('status');
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
