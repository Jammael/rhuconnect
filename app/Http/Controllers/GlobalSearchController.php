<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    /**
     * Fallback routes for bare-string nav labels — these don't carry a
     * 'route' key in Navigation::forRole()'s nested structure because
     * layouts/dashboard.blade.php's $navHref closure already resolves them
     * by hardcoded label match. Bare-string labels not listed here (e.g.
     * "Smart Queue") intentionally have no route yet — preserve their
     * href="#" placeholder behavior, don't invent one.
     */
    private const NAV_ROUTES = [
        'Dashboard'            => 'dashboard',
        'Patient Records'      => 'patients.index',
        'Profile'              => 'profile.edit',
        'Doctor Availability'  => 'doctor-availability.index',
        'My Availability'      => 'doctor-availability.index',
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
        $roleName       = $actor->role?->name ?? '';
        $allowedNav     = Navigation::flatLabelsForRole($roleName);
        $embeddedRoutes = Navigation::routesForRole($roleName);
        $lower          = mb_strtolower($query);

        return collect($allowedNav)
            ->filter(fn (string $label) => str_contains(mb_strtolower($label), $lower))
            ->take(self::RESULT_LIMIT)
            ->map(function (string $label) use ($embeddedRoutes) {
                $routeName = $embeddedRoutes[$label] ?? self::NAV_ROUTES[$label] ?? null;

                return [
                    'label' => $label,
                    'link'  => $routeName && \Illuminate\Support\Facades\Route::has($routeName)
                        ? route($routeName)
                        : '#',
                ];
            })
            ->values()
            ->all();
    }
}
