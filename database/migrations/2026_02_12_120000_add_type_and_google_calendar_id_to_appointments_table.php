<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * - type: presencial | virtual, default presencial (existentes quedan presencial).
     * - google_calendar_id: string nullable, sin integración aún.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('type', 20)->default('presencial')->after('payment_status');
            $table->string('google_calendar_id')->nullable()->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['type', 'google_calendar_id']);
        });
    }
};
