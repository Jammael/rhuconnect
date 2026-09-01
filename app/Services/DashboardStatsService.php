<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\DoctorAvailabilityException;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\SmsNotification;
use App\Models\User;
use App\Models\Vitals;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Real, DB-backed queries for the 5 role dashboards' stat cards, tables,
 * and secondary lists. Organized per role so it's clear which method
 * serves which dashboard — see DashboardController for wiring.
 *
 * Modules that don't exist yet (maternal-care tracking, per-user entry
 * attribution) are represented honestly as "not yet active" placeholders
 * rather than fabricated numbers derived from unrelated data.
 */
class DashboardStatsService
{
    private const STATUS_TONES = [
        'Pending' => 'amber',
        'Approved' => 'green',
        'Completed' => 'blue',
        'Cancelled' => 'gray',
        'No Show' => 'red',
    ];

    private const QUEUE_STATUS_TONES = [
        'Waiting' => 'amber',
        'Serving' => 'blue',
        'Completed' => 'green',
        'Skipped' => 'gray',
        'Cancelled' => 'gray',
    ];

    private const PRIORITY_LANES = ['Senior Citizen', 'PWD', 'Pregnant'];

    // -------------------------------------------------------------------
    // Administrator
    // -------------------------------------------------------------------

    /**
     * Stat cards for the Administrator dashboard, excluding "Available
     * Doctors" which is already computed by DashboardController's
     * adminDoctorAvailability() and shares data with the secondary card.
     */
    public function adminStats(): array
    {
        $today = today();

        return [
            [
                'label' => "Today's Appointments",
                'value' => (string) $this->todaysAppointmentsQuery($today)->count(),
                'caption' => 'Clinic-wide today',
                'tone' => 'green',
            ],
            [
                'label' => 'Patients in Queue',
                'value' => (string) $this->todaysQueueQuery()->whereIn('queue_status', ['Waiting', 'Serving'])->count(),
                'caption' => 'Waiting or being served',
                'tone' => 'orange',
            ],
            [
                'label' => 'Completed Visits',
                'value' => (string) $this->todaysAppointmentsQuery($today)->where('status', 'Completed')->count(),
                'caption' => 'Completed today',
                'tone' => 'blue',
            ],
        ];
    }

    /**
     * Primary table for the Administrator dashboard: today's appointments,
     * clinic-wide, across all doctors.
     */
    public function adminTodaysAppointments(int $limit = 5): array
    {
        $appointments = $this->todaysAppointmentsQuery(today())
            ->with('patient')
            ->orderBy('appointment_time')
            ->limit($limit)
            ->get();

        return [
            'title' => "Today's Appointments",
            'columns' => ['Patient', 'Service', 'Time', 'Status'],
            'rows' => $this->appointmentRows($appointments),
            'empty_message' => 'No appointments scheduled today.',
        ];
    }

    // -------------------------------------------------------------------
    // Doctor
    // -------------------------------------------------------------------

    public function doctorStats(User $doctor): array
    {
        $today = today();
        $ownToday = fn () => $this->todaysAppointmentsQuery($today)->where('doctor_id', $doctor->id);

        $next = $ownToday()
            ->whereIn('status', ['Pending', 'Approved'])
            ->where('appointment_time', '>=', now()->format('H:i:s'))
            ->orderBy('appointment_time')
            ->first();

        return [
            [
                'label' => "Today's Appointments",
                'value' => (string) $ownToday()->count(),
                'caption' => 'Assigned to you',
                'tone' => 'green',
            ],
            $this->notYetActiveStat('Patients Waiting', 'Smart Queue module not yet active'),
            [
                'label' => 'Completed Today',
                'value' => (string) $ownToday()->where('status', 'Completed')->count(),
                'caption' => 'Consultations closed',
                'tone' => 'blue',
            ],
            [
                'label' => 'Next Appointment',
                'value' => $next ? Carbon::parse($next->appointment_time)->format('h:i A') : 'None today',
                'caption' => $next ? 'Upcoming consult' : 'Nothing else scheduled',
                'tone' => 'purple',
            ],
        ];
    }

    public function doctorTodaysSchedule(User $doctor, int $limit = 5): array
    {
        $appointments = $this->todaysAppointmentsQuery(today())
            ->where('doctor_id', $doctor->id)
            ->with('patient')
            ->orderBy('appointment_time')
            ->limit($limit)
            ->get();

        return [
            'title' => 'My Schedule Today',
            'columns' => ['Patient', 'Purpose', 'Time', 'Status'],
            'rows' => $this->appointmentRows($appointments),
            'empty_message' => 'No appointments scheduled today.',
        ];
    }

