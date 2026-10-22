<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 * Query logic behind the Admin Reports & Analytics page — one method per
 * report, each scoped to a caller-supplied [start, end] date range so the
 * page's single date-range picker drives all four reports uniformly.
 */
class ReportsService
{
    private const STATUSES = ['Pending', 'Approved', 'Completed', 'Cancelled', 'No Show'];

    private const PRIORITY_TYPES = ['Regular', 'Senior Citizen', 'PWD', 'Pregnant'];

    public function __construct(
        private readonly DoctorAvailabilityService $availabilityService
    ) {}

    /**
     * Day-by-day appointment counts by status, plus the clinical no-show
     * rate: No Show ÷ (Completed + No Show) — excludes Pending/Approved
     * (unresolved) and Cancelled (called off ahead of time, not a no-show).
     */
    public function appointmentVolume(Carbon $start, Carbon $end): array
    {
        $byDate = [];

        foreach (CarbonPeriod::create($start, $end) as $date) {
            $byDate[$date->toDateString()] = array_merge(
                array_fill_keys(self::STATUSES, 0),
                ['date' => $date->toDateString(), 'total' => 0]
            );
        }

        $totals = array_fill_keys(self::STATUSES, 0);

        $this->scopeToDateRange(Appointment::query(), $start, $end)
            ->get(['appointment_date', 'status'])
            ->each(function (Appointment $appointment) use (&$byDate, &$totals): void {
                $dateKey = $appointment->appointment_date->toDateString();

                if (! isset($byDate[$dateKey])) {
                    return;
                }

                $byDate[$dateKey][$appointment->status]++;
                $byDate[$dateKey]['total']++;
                $totals[$appointment->status]++;
            });

        $resolved = $totals['Completed'] + $totals['No Show'];

        return [
            'days' => array_values($byDate),
            'totals' => array_merge($totals, ['total' => array_sum($totals)]),
            'no_show_rate' => $resolved > 0 ? round($totals['No Show'] / $resolved * 100, 1) : 0.0,
        ];
    }

    /**
     * Per active doctor: booked (Pending/Approved/Completed) appointments
     * vs. total capacity (booked + still-open slots from
     * DoctorAvailabilityService, the same source of truth the booking flow
     * uses). Cancelled/No Show are excluded from the ratio — they never
     * consumed a slot — but No Show is surfaced separately for context.
     */
    public function doctorUtilization(Carbon $start, Carbon $end): array
    {
        $doctors = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'Doctor'))
            ->where('account_status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        $period = CarbonPeriod::create($start, $end);

        $rows = $doctors->map(function (User $doctor) use ($period, $start, $end): array {
            $availableSlots = 0;

            foreach ($period as $date) {
                $availableSlots += count($this->availabilityService->getAvailableSlots($doctor, $date));
            }

            $booked = $this->scopeToDateRange(Appointment::query()->where('doctor_id', $doctor->id), $start, $end)
                ->whereNotIn('status', ['Cancelled', 'No Show'])
                ->count();

            $noShow = $this->scopeToDateRange(Appointment::query()->where('doctor_id', $doctor->id), $start, $end)
                ->where('status', 'No Show')
                ->count();

            $capacity = $booked + $availableSlots;

            return [
                'doctor' => $doctor->name,
                'booked' => $booked,
                'no_show' => $noShow,
                'capacity' => $capacity,
                'utilization_percent' => $capacity > 0 ? round($booked / $capacity * 100, 1) : 0.0,
            ];
        });

        return ['doctors' => $rows->values()->all()];
    }

    /**
     * Sex, age-bracket, and barangay breakdown for patients with at least
     * one appointment in range (deduplicated) — scoped to the range so the
     * shared date picker actually affects this report too, rather than
     * always reflecting the entire all-time patient registry.
     */
    public function patientDemographics(Carbon $start, Carbon $end): array
    {
        $patientIds = $this->scopeToDateRange(Appointment::query(), $start, $end)
            ->distinct()
            ->pluck('patient_id');

        $patients = Patient::query()->whereIn('id', $patientIds)->get();

        $sex = ['Male' => 0, 'Female' => 0];
        $ageBrackets = ['0-17' => 0, '18-59' => 0, '60+' => 0];
        $barangays = [];

        foreach ($patients as $patient) {
            $sex[$patient->sex] = ($sex[$patient->sex] ?? 0) + 1;

            if ($patient->age !== null) {
                $bracket = match (true) {
                    $patient->age < 18 => '0-17',
                    $patient->age < 60 => '18-59',
                    default => '60+',
                };
                $ageBrackets[$bracket]++;
            }

            $barangay = $patient->barangay ?: 'Not specified';
            $barangays[$barangay] = ($barangays[$barangay] ?? 0) + 1;
        }

        arsort($barangays);
        $topBarangays = array_slice($barangays, 0, 10, true);
        $otherCount = array_sum($barangays) - array_sum($topBarangays);

        if ($otherCount > 0) {
            $topBarangays['Other'] = $otherCount;
        }

        return [
            'total_patients' => $patients->count(),
            'sex' => $sex,
            'age_brackets' => $ageBrackets,
            'barangays' => $topBarangays,
        ];
    }

    /**
     * Check-in to completion duration (minutes) for Completed queue
     * entries whose appointment falls in range, overall and per priority
     * lane — created_at is check-in time, updated_at is the last status
     * write, which for a Completed row is the completion moment.
     */
    public function queueTurnaround(Carbon $start, Carbon $end): array
    {
        $queues = Queue::query()
            ->where('queue_status', 'Completed')
            ->whereHas('appointment', fn ($query) => $this->scopeToDateRange($query, $start, $end))
            ->get(['id', 'priority_type', 'created_at', 'updated_at']);

        $minutes = $queues->map(fn (Queue $queue) => $queue->created_at->diffInMinutes($queue->updated_at));

        $byPriority = [];
        foreach (self::PRIORITY_TYPES as $type) {
            $subset = $queues->where('priority_type', $type)
                ->map(fn (Queue $queue) => $queue->created_at->diffInMinutes($queue->updated_at));

            $byPriority[$type] = [
                'count' => $subset->count(),
                'avg_minutes' => $subset->isNotEmpty() ? round($subset->avg(), 1) : null,
            ];
        }

        return [
            'count' => $minutes->count(),
            'avg_minutes' => $minutes->isNotEmpty() ? round($minutes->avg(), 1) : null,
            'median_minutes' => $minutes->isNotEmpty() ? $this->median($minutes->all()) : null,
            'min_minutes' => $minutes->isNotEmpty() ? round($minutes->min(), 1) : null,
            'max_minutes' => $minutes->isNotEmpty() ? round($minutes->max(), 1) : null,
            'by_priority' => $byPriority,
        ];
    }

    /**
     * Constrain a query to appointment_date within [start, end], inclusive.
     * Uses whereDate() rather than whereBetween() on raw date strings so
     * the comparison is correct regardless of whether the driver stores
     * the 'date'-cast column with or without a time component (e.g.
     * SQLite stores it as a full datetime string, which would otherwise
     * sort outside a same-day upper bound in a plain string comparison).
     */
    private function scopeToDateRange($query, Carbon $start, Carbon $end)
    {
        return $query
            ->whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<=', $end->toDateString());
    }

    private function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return round(($values[$middle - 1] + $values[$middle]) / 2, 1);
        }

        return round((float) $values[$middle], 1);
    }
}
