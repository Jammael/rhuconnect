<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\DoctorAvailabilityService;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DoctorAvailabilityService $doctorAvailabilityService,
        private readonly DashboardStatsService $stats,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('Administrator')) {
            return redirect()->route('admin.dashboard');
        }

        return match ($this->roleName($user)) {
            'Doctor' => $this->doctor($request),
            'Nurse' => $this->nurse($request),
            'Midwife' => $this->midwife($request),
            'Data Encoder' => $this->dataEncoder($request),
            default => $this->doctor($request),
        };
    }

    /**
     * JSON counterpart to index()/admin(): the same per-role stats array
     * that gets baked into the initial page render, fetched fresh on
     * demand so dashboards.partials.overview's stat cards can poll without
     * a full page reload. Reuses statsForRole() so the role -> stats
     * mapping lives in exactly one place.
     */
    public function statsData(Request $request): JsonResponse
    {
        $user = $request->user();
        $roleName = $user->hasRole('Administrator') ? 'Administrator' : $this->roleName($user);

        return response()->json([
            'stats' => $this->statsForRole($request, $roleName),
        ]);
    }

    public function admin(Request $request): View
    {
        $doctorAvailability = $this->adminDoctorAvailability();

        return view('dashboards.admin', $this->dashboardData($request, 'Administrator', [
            'pageTitle' => 'Administrator Dashboard',
            'pageSubtitle' => 'Monitor clinic operations, access, and service flow.',
            'context' => 'Here is the clinic-wide pulse for appointments, queues, providers, and reports.',
            'stats' => $this->statsForRole($request, 'Administrator'),
            'primary' => $this->stats->adminTodaysAppointments(),
            'actions' => [
                ['label' => 'Manage Appointments', 'description' => 'Review bookings and walk-ins.', 'route' => 'appointments.index', 'tone' => 'green'],
                ['label' => 'Manage Smart Queue', 'description' => 'Prioritize current patient flow.', 'route' => 'queue.index', 'tone' => 'orange'],
                ['label' => 'Generate Reports', 'description' => 'Prepare operational summaries.', 'route' => null, 'tone' => 'blue'],
                ['label' => 'User Management', 'description' => 'Provision staff access.', 'route' => 'admin.users.index', 'tone' => 'purple'],
            ],
            'secondary' => [
                'title' => 'Doctor Availability',
                'items' => $doctorAvailability['items'],
            ],
        ]));
    }

    /**
     * Build the availability snapshot shared by the Admin stat and detail card.
     *
     * @return array{available_count: int, items: array<int, array<string, mixed>>}
     */
    private function adminDoctorAvailability(): array
    {
        $today = today();

        $doctors = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'Doctor'))
            ->orderBy('name')
            ->get();

        $availability = $doctors->map(function (User $doctor) use ($today): array {
            $isAvailable = $this->doctorAvailabilityService->getAvailableSlots($doctor, $today) !== [];

            return [
                'title' => $doctor->name,
                'subtitle' => $isAvailable ? 'Available for appointments today' : 'No availability scheduled today',
                'available' => $isAvailable,
                'status' => [
                    'label' => $isAvailable ? 'Available' : 'Unavailable',
                    'tone' => $isAvailable ? 'green' : 'gray',
                ],
            ];
        });

        return [
            'available_count' => $availability->where('available', true)->count(),
            'items' => $availability
                ->take(6)
                ->map(fn (array $doctor) => collect($doctor)->except('available')->all())
                ->values()
                ->all(),
        ];
    }

    private function doctor(Request $request): View
    {
        $doctor = $request->user();

        return view('dashboards.doctor', $this->dashboardData($request, 'Doctor', [
            'pageTitle' => 'Doctor Dashboard',
            'pageSubtitle' => 'Manage consultations, availability, and patient continuity.',
            'context' => 'Your schedule and waiting patients are ready for a smooth clinic day.',
            'stats' => $this->statsForRole($request, 'Doctor'),
            'primary' => $this->stats->doctorTodaysSchedule($doctor),
            'actions' => [
                ['label' => 'View Full Schedule', 'description' => 'See today and upcoming visits.', 'route' => 'appointments.index', 'tone' => 'green'],
                ['label' => 'Update My Availability', 'description' => 'Set clinic availability status.', 'href' => route('doctor-availability.edit', $doctor->id), 'tone' => 'orange'],
                ['label' => 'Start Next Consultation', 'description' => 'Open the next patient visit.', 'route' => 'queue.index', 'tone' => 'blue'],
                ['label' => 'View Patient History', 'description' => 'Review prior visit notes.', 'route' => null, 'tone' => 'purple'],
            ],
            'secondary' => array_merge(
                ['title' => 'Availability Status'],
                $this->stats->doctorAvailabilityStatus($doctor)
            ),
        ]));
    }

    private function nurse(Request $request): View
    {
        return view('dashboards.nurse', $this->dashboardData($request, 'Nurse', [
            'pageTitle' => 'Nurse Dashboard',
            'pageSubtitle' => 'Coordinate queue flow, triage, and patient preparation.',
            'context' => 'The current queue is organized so you can move patients through intake quickly.',
            'stats' => $this->statsForRole($request, 'Nurse'),
            'primary' => $this->stats->nurseQueueTable(),
            'actions' => [
                ['label' => 'Manage Queue', 'description' => 'Advance or reorder patients.', 'route' => 'queue.index', 'tone' => 'green'],
                ['label' => 'Record Vitals', 'description' => 'Capture intake measurements.', 'route' => 'vitals.index', 'tone' => 'orange'],
                ['label' => 'View Patient Records', 'description' => 'Look up patient information.', 'route' => 'patients.index', 'tone' => 'blue'],
                ['label' => 'Flag Priority Case', 'description' => 'Mark urgent queue entries.', 'route' => 'queue.index', 'tone' => 'purple'],
            ],
            'secondary' => $this->stats->nurseRecentVitals(),
        ]));
    }

    private function midwife(Request $request): View
    {
        $midwife = $request->user();

        return view('dashboards.midwife', $this->dashboardData($request, 'Midwife', [
            'pageTitle' => 'Midwife Dashboard',
            'pageSubtitle' => 'Track maternal care appointments and follow-up visits.',
            'context' => 'Maternal care appointments and prenatal follow-ups are queued for review.',
            'stats' => $this->statsForRole($request, 'Midwife'),
            'primary' => $this->stats->midwifeAppointmentsTable($midwife),
            'actions' => [
                ['label' => 'View Appointments', 'description' => 'Review maternal care schedule.', 'route' => 'appointments.index', 'tone' => 'green'],
                ['label' => 'Log Visit', 'description' => 'Manage prenatal cases and notes.', 'route' => 'prenatal-cases.index', 'tone' => 'orange'],
                ['label' => 'Patient Records', 'description' => 'Open maternal patient records.', 'route' => 'patients.index', 'tone' => 'blue'],
                ['label' => 'Schedule Follow-up', 'description' => 'Plan the next care visit.', 'route' => 'appointments.create', 'tone' => 'purple'],
            ],
            'secondary' => $this->stats->midwifeWatchlist($midwife),
        ]));
    }

    private function dataEncoder(Request $request): View
    {
        return view('dashboards.data-encoder', $this->dashboardData($request, 'Data Encoder', [
            'pageTitle' => 'Data Encoder Dashboard',
            'pageSubtitle' => 'Keep patient records, appointment entries, and SMS logs updated.',
            'context' => 'Recent encoding activity and pending entries are ready for your review.',
            'stats' => $this->statsForRole($request, 'Data Encoder'),
            'primary' => $this->stats->dataEncoderRecentEntries(),
            'actions' => [
                ['label' => 'Add New Patient', 'description' => 'Create a placeholder record.', 'route' => 'patients.create', 'tone' => 'green'],
                ['label' => 'Enter Appointment', 'description' => 'Log manual appointment details.', 'route' => 'appointments.create', 'tone' => 'orange'],
                ['label' => 'View SMS Log', 'description' => 'Review sent notifications.', 'route' => 'sms-notifications.index', 'tone' => 'blue'],
                ['label' => 'Search Records', 'description' => 'Find patient information.', 'route' => 'patients.index', 'tone' => 'purple'],
            ],
            'secondary' => $this->stats->dataEncoderRecentSms(),
        ]));
    }

    /**
     * Single source of truth mapping a role name to its dashboard stats
     * array — shared by the full page-render methods above and
     * statsData()'s polling endpoint, so there is exactly one role -> stats
     * switch in this controller.
     */
    private function statsForRole(Request $request, string $roleName): array
    {
        return match ($roleName) {
            'Administrator' => $this->adminStatsArray(),
            'Doctor' => $this->stats->doctorStats($request->user()),
            'Nurse' => $this->stats->nurseStats(),
            'Midwife' => $this->stats->midwifeStats($request->user()),
            'Data Encoder' => $this->stats->dataEncoderStats(),
            default => $this->stats->doctorStats($request->user()),
        };
    }

    private function adminStatsArray(): array
    {
        $doctorAvailability = $this->adminDoctorAvailability();

        $stats = $this->stats->adminStats();
        $stats[] = ['label' => 'Available Doctors', 'value' => (string) $doctorAvailability['available_count'], 'caption' => 'Currently available', 'tone' => 'purple'];

        return $stats;
    }

    private function dashboardData(Request $request, string $role, array $data): array
    {
        return array_merge($data, [
            'user' => $request->user()->loadMissing('role'),
            'roleLabel' => $role,
            'portalLabel' => $role === 'Administrator' ? 'Admin Portal' : $role.' Portal',
            'navGroups' => Navigation::forRole($role),
        ]);
    }

    private function roleName($user): string
    {
        return $user->role?->name ?? 'Doctor';
    }
}
