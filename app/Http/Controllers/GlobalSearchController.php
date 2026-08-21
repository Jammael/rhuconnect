<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Role-to-nav mapping mirrors the navGroups defined in dashboard.blade.php.
     * When the nav changes there, update this list to match.
     */
    private const NAV_BY_ROLE = [
        'Administrator' => [
            'Dashboard',
            'Patient Records',
            'Online Appointments',
            'Smart Queue',
            'Doctor Availability',
            'Slot Capacity',
            'Patient Visit History',
            'Reports & Analytics',
            'SMS Notifications',
            'User Management',
        ],
        'Doctor' => [
            'Dashboard',
            'Patient Records',
            'My Appointments',
            'My Availability',
            'Patient Visit History',
            'Profile',
        ],
        'Nurse' => [
            'Dashboard',
            'Patient Queue',
            'Patient Records',
            'Vitals/Triage',
            'Profile',
        ],
        'Midwife' => [
            'Dashboard',
            'Maternal Care Appointments',
            'Patient Records',
            'Visit History',
            'Profile',
        ],
        'Data Encoder' => [
            'Dashboard',
            'Patient Records',
            'Appointment Entry',
            'SMS Notifications Log',
            'Profile',
        ],
    ];

    /** Route map for nav items that have a registered Laravel route. */
    private const NAV_ROUTES = [
        'Dashboard'              => 'dashboard',
        'Patient Records'        => 'patients.index',
        'User Management'        => 'admin.users.index',
        'Profile'                => 'profile.edit',
    ];

    private const RESULT_LIMIT = 5;

    public function index(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        // Enforce minimum query length to avoid overly broad matches.
        if (mb_strlen($query) < 2) {
            return response()->json([
                'patients' => [],
                'users'    => [],
                'pages'    => [],
            ]);
        }

        $user = $request->user();

        return response()->json([
            'patients' => $this->searchPatients($query),
            'users'    => $this->searchUsers($query, $user),
            'pages'    => $this->searchPages($query, $user),
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Patients are visible to all authenticated staff roles, matching the same
     * access level established for PatientController (all roles can view patients).
     * Excludes soft-deleted (archived) records.
     */
    private function searchPatients(string $query): array
    {
        return Patient::query()
            ->whereNull('deleted_at')
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('middle_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('contact_number', 'like', "%{$query}%");
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(self::RESULT_LIMIT)
            ->get()
            ->map(fn (Patient $patient) => [
                'id'   => $patient->id,
                'name' => $patient->full_name,
                'meta' => $patient->contact_number,
                'link' => route('patients.show', $patient),
            ])
            ->values()
            ->all();
    }

    /**
     * Users are only returned for Administrators — matching the role:Administrator
     * restriction already enforced on /admin/users routes.
     * Uses the same hasRole() check defined on the User model.
     */
    private function searchUsers(string $query, User $actor): array
    {
        if (! $actor->hasRole('Administrator')) {
            return [];
        }

        return User::query()
            ->with('role')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT)
            ->get()
            ->map(fn (User $user) => [
                'id'   => $user->id,
                'name' => $user->name,
                'meta' => $user->email,
                'link' => route('admin.users.show', $user),
            ])
            ->values()
            ->all();
    }

    /**
     * Pages are filtered by the authenticated user's role nav list so a Nurse
     * searching "user" never sees "User Management" as a navigable result.
     */
    private function searchPages(string $query, User $actor): array
    {
        $roleName   = $actor->role?->name ?? '';
        $allowedNav = self::NAV_BY_ROLE[$roleName] ?? [];
        $lower      = mb_strtolower($query);

        return collect($allowedNav)
            ->filter(fn (string $label) => str_contains(mb_strtolower($label), $lower))
            ->take(self::RESULT_LIMIT)
            ->map(fn (string $label) => [
                'label' => $label,
                'link'  => isset(self::NAV_ROUTES[$label]) && \Illuminate\Support\Facades\Route::has(self::NAV_ROUTES[$label])
                    ? route(self::NAV_ROUTES[$label])
                    : '#',
            ])
            ->values()
            ->all();
    }
}
