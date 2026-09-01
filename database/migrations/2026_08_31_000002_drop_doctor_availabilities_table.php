<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('doctor_availabilities');
    }

    public function down(): void
    {
        // Intentionally not restored: doctor_availabilities was the legacy
        // table superseded by doctor_availability_templates +
        // doctor_availability_exceptions. See
        // 2026_07_29_000004_create_doctor_availabilities_table.php for the
        // original schema if this ever needs to be resurrected.
    }
};
