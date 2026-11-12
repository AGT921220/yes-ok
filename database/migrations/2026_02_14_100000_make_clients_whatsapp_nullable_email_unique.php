<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cliente debe tener correo o teléfono (uno obligatorio). Cada uno único en clients y users.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('whatsapp', 20)->nullable()->change();
            $table->string('email')->nullable()->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('whatsapp', 20)->nullable(false)->change();
            $table->dropUnique(['email']);
            $table->string('email')->nullable()->change();
        });
    }
};
