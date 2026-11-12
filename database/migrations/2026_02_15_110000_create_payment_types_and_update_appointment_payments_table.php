<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::table('appointment_payments', function (Blueprint $table) {
            $table->foreignId('payment_type_id')
                ->nullable()
                ->after('amount')
                ->constrained('payment_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('appointment_payments', function (Blueprint $table) {
            if (Schema::hasColumn('appointment_payments', 'payment_type_id')) {
                $table->dropConstrainedForeignId('payment_type_id');
            }
        });

        Schema::dropIfExists('payment_types');
    }
};

