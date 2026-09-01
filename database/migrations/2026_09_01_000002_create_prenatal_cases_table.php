<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prenatal_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('midwife_id')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('last_menstrual_period');
            $table->date('estimated_due_date');
            $table->unsignedTinyInteger('gravida');
            $table->unsignedTinyInteger('para');
            $table->boolean('is_high_risk')->default(false);
            $table->text('risk_notes')->nullable();
            $table->enum('status', ['Active', 'Completed', 'Miscarried', 'Transferred'])->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['midwife_id', 'status']);
            $table->index('patient_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prenatal_cases');
    }
};
