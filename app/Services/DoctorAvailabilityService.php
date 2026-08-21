<?php

namespace App\Services;

use App\Models\DoctorAvailabilityException;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DoctorAvailabilityService
{
    /**
     * Compute bookable slots for a given doctor and date.
     *
     * Algorithm:
     * 1. Check if date-specific exception exists for this doctor.
     *    - If is_available = false (leave/holiday/etc.) -> returns empty array [].
     *    - If is_available = true with custom start_time/end_time -> uses those custom hours.
     * 2. If no exception (or exception is available without custom hours):
     *    - Look up recurring day_of_week template (0=Sun, 1=Mon, ..., 6=Sat).
     *    - If no template found or is_active = false -> returns empty array [].
     * 3. Divide time range into slot_duration_minutes increments.
     *
     * NOTE: This does NOT yet exclude already-booked appointment slots,
     * since the Appointments module doesn't exist yet — this will need to be
     * extended later to subtract booked slots once appointments.doctor_id + date + time exist.
     *
     * @param User $doctor
     * @param CarbonInterface|string $date
     * @return array<string> Array of time slots formatted as ['08:00', '08:30', ...]
     */
    public function getAvailableSlots(User $doctor, CarbonInterface|string $date): array
    {
        $carbonDate = is_string($date) ? Carbon::parse($date) : $date->copy();
        $dateString = $carbonDate->format('Y-m-d');
        $dayOfWeek = (int) $carbonDate->dayOfWeek; // 0 (Sun) to 6 (Sat)

        // 1. Check date-specific exception
        $exception = DoctorAvailabilityException::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', $dateString)
            ->first();

        if ($exception) {
            if (! $exception->is_available) {
                // Doctor is explicitly unavailable on this date (e.g. Leave, Holiday)
                return [];
            }

            // Doctor is available with custom hours on this date
            if ($exception->start_time && $exception->end_time) {
                $template = DoctorAvailabilityTemplate::query()
                    ->where('doctor_id', $doctor->id)
                    ->where('day_of_week', $dayOfWeek)
                    ->first();

                $slotDuration = $template?->slot_duration_minutes ?? 30;

                return $this->generateSlots(
                    $carbonDate,
                    $exception->start_time,
                    $exception->end_time,
                    $slotDuration
                );
            }
        }

        // 2. Look up weekly recurring template
        $template = DoctorAvailabilityTemplate::query()
            ->where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (! $template || ! $template->is_active) {
            return [];
        }

        return $this->generateSlots(
            $carbonDate,
            $template->start_time,
            $template->end_time,
            $template->slot_duration_minutes
        );
    }

    /**
     * Generate formatted time slots given start/end time and duration.
     *
     * @return array<string>
     */
    public function generateSlots(
        CarbonInterface $date,
        string $startTime,
        string $endTime,
        int $durationMinutes
    ): array {
        if ($durationMinutes <= 0) {
            $durationMinutes = 30;
        }

        $dateString = $date->format('Y-m-d');
        $start = Carbon::parse("{$dateString} {$startTime}");
        $end = Carbon::parse("{$dateString} {$endTime}");

        if ($start->gte($end)) {
            return [];
        }

        $slots = [];
        $current = $start->copy();

        while ($current->copy()->addMinutes($durationMinutes)->lte($end)) {
            $slots[] = $current->format('H:i');
            $current->addMinutes($durationMinutes);
        }

        return $slots;
    }

    /**
     * Generate a quick human-readable summary of a doctor's weekly template schedule.
     * E.g. "Mon, Tue, Wed, Thu, Fri • 08:00 AM - 05:00 PM (30m slots)"
     */
    public function getWeeklyScheduleSummary(User $doctor): string
    {
        $templates = DoctorAvailabilityTemplate::query()
            ->where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->get();

        if ($templates->isEmpty()) {
            return 'No active weekly schedule';
        }

        $dayNames = [
            0 => 'Sun',
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat',
        ];

        $activeDays = $templates->pluck('day_of_week')->all();
        $dayLabels = array_map(fn ($d) => $dayNames[$d] ?? (string) $d, $activeDays);
        $daysString = implode(', ', $dayLabels);

        $sample = $templates->first();
        $start = Carbon::parse($sample->start_time)->format('h:i A');
        $end = Carbon::parse($sample->end_time)->format('h:i A');
        $duration = $sample->slot_duration_minutes;

        return "{$daysString} • {$start} - {$end} ({$duration}m slots)";
    }
}

