<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('appointment_id')
                ->constrained()
                ->restrictOnDelete();
            $table->text('message');
            $table->string('recipient_number', 20);
            $table->enum('delivery_status', ['Pending', 'Sent', 'Failed'])->default('Pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('patient_id');
            $table->index('appointment_id');
            $table->index('delivery_status');
            $table->index('sent_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_notifications');
    }
};
