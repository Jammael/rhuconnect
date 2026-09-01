<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Queue;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class QueueController extends Controller
{
    private const CHECK_IN_ROLES = ['Administrator', 'Nurse', 'Midwife', 'Data Encoder'];

    private const MANAGE_ROLES = ['Administrator', 'Nurse'];

    private const PRIORITY_TYPES = ['Regular', 'Senior Citizen', 'PWD', 'Pregnant'];

    public function __construct(
        private readonly QueueService $queueService
    ) {}

    public function index(Request $request): View
    {
        return view('queue.index', array_merge($this->boardArrays(), [
            'canManageQueue' => $this->canManageQueue($request),
        ]));
    }

    public function boardData(Request $request): JsonResponse
    {
        return response()->json($this->boardArrays());
    }

    public function checkIn(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($request->user()?->hasRole(self::CHECK_IN_ROLES), 403);

        $validated = $request->validate([
            'priority_type' => ['required', Rule::in(self::PRIORITY_TYPES)],
        ]);

        $this->queueService->checkIn($appointment, $validated['priority_type']);

        return back()->with('status', 'Patient checked in to the queue successfully.');
    }

    public function callNext(Request $request): RedirectResponse
    {
        abort_unless($this->canManageQueue($request), 403);

        $queue = $this->queueService->callNext();

        return back()->with('status', $queue
            ? "Now serving {$queue->queue_number} — {$queue->appointment->patient->full_name}."
            : 'The queue is empty — no one is waiting.');
    }

    public function updateStatus(Request $request, Queue $queue): RedirectResponse
    {
        abort_unless($this->canManageQueue($request), 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['Skipped', 'Cancelled'])],
        ]);

        $this->queueService->markStatus($queue, $validated['status']);

        return back()->with('status', "Queue entry marked {$validated['status']}.");
    }

    /**
     * @return array{waiting: array, serving: array, history: array}
     */
    private function boardArrays(): array
    {
        $today = Queue::query()
            ->whereHas('appointment', fn ($query) => $query->whereDate('appointment_date', today()))
            ->with(['appointment.patient', 'appointment.doctor'])
            ->get();

        $waiting = $today->where('queue_status', 'Waiting')
            ->sortBy([
                fn (Queue $a, Queue $b) => ($b->priority_type !== 'Regular') <=> ($a->priority_type !== 'Regular'),
                fn (Queue $a, Queue $b) => $a->id <=> $b->id,
            ]);

        $serving = $today->where('queue_status', 'Serving')->sortBy('id');

        $history = $today->whereIn('queue_status', ['Completed', 'Skipped', 'Cancelled'])->sortByDesc('updated_at');

        return [
            'waiting' => $this->rows($waiting),
            'serving' => $this->rows($serving),
            'history' => $this->rows($history),
        ];
    }

    private function rows(Collection $queues): array
    {
        return $queues->map(fn (Queue $queue) => [
            'id' => $queue->id,
            'queue_number' => $queue->queue_number,
            'priority_type' => $queue->priority_type,
            'queue_status' => $queue->queue_status,
            'checked_in_at' => $queue->created_at->format('h:i A'),
            'patient' => [
                'name' => $queue->appointment->patient->full_name,
                'contact' => $queue->appointment->patient->contact_number,
            ],
            'doctor' => $queue->appointment->doctor->name,
            'appointment_id' => $queue->appointment_id,
        ])->values()->all();
    }

    private function canManageQueue(Request $request): bool
    {
        return $request->user()?->hasRole(self::MANAGE_ROLES) ?? false;
    }
}
