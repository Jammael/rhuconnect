<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            if (! Schema::hasColumn('patients', 'philhealth_id')) {
                $table->string('philhealth_id', 50)->nullable()->after('contact_number');
            }

            if (! Schema::hasColumn('patients', 'blood_type')) {
                $table->string('blood_type', 10)->nullable()->after('philhealth_id');
            }

            if (! Schema::hasColumn('patients', 'guardian_name')) {
                $table->string('guardian_name')->nullable()->after('blood_type');
            }

            if (! Schema::hasColumn('patients', 'guardian_contact')) {
                $table->string('guardian_contact', 20)->nullable()->after('guardian_name');
            }

            if (! Schema::hasColumn('patients', 'known_allergies')) {
                $table->text('known_allergies')->nullable()->after('guardian_contact');
            }

            if (! Schema::hasColumn('patients', 'existing_conditions')) {
                $table->text('existing_conditions')->nullable()->after('known_allergies');
            }

            if (! Schema::hasColumn('patients', 'current_medications')) {
                $table->text('current_medications')->nullable()->after('existing_conditions');
            }

            if (! Schema::hasColumn('patients', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('current_medications');
            }

            if (! Schema::hasColumn('patients', 'emergency_contact_number')) {
                $table->string('emergency_contact_number', 20)->nullable()->after('emergency_contact_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            foreach ([
                'philhealth_id',
                'blood_type',
                'guardian_name',
                'guardian_contact',
                'known_allergies',
                'existing_conditions',
                'current_medications',
                'emergency_contact_name',
                'emergency_contact_number',
            ] as $column) {
                if (Schema::hasColumn('patients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
