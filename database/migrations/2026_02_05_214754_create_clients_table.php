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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('first_name'); // Nombre(s)
            $table->string('paternal_surname'); // Apellido Paterno
            $table->string('maternal_surname')->nullable(); // Apellido Materno (opcional)
            $table->string('whatsapp', 20)->unique(); // WhatsApp (identificador único) - unique() ya crea índice
            $table->string('email')->nullable();
            $table->boolean('consent_notifications')->default(true);
            $table->boolean('otp_verified')->default(false);
            // Token para acceso al portal de cliente (magic link)
            $table->string('access_token', 255)->nullable()->index();
            $table->timestamp('access_token_expires')->nullable();
            $table->timestamps();
            
            // Nota: whatsapp ya tiene índice por unique(), access_token ya tiene índice en línea 24
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
