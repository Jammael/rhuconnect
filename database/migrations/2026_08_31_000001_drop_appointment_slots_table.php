<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('appointment_slots');
    }

    public function down(): void
    {
        // Intentionally not restored: appointment_slots was an unused,
        // superseded pre-generated-capacity design. See
        // 2026_07_29_000005_create_appointment_slots_table.php for the
        // original schema if this ever needs to be resurrected.
    }
};
