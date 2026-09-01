<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vitals extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'vitals';

    protected $fillable = [
        'appointment_id',
        'recorded_by',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'temperature_celsius',
        'pulse_rate',
        'respiratory_rate',
        'oxygen_saturation',
        'weight_kg',
        'height_cm',
        'chief_complaint',
    ];

    protected function casts(): array
    {
        return [
            'temperature_celsius' => 'decimal:1',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:1',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
