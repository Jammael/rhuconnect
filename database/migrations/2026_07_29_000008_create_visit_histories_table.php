<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('doctor_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('diagnosis');
            $table->text('prescription')->nullable();
            $table->text('notes')->nullable();
            $table->date('consultation_date');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('appointment_id');
            $table->index(['doctor_id', 'consultation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_histories');
    }
};
