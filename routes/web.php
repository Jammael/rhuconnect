<?php

use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PrenatalCaseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\SessionCloseController;
use App\Http\Controllers\SmsNotificationController;
use App\Http\Controllers\VisitHistoryController;
use App\Http\Controllers\VitalsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'idle', 'verified', 'active'])
    ->name('dashboard');

Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
    ->middleware(['auth', 'idle', 'verified', 'active', 'role:Administrator'])
    ->name('admin.dashboard');

Route::post('/session/close-beacon', SessionCloseController::class)
    ->name('session.close-beacon');

Route::middleware(['auth', 'idle', 'verified', 'active', 'role:Administrator'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::patch('users/bulk-status', [UserManagementController::class, 'bulkUpdateStatus'])
            ->name('users.bulk-status');
        Route::resource('users', UserManagementController::class)->except(['destroy']);
        Route::patch('users/{user}/status', [UserManagementController::class, 'updateStatus'])
            ->name('users.status');

        Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
        Route::get('reports/export/appointment-volume', [ReportsController::class, 'exportAppointmentVolume'])->name('reports.export.appointments');
        Route::get('reports/export/doctor-utilization', [ReportsController::class, 'exportDoctorUtilization'])->name('reports.export.doctors');
        Route::get('reports/export/patient-demographics', [ReportsController::class, 'exportPatientDemographics'])->name('reports.export.demographics');
        Route::get('reports/export/queue-turnaround', [ReportsController::class, 'exportQueueTurnaround'])->name('reports.export.queue');
    });

Route::middleware(['auth', 'idle', 'active'])->group(function () {
    Route::get('/dashboard/stats-data', [DashboardController::class, 'statsData'])->name('dashboard.stats-data');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/session/heartbeat', fn () => response()->noContent())->name('session.heartbeat');

    Route::get('/search', [GlobalSearchController::class, 'index'])->name('search');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('patients', PatientController::class)->except(['destroy'])->withTrashed(['show']);
    Route::patch('/patients/{patient}/archive', [PatientController::class, 'archive'])->name('patients.archive');
    Route::patch('/patients/{patient}/restore', [PatientController::class, 'restore'])->name('patients.restore');

    Route::get('/appointments/available-slots', [AppointmentController::class, 'availableSlots'])->name('appointments.available-slots');
    Route::resource('appointments', AppointmentController::class)->except(['destroy']);
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.status');
    Route::post('/appointments/{appointment}/vitals', [VitalsController::class, 'store'])->name('vitals.store');

    Route::get('/vitals', [VitalsController::class, 'index'])->name('vitals.index');

    Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
    Route::get('/queue/board-data', [QueueController::class, 'boardData'])->name('queue.board-data');
    Route::post('/queue/{appointment}/check-in', [QueueController::class, 'checkIn'])->name('queue.check-in');
    Route::post('/queue/call-next', [QueueController::class, 'callNext'])->name('queue.call-next');
    Route::patch('/queue/{queue}/status', [QueueController::class, 'updateStatus'])->name('queue.status');

    Route::get('/visit-history', [VisitHistoryController::class, 'index'])->name('visit-history.index');
    Route::get('/visit-history/{visitHistory}', [VisitHistoryController::class, 'show'])->name('visit-history.show');

    Route::get('/prenatal-cases/active-for-patient/{patient}', [PrenatalCaseController::class, 'activeForPatient'])->name('prenatal-cases.active-for-patient');
    Route::resource('prenatal-cases', PrenatalCaseController::class)->except(['destroy']);

    Route::get('/sms-notifications', [SmsNotificationController::class, 'index'])->name('sms-notifications.index');

    Route::get('/doctor-availability', [DoctorAvailabilityController::class, 'index'])->name('doctor-availability.index');
    Route::get('/doctor-availability/{doctor}/edit', [DoctorAvailabilityController::class, 'edit'])->name('doctor-availability.edit');
    Route::put('/doctor-availability/{doctor}', [DoctorAvailabilityController::class, 'update'])->name('doctor-availability.update');
    Route::get('/doctor-availability/{doctor}/exceptions', [DoctorAvailabilityController::class, 'exceptions'])->name('doctor-availability.exceptions');
    Route::post('/doctor-availability/{doctor}/exceptions', [DoctorAvailabilityController::class, 'storeException'])->name('doctor-availability.exceptions.store');
    Route::delete('/doctor-availability/{doctor}/exceptions/{exception}', [DoctorAvailabilityController::class, 'destroyException'])->name('doctor-availability.exceptions.destroy');
});

require __DIR__.'/auth.php';
