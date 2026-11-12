<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('forced')->default(false)->after('booked_by_client');
            $table->string('attendance_status', 20)->default('pendiente')->after('forced');
            $table->string('payment_status', 20)->default('sin_pagar')->after('attendance_status');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['forced', 'attendance_status', 'payment_status']);
        });
    }
};
