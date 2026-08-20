<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_availability_id')
                ->constrained('doctor_availabilities')
                ->restrictOnDelete();
            $table->unsignedInteger('maximum_slots');
            $table->unsignedInteger('booked_slots')->default(0);
            $table->unsignedInteger('remaining_slots')->storedAs('maximum_slots - booked_slots');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('doctor_availability_id');
            $table->index('remaining_slots');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slots');
    }
};
