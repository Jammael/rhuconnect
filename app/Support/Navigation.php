<?php

namespace App\Support;

class Navigation
{
    /**
     * Canonical per-role sidebar navigation, grouped by section label.
     *
     * Each item is either a bare string label (no link — a placeholder for
     * a page that doesn't exist yet, e.g. "Smart Queue") or an associative
     * array with `label`, `route`, and `active` (a route-pattern for
     * highlighting the item as current).
     *
     * @return array<string, array<int, string|array{label: string, route: string, active: string}>>
     */
    public static function forRole(string $roleName): array
    {
        return match ($roleName) {
            'Administrator' => [
                'MAIN MENU' => ['Dashboard', 'Patient Records', ['label' => 'Online Appointments', 'route' => 'appointments.index', 'active' => 'appointments.*'], ['label' => 'Smart Queue', 'route' => 'queue.index', 'active' => 'queue.*'], 'Doctor Availability', ['label' => 'Patient Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], ['label' => 'Reports & Analytics', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*'], ['label' => 'SMS Notifications', 'route' => 'sms-notifications.index', 'active' => 'sms-notifications.*']],
                'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
            ],
            'Doctor' => [
                'MAIN MENU' => ['Dashboard', 'Patient Records', ['label' => 'My Appointments', 'route' => 'appointments.index', 'active' => 'appointments.*'], 'My Availability', ['label' => 'Patient Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], 'Profile'],
            ],
            'Nurse' => [
                'MAIN MENU' => ['Dashboard', ['label' => 'Patient Queue', 'route' => 'queue.index', 'active' => 'queue.*'], 'Patient Records', ['label' => 'Appointments', 'route' => 'appointments.index', 'active' => 'appointments.*'], ['label' => 'Patient Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], ['label' => 'Vitals/Triage', 'route' => 'vitals.index', 'active' => 'vitals.*'], 'Profile'],
            ],
            'Midwife' => [
                'MAIN MENU' => ['Dashboard', ['label' => 'Maternal Care Appointments', 'route' => 'appointments.index', 'active' => 'appointments.*'], ['label' => 'Prenatal Cases', 'route' => 'prenatal-cases.index', 'active' => 'prenatal-cases.*'], 'Patient Records', ['label' => 'Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], 'Profile'],
            ],
            'Data Encoder' => [
                'MAIN MENU' => ['Dashboard', 'Patient Records', ['label' => 'Appointment Entry', 'route' => 'appointments.index', 'active' => 'appointments.*'], ['label' => 'Patient Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], ['label' => 'SMS Notifications Log', 'route' => 'sms-notifications.index', 'active' => 'sms-notifications.*'], 'Profile'],
            ],
            default => [
                'MAIN MENU' => ['Dashboard', 'Profile'],
            ],
        };
    }

    /**
     * Flatten the nested group structure into an ordered list of labels,
     * group order preserved, groups concatenated.
     *
     * @return array<int, string>
     */
    public static function flatLabelsForRole(string $roleName): array
    {
        $labels = [];

        foreach (self::forRole($roleName) as $items) {
            foreach ($items as $item) {
                $labels[] = is_string($item) ? $item : $item['label'];
            }
        }

        return $labels;
    }

    /**
     * Label => route name map for items whose route is embedded in the
     * nested structure (i.e. array-shaped items). Bare-string items
     * intentionally carry no route here — some (e.g. "Dashboard", "Profile")
     * are resolved by hardcoded label match elsewhere (layouts/dashboard's
     * $navHref), others (e.g. "Smart Queue") have no route at all yet.
     *
     * @return array<string, string>
     */
    public static function routesForRole(string $roleName): array
    {
        $routes = [];

        foreach (self::forRole($roleName) as $items) {
            foreach ($items as $item) {
                if (is_array($item) && isset($item['route'])) {
                    $routes[$item['label']] = $item['route'];
                }
            }
        }

        return $routes;
    }
}
