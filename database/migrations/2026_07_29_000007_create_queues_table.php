<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('queue_number', 30);
            $table->enum('priority_type', ['Regular', 'Senior Citizen', 'PWD', 'Pregnant'])->default('Regular');
            $table->enum('queue_status', ['Waiting', 'Serving', 'Completed', 'Skipped', 'Cancelled'])->default('Waiting');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('appointment_id');
            $table->index('queue_number');
            $table->index('priority_type');
            $table->index('queue_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
