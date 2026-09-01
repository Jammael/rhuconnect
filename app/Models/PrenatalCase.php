<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrenatalCase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'patient_id',
        'midwife_id',
        'last_menstrual_period',
        'estimated_due_date',
        'gravida',
        'para',
        'is_high_risk',
        'risk_notes',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'last_menstrual_period' => 'date',
            'estimated_due_date' => 'date',
            'is_high_risk' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function midwife(): BelongsTo
    {
        return $this->belongsTo(User::class, 'midwife_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /**
     * Computed from last_menstrual_period against today's date — never
     * stored, since a stored trimester would silently go stale as time
     * passes without anyone updating this record.
     */
    protected function trimester(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $weeks = $this->last_menstrual_period->diffInWeeks(today());

                return match (true) {
                    $weeks < 13 => 'First',
                    $weeks < 28 => 'Second',
                    default => 'Third',
                };
            },
        );
    }
}
