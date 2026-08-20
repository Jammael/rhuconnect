<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentSlot extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'doctor_availability_id',
        'maximum_slots',
        'booked_slots',
    ];

    protected function casts(): array
    {
        return [
            'maximum_slots' => 'integer',
            'booked_slots' => 'integer',
            'remaining_slots' => 'integer',
        ];
    }

    public function doctorAvailability(): BelongsTo
    {
        return $this->belongsTo(DoctorAvailability::class);
    }
}
