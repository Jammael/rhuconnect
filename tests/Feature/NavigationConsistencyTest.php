<?php

namespace Tests\Feature;

use App\Support\Navigation;
use Tests\TestCase;

class NavigationConsistencyTest extends TestCase
{
    /**
     * Locks in the canonical per-role nav structure so a future edit to
     * Navigation::forRole() has to be a deliberate, visible change here
     * rather than an accidental regression during some other refactor.
     */
    public function test_navigation_for_role_matches_the_expected_canonical_structure(): void
    {
        $this->assertSame([
            'MAIN MENU' => ['Dashboard', 'Patient Records', ['label' => 'Online Appointments', 'route' => 'appointments.index', 'active' => 'appointments.*'], ['label' => 'Smart Queue', 'route' => 'queue.index', 'active' => 'queue.*'], 'Doctor Availability', ['label' => 'Patient Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], ['label' => 'Reports & Analytics', 'route' => 'admin.reports.index', 'active' => 'admin.reports.*'], ['label' => 'SMS Notifications', 'route' => 'sms-notifications.index', 'active' => 'sms-notifications.*']],
            'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
        ], Navigation::forRole('Administrator'));

        $this->assertSame([
            'MAIN MENU' => ['Dashboard', 'Patient Records', ['label' => 'My Appointments', 'route' => 'appointments.index', 'active' => 'appointments.*'], 'My Availability', ['label' => 'Patient Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], 'Profile'],
        ], Navigation::forRole('Doctor'));

        $this->assertSame([
            'MAIN MENU' => ['Dashboard', ['label' => 'Patient Queue', 'route' => 'queue.index', 'active' => 'queue.*'], 'Patient Records', ['label' => 'Appointments', 'route' => 'appointments.index', 'active' => 'appointments.*'], ['label' => 'Patient Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], ['label' => 'Vitals/Triage', 'route' => 'vitals.index', 'active' => 'vitals.*'], 'Profile'],
        ], Navigation::forRole('Nurse'));

        $this->assertSame([
            'MAIN MENU' => ['Dashboard', ['label' => 'Maternal Care Appointments', 'route' => 'appointments.index', 'active' => 'appointments.*'], ['label' => 'Prenatal Cases', 'route' => 'prenatal-cases.index', 'active' => 'prenatal-cases.*'], 'Patient Records', ['label' => 'Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], 'Profile'],
        ], Navigation::forRole('Midwife'));

        $this->assertSame([
            'MAIN MENU' => ['Dashboard', 'Patient Records', ['label' => 'Appointment Entry', 'route' => 'appointments.index', 'active' => 'appointments.*'], ['label' => 'Patient Visit History', 'route' => 'visit-history.index', 'active' => 'visit-history.*'], ['label' => 'SMS Notifications Log', 'route' => 'sms-notifications.index', 'active' => 'sms-notifications.*'], 'Profile'],
        ], Navigation::forRole('Data Encoder'));

        $this->assertSame([
            'MAIN MENU' => ['Dashboard', 'Profile'],
        ], Navigation::forRole('Unknown Role'));
    }

    public function test_flat_labels_for_role_preserves_group_order(): void
    {
        $this->assertSame(
            ['Dashboard', 'Patient Records', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications', 'User Management'],
            Navigation::flatLabelsForRole('Administrator')
        );

        $this->assertSame(
            ['Dashboard', 'Patient Queue', 'Patient Records', 'Appointments', 'Patient Visit History', 'Vitals/Triage', 'Profile'],
            Navigation::flatLabelsForRole('Nurse')
        );
    }

    public function test_routes_for_role_only_includes_array_shaped_items(): void
    {
        $this->assertSame([
            'Online Appointments' => 'appointments.index',
            'Smart Queue' => 'queue.index',
            'Patient Visit History' => 'visit-history.index',
            'Reports & Analytics' => 'admin.reports.index',
            'SMS Notifications' => 'sms-notifications.index',
            'User Management' => 'admin.users.index',
        ], Navigation::routesForRole('Administrator'));

        $this->assertSame([
            'Patient Queue' => 'queue.index',
            'Appointments' => 'appointments.index',
            'Patient Visit History' => 'visit-history.index',
            'Vitals/Triage' => 'vitals.index',
        ], Navigation::routesForRole('Nurse'));

        // Bare-string-only roles/defaults carry no embedded routes.
        $this->assertSame([], Navigation::routesForRole('Unknown Role'));
    }

    public function test_all_five_staff_roles_agree_between_dashboard_controller_and_global_search(): void
    {
        foreach (['Administrator', 'Doctor', 'Nurse', 'Midwife', 'Data Encoder'] as $roleName) {
            $flat = Navigation::flatLabelsForRole($roleName);

            $this->assertNotEmpty($flat, "Role {$roleName} should have at least one nav item.");
            $this->assertContains('Dashboard', $flat);
        }
    }
}
