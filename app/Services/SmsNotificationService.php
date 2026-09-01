<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\SmsNotification;
use Carbon\Carbon;

/**
 * Simulated SMS gateway: no outbound network call is made. Every "send" is
 * logged straight to the sms_notifications table as Sent (there's no real
 * async delivery here to justify a Pending window). Swapping in a real
 * provider later means replacing the body of send() with an actual API
 * call and letting delivery_status reflect its real response — the two
 * call sites in AppointmentController don't need to change.
 */
class SmsNotificationService
{
    /**
     * Triggered when an appointment is first booked.
     */
    public function logBookingConfirmation(Appointment $appointment): SmsNotification
    {
        $appointment->loadMissing(['patient', 'doctor']);

        $message = sprintf(
            'Hi %s, your appointment with Dr. %s on %s at %s for %s has been booked. Please arrive 10 minutes early. - RHUConnect',
            $appointment->patient->full_name,
            $appointment->doctor->name,
            $appointment->appointment_date->format('M d, Y'),
            Carbon::parse($appointment->appointment_time)->format('h:i A'),
            $appointment->purpose_of_visit,
        );

        return $this->send($appointment, $message);
    }

    /**
     * Triggered when an appointment's status changes to Approved or
     * Cancelled — the only two status transitions that warrant notifying
     * the patient. Any other status (Pending, Completed, No Show) is a
     * no-op here and returns null.
     */
    public function logStatusChange(Appointment $appointment, string $status): ?SmsNotification
    {
        $message = match ($status) {
            'Approved' => $this->approvedMessage($appointment),
            'Cancelled' => $this->cancelledMessage($appointment),
            default => null,
        };

        if ($message === null) {
            return null;
        }

        return $this->send($appointment, $message);
    }

    private function approvedMessage(Appointment $appointment): string
    {
        $appointment->loadMissing(['patient', 'doctor']);

        return sprintf(
            'Hi %s, your appointment with Dr. %s on %s at %s has been approved. See you then! - RHUConnect',
            $appointment->patient->full_name,
            $appointment->doctor->name,
            $appointment->appointment_date->format('M d, Y'),
            Carbon::parse($appointment->appointment_time)->format('h:i A'),
        );
    }

    private function cancelledMessage(Appointment $appointment): string
    {
        $appointment->loadMissing(['patient', 'doctor']);

        return sprintf(
            'Hi %s, your appointment with Dr. %s on %s at %s has been cancelled. Please contact us to reschedule. - RHUConnect',
            $appointment->patient->full_name,
            $appointment->doctor->name,
            $appointment->appointment_date->format('M d, Y'),
            Carbon::parse($appointment->appointment_time)->format('h:i A'),
        );
    }

    private function send(Appointment $appointment, string $message): SmsNotification
    {
        return SmsNotification::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'message' => $message,
            'recipient_number' => $appointment->patient->contact_number,
            'delivery_status' => 'Sent',
            'sent_at' => now(),
        ]);
    }
}