    /**
     * Real snapshot of the doctor's own availability for today, based on
     * DoctorAvailabilityTemplate/DoctorAvailabilityException — same data
     * source as DashboardController::adminDoctorAvailability(), just
     * rendered as a one-item detail instead of a per-doctor boolean.
     */
    public function doctorAvailabilityStatus(User $doctor): array
    {
        $today = today();
        $dayOfWeek = (int) $today->dayOfWeek;

        $exception = DoctorAvailabilityException::query()
            ->where('doctor_id', $doctor->id)
            ->whereDate('date', $today)
            ->first();

        if ($exception && ! $exception->is_available) {
            return ['items' => [[
                'title' => "Today's Schedule",
                'subtitle' => $exception->reason ?: 'Unavailable today',
                'status' => ['label' => 'Unavailable', 'tone' => 'gray'],
            ]]];
        }

        if ($exception && $exception->start_time && $exception->end_time) {
            $start = Carbon::parse($exception->start_time)->format('h:i A');
            $end = Carbon::parse($exception->end_time)->format('h:i A');

            return ['items' => [[
                'title' => "Today's Schedule (Custom Hours)",
                'subtitle' => "{$start} - {$end}",
                'status' => ['label' => 'Available', 'tone' => 'green'],
            ]]];
        }

        $template = DoctorAvailabilityTemplate::query()
            ->where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (! $template || ! $template->is_active) {
            return ['items' => [[
                'title' => 'No Schedule Today',
                'subtitle' => 'No availability configured for today',
                'status' => ['label' => 'Unavailable', 'tone' => 'gray'],
            ]]];
        }

        $start = Carbon::parse($template->start_time)->format('h:i A');
        $end = Carbon::parse($template->end_time)->format('h:i A');

        return ['items' => [[
            'title' => "Today's Schedule",
            'subtitle' => "{$start} - {$end}",
            'status' => ['label' => 'Available', 'tone' => 'green'],
        ]]];
    }

    // -------------------------------------------------------------------
    // Nurse — Queue stats are real (Smart Queue module). Vitals/Triage is
    // not built; represented honestly as not-yet-active rather than
    // approximated via appointments.
    // -------------------------------------------------------------------

    public function nurseStats(): array
    {
        $today = today();
        $active = fn () => $this->todaysQueueQuery()->whereIn('queue_status', ['Waiting', 'Serving']);

        return [
            [
                'label' => 'Patients in Queue',
                'value' => (string) $active()->count(),
                'caption' => 'Waiting or being served',
                'tone' => 'orange',
            ],
            [
                'label' => 'Vitals Recorded Today',
                'value' => (string) Vitals::query()->whereHas('appointment', fn ($query) => $query->whereDate('appointment_date', $today))->count(),
                'caption' => 'Clinic-wide today',
                'tone' => 'blue',
            ],
            [
                'label' => 'Waiting for Doctor',
                'value' => (string) $this->todaysQueueQuery()->where('queue_status', 'Waiting')->count(),
                'caption' => 'Not yet called',
                'tone' => 'amber',
            ],
            [
                'label' => 'Priority Cases',
                'value' => (string) $active()->whereIn('priority_type', self::PRIORITY_LANES)->count(),
                'caption' => 'Senior, PWD, or pregnant',
                'tone' => 'purple',
            ],
        ];
    }

    /**
     * Current queue rows (Waiting/Serving), priority lanes surfaced ahead
     * of Regular per RA 9257/RA 10754, then oldest check-in first.
     */
    public function nurseQueueTable(int $limit = 8): array
    {
        $queues = $this->todaysQueueQuery()
            ->whereIn('queue_status', ['Waiting', 'Serving'])
            ->with('appointment.patient')
            ->get()
            ->sortBy([
                fn (Queue $a, Queue $b) => (in_array($b->priority_type, self::PRIORITY_LANES, true) ? 1 : 0)
                    <=> (in_array($a->priority_type, self::PRIORITY_LANES, true) ? 1 : 0),
                fn (Queue $a, Queue $b) => $a->id <=> $b->id,
            ])
            ->take($limit);

        return [
            'title' => 'Current Queue',
            'columns' => ['Queue No.', 'Patient', 'Priority Type', 'Status'],
            'rows' => $queues->map(fn (Queue $queue) => [
                'queue' => $queue->queue_number,
                'patient' => [
                    'name' => $queue->appointment->patient->full_name,
                    'meta' => $queue->appointment->patient->contact_number,
                    'initials' => $this->initials($queue->appointment->patient->full_name),
                ],
                'service' => $queue->priority_type,
                'status' => [
                    'label' => $queue->queue_status,
                    'tone' => self::QUEUE_STATUS_TONES[$queue->queue_status] ?? 'gray',
                ],
            ])->values()->all(),
            'empty_message' => 'No one is waiting or being served right now.',
        ];
    }

