<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
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

    public function admin(Request $request): View
    {
        return view('dashboards.admin', $this->dashboardData($request, 'Administrator', [
            'pageTitle' => 'Administrator Dashboard',
            'pageSubtitle' => 'Monitor clinic operations, access, and service flow.',
            'context' => 'Here is the clinic-wide pulse for appointments, queues, providers, and reports.',
            'stats' => [
                ['label' => "Today's Appointments", 'value' => '24', 'caption' => '+8% from yesterday', 'tone' => 'green'],
                ['label' => 'Patients in Queue', 'value' => '12', 'caption' => 'Current queue status', 'tone' => 'orange'],
                ['label' => 'Completed Visits', 'value' => '18', 'caption' => 'Completed today', 'tone' => 'blue'],
                ['label' => 'Available Doctors', 'value' => '2', 'caption' => 'Currently available', 'tone' => 'purple'],
            ],
            'primary' => [
                'title' => "Today's Appointments",
                'columns' => ['Patient', 'Service', 'Time', 'Status'],
                'rows' => [
                    ['patient' => ['name' => 'Maria Santos', 'meta' => 'RHU-1024', 'initials' => 'MS'], 'service' => 'Prenatal Checkup', 'time' => '08:30 AM', 'status' => ['label' => 'Confirmed', 'tone' => 'green']],
                    ['patient' => ['name' => 'Jose Villanueva', 'meta' => 'RHU-1188', 'initials' => 'JV'], 'service' => 'General Consultation', 'time' => '09:15 AM', 'status' => ['label' => 'Pending', 'tone' => 'amber']],
                    ['patient' => ['name' => 'Ana Reyes', 'meta' => 'RHU-1215', 'initials' => 'AR'], 'service' => 'Immunization', 'time' => '10:00 AM', 'status' => ['label' => 'Confirmed', 'tone' => 'green']],
                ],
            ],
            'actions' => [
                ['label' => 'Manage Appointments', 'description' => 'Review bookings and walk-ins.', 'route' => null, 'tone' => 'green'],
                ['label' => 'Manage Smart Queue', 'description' => 'Prioritize current patient flow.', 'route' => null, 'tone' => 'orange'],
                ['label' => 'Generate Reports', 'description' => 'Prepare operational summaries.', 'route' => null, 'tone' => 'blue'],
                ['label' => 'User Management', 'description' => 'Provision staff access.', 'route' => 'admin.users.index', 'tone' => 'purple'],
            ],
            'secondary' => [
                'title' => 'Doctor Availability',
                'items' => [
                    ['title' => 'Dr. Elena Cruz', 'subtitle' => 'Family Medicine', 'status' => ['label' => 'Available', 'tone' => 'green']],
                    ['title' => 'Dr. Marco Lim', 'subtitle' => 'General Practice', 'status' => ['label' => 'Unavailable', 'tone' => 'gray']],
                ],
            ],
        ]));
    }

    private function doctor(Request $request): View
    {
        return view('dashboards.doctor', $this->dashboardData($request, 'Doctor', [
            'pageTitle' => 'Doctor Dashboard',
            'pageSubtitle' => 'Manage consultations, availability, and patient continuity.',
            'context' => 'Your schedule and waiting patients are ready for a smooth clinic day.',
            'stats' => [
                ['label' => "Today's Appointments", 'value' => '9', 'caption' => 'Assigned to you', 'tone' => 'green'],
                ['label' => 'Patients Waiting', 'value' => '5', 'caption' => 'Awaiting consultation', 'tone' => 'orange'],
                ['label' => 'Completed Today', 'value' => '7', 'caption' => 'Consultations closed', 'tone' => 'blue'],
                ['label' => 'Next Appointment', 'value' => '10:30 AM', 'caption' => 'Follow-up consult', 'tone' => 'purple'],
            ],
            'primary' => [
                'title' => 'My Schedule Today',
                'columns' => ['Patient', 'Purpose', 'Time', 'Status'],
                'rows' => [
                    ['patient' => ['name' => 'Liza Mendoza', 'meta' => 'RHU-1301', 'initials' => 'LM'], 'service' => 'Hypertension Follow-up', 'time' => '09:30 AM', 'status' => ['label' => 'In Queue', 'tone' => 'amber']],
                    ['patient' => ['name' => 'Ben Castillo', 'meta' => 'RHU-0922', 'initials' => 'BC'], 'service' => 'General Consultation', 'time' => '10:30 AM', 'status' => ['label' => 'Confirmed', 'tone' => 'green']],
                    ['patient' => ['name' => 'Nora Flores', 'meta' => 'RHU-1419', 'initials' => 'NF'], 'service' => 'Lab Result Review', 'time' => '11:15 AM', 'status' => ['label' => 'Pending', 'tone' => 'amber']],
                ],
            ],
            'actions' => [
                ['label' => 'View Full Schedule', 'description' => 'See today and upcoming visits.', 'route' => null, 'tone' => 'green'],
                ['label' => 'Update My Availability', 'description' => 'Set clinic availability status.', 'route' => null, 'tone' => 'orange'],
                ['label' => 'Start Next Consultation', 'description' => 'Open the next patient visit.', 'route' => null, 'tone' => 'blue'],
                ['label' => 'View Patient History', 'description' => 'Review prior visit notes.', 'route' => null, 'tone' => 'purple'],
            ],
            'secondary' => [
                'title' => 'Availability Status',
                'items' => [
                    ['title' => 'Clinic Consultation Block', 'subtitle' => '08:00 AM - 12:00 PM', 'status' => ['label' => 'Available', 'tone' => 'green']],
                    ['title' => 'Afternoon Follow-ups', 'subtitle' => '01:00 PM - 03:00 PM', 'status' => ['label' => 'Limited', 'tone' => 'amber']],
                ],
            ],
        ]));
    }

    private function nurse(Request $request): View
    {
        return view('dashboards.nurse', $this->dashboardData($request, 'Nurse', [
            'pageTitle' => 'Nurse Dashboard',
            'pageSubtitle' => 'Coordinate queue flow, triage, and patient preparation.',
            'context' => 'The current queue is organized so you can move patients through intake quickly.',
            'stats' => [
                ['label' => 'Patients in Queue', 'value' => '12', 'caption' => 'Waiting for service', 'tone' => 'green'],
                ['label' => 'Vitals Recorded Today', 'value' => '31', 'caption' => 'Triage entries completed', 'tone' => 'blue'],
                ['label' => 'Waiting for Doctor', 'value' => '6', 'caption' => 'Ready for consultation', 'tone' => 'orange'],
                ['label' => 'Priority Cases', 'value' => '2', 'caption' => 'Needs close attention', 'tone' => 'purple'],
            ],
            'primary' => [
                'title' => 'Current Queue',
                'columns' => ['Queue No.', 'Patient', 'Priority Type', 'Status'],
                'rows' => [
                    ['queue' => 'Q-014', 'patient' => ['name' => 'Oscar Dela Cruz', 'meta' => 'RHU-1008', 'initials' => 'OD'], 'service' => 'Senior Citizen', 'status' => ['label' => 'For Vitals', 'tone' => 'amber']],
                    ['queue' => 'Q-015', 'patient' => ['name' => 'Mila Ramos', 'meta' => 'RHU-1152', 'initials' => 'MR'], 'service' => 'Pregnant Patient', 'status' => ['label' => 'Priority', 'tone' => 'red']],
                    ['queue' => 'Q-016', 'patient' => ['name' => 'Carlo Garcia', 'meta' => 'RHU-1170', 'initials' => 'CG'], 'service' => 'Regular', 'status' => ['label' => 'Waiting', 'tone' => 'gray']],
                ],
            ],
            'actions' => [
                ['label' => 'Manage Queue', 'description' => 'Advance or reorder patients.', 'route' => null, 'tone' => 'green'],
                ['label' => 'Record Vitals', 'description' => 'Capture intake measurements.', 'route' => null, 'tone' => 'orange'],
                ['label' => 'View Patient Records', 'description' => 'Look up patient information.', 'route' => null, 'tone' => 'blue'],
                ['label' => 'Flag Priority Case', 'description' => 'Mark urgent queue entries.', 'route' => null, 'tone' => 'purple'],
            ],
            'secondary' => [
                'title' => 'Triage Notes',
                'items' => [
                    ['title' => 'Vitals Station', 'subtitle' => 'Two patients ready for intake', 'status' => ['label' => 'Active', 'tone' => 'green']],
                    ['title' => 'Priority Lane', 'subtitle' => 'Pregnant and senior patients first', 'status' => ['label' => 'Monitoring', 'tone' => 'amber']],
                ],
            ],
        ]));
    }

    private function midwife(Request $request): View
    {
        return view('dashboards.midwife', $this->dashboardData($request, 'Midwife', [
            'pageTitle' => 'Midwife Dashboard',
            'pageSubtitle' => 'Track maternal care appointments and follow-up visits.',
            'context' => 'Maternal care appointments and prenatal follow-ups are queued for review.',
            'stats' => [
                ['label' => "Today's Maternal Appointments", 'value' => '8', 'caption' => 'Scheduled today', 'tone' => 'green'],
                ['label' => 'Active Prenatal Cases', 'value' => '42', 'caption' => 'Currently monitored', 'tone' => 'orange'],
                ['label' => 'Completed Visits', 'value' => '5', 'caption' => 'Completed today', 'tone' => 'blue'],
                ['label' => 'Upcoming Follow-ups', 'value' => '11', 'caption' => 'Next seven days', 'tone' => 'purple'],
            ],
            'primary' => [
                'title' => "Today's Maternal Care Appointments",
                'columns' => ['Patient', 'Visit Type', 'Time', 'Status'],
                'rows' => [
                    ['patient' => ['name' => 'Grace Bautista', 'meta' => 'RHU-0771', 'initials' => 'GB'], 'service' => 'Prenatal Checkup', 'time' => '08:45 AM', 'status' => ['label' => 'Confirmed', 'tone' => 'green']],
                    ['patient' => ['name' => 'Sarah Aquino', 'meta' => 'RHU-0820', 'initials' => 'SA'], 'service' => 'Postnatal Visit', 'time' => '10:15 AM', 'status' => ['label' => 'Pending', 'tone' => 'amber']],
                    ['patient' => ['name' => 'Rhea Molina', 'meta' => 'RHU-0914', 'initials' => 'RM'], 'service' => 'Follow-up', 'time' => '01:30 PM', 'status' => ['label' => 'Confirmed', 'tone' => 'green']],
                ],
            ],
            'actions' => [
                ['label' => 'View Appointments', 'description' => 'Review maternal care schedule.', 'route' => null, 'tone' => 'green'],
                ['label' => 'Log Visit', 'description' => 'Record maternal care notes.', 'route' => null, 'tone' => 'orange'],
                ['label' => 'Patient Records', 'description' => 'Open maternal patient records.', 'route' => null, 'tone' => 'blue'],
                ['label' => 'Schedule Follow-up', 'description' => 'Plan the next care visit.', 'route' => null, 'tone' => 'purple'],
            ],
            'secondary' => [
                'title' => 'Maternal Care Watchlist',
                'items' => [
                    ['title' => 'Third Trimester Follow-ups', 'subtitle' => 'Four patients due this week', 'status' => ['label' => 'Active', 'tone' => 'green']],
                    ['title' => 'Missed Appointment Review', 'subtitle' => 'Two patients need outreach', 'status' => ['label' => 'Pending', 'tone' => 'amber']],
                ],
            ],
        ]));
    }

    private function dataEncoder(Request $request): View
    {
        return view('dashboards.data-encoder', $this->dashboardData($request, 'Data Encoder', [
            'pageTitle' => 'Data Encoder Dashboard',
            'pageSubtitle' => 'Keep patient records, appointment entries, and SMS logs updated.',
            'context' => 'Recent encoding activity and pending entries are ready for your review.',
            'stats' => [
                ['label' => 'Patients Encoded Today', 'value' => '16', 'caption' => 'New or updated records', 'tone' => 'green'],
                ['label' => 'Pending Entries', 'value' => '4', 'caption' => 'Need completion', 'tone' => 'orange'],
                ['label' => 'Appointments Entered Today', 'value' => '21', 'caption' => 'Manual entries logged', 'tone' => 'blue'],
                ['label' => 'SMS Sent Today', 'value' => '38', 'caption' => 'Notification messages', 'tone' => 'purple'],
            ],
            'primary' => [
                'title' => 'Recent Entries',
                'columns' => ['Patient', 'Entry Type', 'Time', 'Status'],
                'rows' => [
                    ['patient' => ['name' => 'Alma Cortez', 'meta' => 'RHU-1220', 'initials' => 'AC'], 'service' => 'Patient Record', 'time' => '08:20 AM', 'status' => ['label' => 'Submitted', 'tone' => 'green']],
                    ['patient' => ['name' => 'Rico Padilla', 'meta' => 'RHU-1221', 'initials' => 'RP'], 'service' => 'Appointment Entry', 'time' => '09:05 AM', 'status' => ['label' => 'Draft', 'tone' => 'gray']],
                    ['patient' => ['name' => 'Celia Torres', 'meta' => 'RHU-1222', 'initials' => 'CT'], 'service' => 'SMS Notification', 'time' => '09:50 AM', 'status' => ['label' => 'Submitted', 'tone' => 'green']],
                ],
            ],
            'actions' => [
                ['label' => 'Add New Patient', 'description' => 'Create a placeholder record.', 'route' => null, 'tone' => 'green'],
                ['label' => 'Enter Appointment', 'description' => 'Log manual appointment details.', 'route' => null, 'tone' => 'orange'],
                ['label' => 'View SMS Log', 'description' => 'Review sent notifications.', 'route' => null, 'tone' => 'blue'],
                ['label' => 'Search Records', 'description' => 'Find patient information.', 'route' => null, 'tone' => 'purple'],
            ],
            'secondary' => [
                'title' => 'Encoding Activity',
                'items' => [
                    ['title' => 'Patient Records Batch', 'subtitle' => 'Three records awaiting review', 'status' => ['label' => 'Pending', 'tone' => 'amber']],
                    ['title' => 'SMS Notifications Log', 'subtitle' => 'All morning reminders sent', 'status' => ['label' => 'Complete', 'tone' => 'green']],
                ],
            ],
        ]));
    }

    private function dashboardData(Request $request, string $role, array $data): array
    {
        return array_merge($data, [
            'user' => $request->user()->loadMissing('role'),
            'roleLabel' => $role,
            'portalLabel' => $role === 'Administrator' ? 'Admin Portal' : $role.' Portal',
            'navGroups' => $this->navigationFor($role),
        ]);
    }

    private function navigationFor(string $role): array
    {
        return match ($role) {
            'Administrator' => [
                'MAIN MENU' => ['Dashboard', 'Patient Records', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Slot Capacity', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications'],
                'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
            ],
            'Doctor' => [
                'MAIN MENU' => ['Dashboard', 'Patient Records', 'My Appointments', 'My Availability', 'Patient Visit History', 'Profile'],
            ],
            'Nurse' => [
                'MAIN MENU' => ['Dashboard', 'Patient Queue', 'Patient Records', 'Vitals/Triage', 'Profile'],
            ],
            'Midwife' => [
                'MAIN MENU' => ['Dashboard', 'Maternal Care Appointments', 'Patient Records', 'Visit History', 'Profile'],
            ],
            'Data Encoder' => [
                'MAIN MENU' => ['Dashboard', 'Patient Records', 'Appointment Entry', 'SMS Notifications Log', 'Profile'],
            ],
            default => [
                'MAIN MENU' => ['Dashboard', 'Profile'],
            ],
        };
    }

    private function roleName($user): string
    {
        return $user->role?->name ?? 'Doctor';
    }
}
