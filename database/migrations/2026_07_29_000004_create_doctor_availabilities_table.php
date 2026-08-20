<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('available_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['Available', 'Unavailable'])->default('Available');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['doctor_id', 'available_date', 'start_time', 'end_time'],
                'doctor_availability_unique'
            );
            $table->index(['doctor_id', 'available_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_availabilities');
    }
};