    /**
     * Secondary card for the Nurse dashboard: the most recently recorded
     * vitals, clinic-wide, for today's appointments.
     */
    public function nurseRecentVitals(int $limit = 5): array
    {
        $vitals = Vitals::query()
            ->with('appointment.patient')
            ->whereHas('appointment', fn ($query) => $query->whereDate('appointment_date', today()))
            ->latest('created_at')
            ->limit($limit)
            ->get();

        return [
            'title' => 'Recent Vitals',
            'items' => $vitals->map(fn (Vitals $vital) => [
                'title' => $vital->appointment->patient->full_name,
                'subtitle' => "{$vital->blood_pressure_systolic}/{$vital->blood_pressure_diastolic} mmHg · {$vital->temperature_celsius}°C · {$vital->oxygen_saturation}% O2",
                'status' => ['label' => 'Recorded', 'tone' => 'green'],
            ])->values()->all(),
            'empty_message' => 'No vitals recorded today yet.',
        ];
    }

    // -------------------------------------------------------------------
    // Midwife — no maternal-care/prenatal/follow-up data model exists;
    // represented as not-yet-active rather than shown under a misleading
    // "maternal" label with unfiltered clinic-wide numbers.
    // -------------------------------------------------------------------

    public function midwifeStats(): array
    {
        return [
            $this->notYetActiveStat("Today's Maternal Appointments", 'Maternal care tracking not yet active'),
            $this->notYetActiveStat('Active Prenatal Cases', 'Maternal care tracking not yet active'),
            $this->notYetActiveStat('Completed Visits', 'Maternal care tracking not yet active'),
            $this->notYetActiveStat('Upcoming Follow-ups', 'Maternal care tracking not yet active'),
        ];
    }

    public function midwifeAppointmentsTable(): array
    {
        return [
            'title' => "Today's Maternal Care Appointments",
            'columns' => ['Patient', 'Visit Type', 'Time', 'Status'],
            'rows' => [],
            'empty_message' => 'Maternal care tracking not yet active.',
        ];
    }

    public function midwifeWatchlist(): array
    {
        return [
            'title' => 'Maternal Care Watchlist',
            'items' => [],
            'empty_message' => 'Maternal care tracking not yet active.',
        ];
    }

    // -------------------------------------------------------------------
    // Data Encoder — "encoded by me" can't be attributed (no created_by
    // column on patients/appointments), so activity counts are shown
    // clinic-wide with captions that don't imply personal attribution.
    // -------------------------------------------------------------------

    public function dataEncoderStats(): array
    {
        $today = today();

        return [
            [
                'label' => 'Patients Encoded Today',
                'value' => (string) Patient::query()->whereDate('created_at', $today)->count(),
                'caption' => 'Clinic-wide today',
                'tone' => 'green',
            ],
            $this->notYetActiveStat('Pending Entries', 'Not tracked as a distinct workflow'),
            [
                'label' => 'Appointments Entered Today',
                'value' => (string) Appointment::query()->whereDate('created_at', $today)->count(),
                'caption' => 'Clinic-wide today',
                'tone' => 'blue',
            ],
            [
                'label' => 'SMS Sent Today',
                'value' => (string) SmsNotification::query()->whereDate('sent_at', $today)->where('delivery_status', 'Sent')->count(),
                'caption' => 'Clinic-wide today',
                'tone' => 'purple',
            ],
        ];
    }

