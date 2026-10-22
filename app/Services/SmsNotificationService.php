<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\SmsNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS gateway via Semaphore (https://semaphore.co) when SEMAPHORE_API_KEY
 * is configured; falls back to a simulated log (no outbound network call,
 * always marked Sent) when it isn't — so local/test environments without
 * real credentials keep working exactly as before. The two call sites in
 * AppointmentController never need to know which mode is active.
 */
class SmsNotificationService
{
    private const ENDPOINT = 'https://api.semaphore.co/api/v4/messages';

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
        $recipient = $appointment->patient->contact_number;
        [$status, $sentAt] = $this->dispatch($recipient, $message);

        return SmsNotification::create([
            'patient_id' => $appointment->patient_id,
            'appointment_id' => $appointment->id,
            'message' => $message,
            'recipient_number' => $recipient,
            'delivery_status' => $status,
            'sent_at' => $sentAt,
        ]);
    }

    /**
     * @return array{0: string, 1: \Illuminate\Support\Carbon} [delivery_status, sent_at]
     */
    private function dispatch(string $recipient, string $message): array
    {
        $apiKey = config('services.semaphore.api_key');

        if (blank($apiKey)) {
            // No real gateway configured — simulated mode, unchanged from before.
            return ['Sent', now()];
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post(self::ENDPOINT, [
                    'apikey' => $apiKey,
                    'number' => preg_replace('/[\s-]/', '', $recipient),
                    'message' => $message,
                    'sendername' => config('services.semaphore.sender_name'),
                ]);

            if ($response->successful()) {
                return ['Sent', now()];
            }

            Log::warning('Semaphore SMS send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['Failed', now()];
        } catch (\Throwable $e) {
            // Network/timeout failures must not break appointment booking —
            // this is a best-effort side effect, not a transactional one.
            Log::error('Semaphore SMS send threw an exception', ['message' => $e->getMessage()]);

            return ['Failed', now()];
        }
    }
}
