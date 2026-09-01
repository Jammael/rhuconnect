<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Queue;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class QueueService
{
    private const PRIORITY_LANES = ['Senior Citizen', 'PWD', 'Pregnant'];

    private const ACTIVE_STATUSES = ['Waiting', 'Serving'];

    /**
     * Check in a today's Pending/Approved appointment, creating its Queue
     * row and generating a per-lane daily queue number. Fails with a normal
     * validation error (not a DB exception) if the appointment is already
     * checked in or not eligible.
     */
    public function checkIn(Appointment $appointment, string $priorityType): Queue
    {
        if (! $appointment->appointment_date->isToday()) {
            throw ValidationException::withMessages([
                'appointment' => 'Only appointments scheduled for today can be checked in.',
            ]);
        }

        if (! in_array($appointment->status, ['Pending', 'Approved'], true)) {
            throw ValidationException::withMessages([
                'appointment' => 'Only Pending or Approved appointments can be checked in.',
            ]);
        }

        if ($appointment->queue()->exists()) {
            throw ValidationException::withMessages([
                'appointment' => 'This appointment has already been checked in.',
            ]);
        }

        return Queue::create([
            'appointment_id' => $appointment->id,
            'queue_number' => $this->nextQueueNumber($priorityType, today()),
            'priority_type' => $priorityType,
            'queue_status' => 'Waiting',
        ]);
    }

    /**
     * Separate daily sequence per lane: Regular patients get R-001, R-002...
     * Senior Citizen/PWD/Pregnant share P-001, P-002... — the visibly
     * distinct priority lane convention used by Philippine RHUs.
     */
    public function nextQueueNumber(string $priorityType, CarbonInterface $today): string
    {
        $lane = in_array($priorityType, self::PRIORITY_LANES, true) ? 'P' : 'R';

        $laneTypes = $lane === 'P' ? self::PRIORITY_LANES : ['Regular'];

        $count = Queue::query()
            ->whereDate('created_at', $today)
            ->whereIn('priority_type', $laneTypes)
            ->count();

        return sprintf('%s-%03d', $lane, $count + 1);
    }

    /**
     * Serve the next Waiting patient: priority lanes ahead of Regular,
     * oldest check-in first within each lane.
     */
    public function callNext(): ?Queue
    {
        $next = $this->todaysQueueQuery()
            ->where('queue_status', 'Waiting')
            ->orderByRaw("CASE WHEN priority_type = 'Regular' THEN 1 ELSE 0 END")
            ->orderBy('id')
            ->first();

        if (! $next) {
            return null;
        }

        $next->update(['queue_status' => 'Serving']);

        return $next;
    }

    /**
     * Manual staff override for a Waiting/Serving entry (e.g. patient walked
     * out). Completed is never set here — it's only reached via
     * syncFromAppointmentStatus().
     */
    public function markStatus(Queue $queue, string $status): void
    {
        if (! in_array($status, ['Skipped', 'Cancelled'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Queue entries can only be manually marked Skipped or Cancelled.',
            ]);
        }

        if (! in_array($queue->queue_status, self::ACTIVE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'This queue entry has already been closed out.',
            ]);
        }

        $queue->update(['queue_status' => $status]);
    }

    /**
     * Keep the queue entry in sync when its appointment reaches a terminal
     * status — without this, a Waiting/Serving row would be silently
     * orphaned once its appointment is already closed out.
     */
    public function syncFromAppointmentStatus(Appointment $appointment, string $newStatus): void
    {
        $queue = $appointment->queue;

        if (! $queue || ! in_array($queue->queue_status, self::ACTIVE_STATUSES, true)) {
            return;
        }

        $mapped = match ($newStatus) {
            'Completed' => 'Completed',
            'No Show' => 'Skipped',
            'Cancelled' => 'Cancelled',
            default => null,
        };

        if ($mapped) {
            $queue->update(['queue_status' => $mapped]);
        }
    }

    private function todaysQueueQuery()
    {
        return Queue::query()
            ->whereHas('appointment', fn ($query) => $query->whereDate('appointment_date', today()));
    }
}