    /**
     * Recent encoding activity feed: newly created patients and newly
     * created appointments today, merged into one "entries" list ordered
     * by creation time — a real substitute for the fabricated multi-source
     * mock (which also included a fake SMS row; simulated SMS sends are
     * surfaced on their own via the "SMS Sent Today" stat and the SMS
     * Notifications Log instead of being mixed into this feed).
     */
    public function dataEncoderRecentEntries(int $limit = 5): array
    {
        $today = today();

        $patientEntries = Patient::query()
            ->whereDate('created_at', $today)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Patient $patient) => [
                'patient' => [
                    'name' => $patient->full_name,
                    'meta' => $patient->contact_number,
                    'initials' => $this->initials($patient->full_name),
                ],
                'service' => 'Patient Record',
                'time' => $patient->created_at->format('h:i A'),
                'status' => ['label' => 'Submitted', 'tone' => 'green'],
                'sort' => $patient->created_at,
            ]);

        $appointmentEntries = Appointment::query()
            ->whereDate('created_at', $today)
            ->with('patient')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Appointment $appointment) => [
                'patient' => [
                    'name' => $appointment->patient->full_name,
                    'meta' => $appointment->patient->contact_number,
                    'initials' => $this->initials($appointment->patient->full_name),
                ],
                'service' => 'Appointment Entry',
                'time' => $appointment->created_at->format('h:i A'),
                'status' => [
                    'label' => $appointment->status,
                    'tone' => self::STATUS_TONES[$appointment->status] ?? 'gray',
                ],
                'sort' => $appointment->created_at,
            ]);

        $rows = $patientEntries->concat($appointmentEntries)
            ->sortByDesc('sort')
            ->take($limit)
            ->map(fn (array $row) => collect($row)->except('sort')->all())
            ->values()
            ->all();

        return [
            'title' => 'Recent Entries',
            'columns' => ['Patient', 'Entry Type', 'Time', 'Status'],
            'rows' => $rows,
            'empty_message' => 'No records or appointments entered today.',
        ];
    }

    /**
     * Secondary card for the Data Encoder dashboard: the most recently
     * simulated SMS sends, clinic-wide (same "no per-user attribution"
     * caveat as the rest of this role's stats).
     */
    public function dataEncoderRecentSms(int $limit = 5): array
    {
        $notifications = SmsNotification::query()
            ->with('patient')
            ->latest('sent_at')
            ->limit($limit)
            ->get();

        return [
            'title' => 'Recent SMS Activity',
            'items' => $notifications->map(fn (SmsNotification $notification) => [
                'title' => $notification->patient->full_name,
                'subtitle' => mb_strlen($notification->message) > 60
                    ? mb_substr($notification->message, 0, 60).'…'
                    : $notification->message,
                'status' => [
                    'label' => $notification->delivery_status,
                    'tone' => match ($notification->delivery_status) {
                        'Sent' => 'green',
                        'Failed' => 'red',
                        default => 'amber',
                    },
                ],
            ])->values()->all(),
            'empty_message' => 'No SMS notifications sent yet.',
        ];
    }

    // -------------------------------------------------------------------
    // Shared helpers
    // -------------------------------------------------------------------

    /**
     * Today's appointments, excluding Cancelled (no longer "on the books").
     */
    private function todaysAppointmentsQuery(\Carbon\CarbonInterface $today)
    {
        return Appointment::query()
            ->whereDate('appointment_date', $today)
            ->where('status', '!=', 'Cancelled');
    }

    /**
     * Queue rows whose underlying appointment is scheduled for today.
     */
    private function todaysQueueQuery()
    {
        return Queue::query()
            ->whereHas('appointment', fn ($query) => $query->whereDate('appointment_date', today()));
    }

    /**
     * Map a collection of Appointment models (with patient loaded) into the
     * primary-table row shape consumed by dashboards.partials.overview.
     */
    private function appointmentRows(Collection $appointments): array
    {
        return $appointments->map(fn (Appointment $appointment) => [
            'patient' => [
                'name' => $appointment->patient->full_name,
                'meta' => $appointment->patient->contact_number,
                'initials' => $this->initials($appointment->patient->full_name),
            ],
            'service' => $appointment->purpose_of_visit,
            'time' => Carbon::parse($appointment->appointment_time)->format('h:i A'),
            'status' => [
                'label' => $appointment->status,
                'tone' => self::STATUS_TONES[$appointment->status] ?? 'gray',
            ],
        ])->values()->all();
    }

    /**
     * A "not yet active" stat card always renders in gray tone regardless
     * of the original mock's tone — a deliberate, consistent visual signal
     * that this number is a placeholder, not real data.
     */
    private function notYetActiveStat(string $label, string $caption): array
    {
        return [
            'label' => $label,
            'value' => '0',
            'caption' => $caption,
            'tone' => 'gray',
        ];
    }

    private function initials(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->implode('') ?: '?';
    }
}
