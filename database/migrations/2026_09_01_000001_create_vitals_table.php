<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('recorded_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->unsignedSmallInteger('blood_pressure_systolic');
            $table->unsignedSmallInteger('blood_pressure_diastolic');
            $table->decimal('temperature_celsius', 4, 1);
            $table->unsignedSmallInteger('pulse_rate');
            $table->unsignedSmallInteger('respiratory_rate');
            $table->unsignedTinyInteger('oxygen_saturation');
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('height_cm', 5, 1);
            $table->text('chief_complaint')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('appointment_id');
            $table->index('recorded_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};
