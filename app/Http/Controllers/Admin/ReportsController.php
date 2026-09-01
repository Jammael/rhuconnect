<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(
        private readonly ReportsService $reports
    ) {}

    public function index(Request $request): View
    {
        $range = $this->resolveDateRange($request);

        return view('admin.reports.index', [
            'range' => $range,
            'appointmentVolume' => $this->reports->appointmentVolume($range['start'], $range['end']),
            'doctorUtilization' => $this->reports->doctorUtilization($range['start'], $range['end']),
            'patientDemographics' => $this->reports->patientDemographics($range['start'], $range['end']),
            'queueTurnaround' => $this->reports->queueTurnaround($range['start'], $range['end']),
        ]);
    }

    public function exportAppointmentVolume(Request $request): StreamedResponse
    {
        $range = $this->resolveDateRange($request);
        $report = $this->reports->appointmentVolume($range['start'], $range['end']);

        return $this->csv('appointment-volume', ['Date', 'Pending', 'Approved', 'Completed', 'Cancelled', 'No Show', 'Total'], array_map(
            fn (array $day) => [$day['date'], $day['Pending'], $day['Approved'], $day['Completed'], $day['Cancelled'], $day['No Show'], $day['total']],
            $report['days']
        ));
    }

    public function exportDoctorUtilization(Request $request): StreamedResponse
    {
        $range = $this->resolveDateRange($request);
        $report = $this->reports->doctorUtilization($range['start'], $range['end']);

        return $this->csv('doctor-utilization', ['Doctor', 'Booked', 'No Show', 'Capacity', 'Utilization %'], array_map(
            fn (array $row) => [$row['doctor'], $row['booked'], $row['no_show'], $row['capacity'], $row['utilization_percent']],
            $report['doctors']
        ));
    }

    public function exportPatientDemographics(Request $request): StreamedResponse
    {
        $range = $this->resolveDateRange($request);
        $report = $this->reports->patientDemographics($range['start'], $range['end']);

        $rows = [];
        foreach ($report['sex'] as $label => $count) {
            $rows[] = ['Sex', $label, $count];
        }
        foreach ($report['age_brackets'] as $label => $count) {
            $rows[] = ['Age Bracket', $label, $count];
        }
        foreach ($report['barangays'] as $label => $count) {
            $rows[] = ['Barangay', $label, $count];
        }

        return $this->csv('patient-demographics', ['Category', 'Label', 'Count'], $rows);
    }

    public function exportQueueTurnaround(Request $request): StreamedResponse
    {
        $range = $this->resolveDateRange($request);
        $report = $this->reports->queueTurnaround($range['start'], $range['end']);

        $rows = [['Overall', $report['count'], $report['avg_minutes'], $report['median_minutes'], $report['min_minutes'], $report['max_minutes']]];

        foreach ($report['by_priority'] as $type => $stats) {
            $rows[] = [$type, $stats['count'], $stats['avg_minutes'], null, null, null];
        }

        return $this->csv('queue-turnaround', ['Group', 'Count', 'Avg Minutes', 'Median Minutes', 'Min Minutes', 'Max Minutes'], $rows);
    }

    /**
     * @return array{start: Carbon, end: Carbon, range: string}
     */
    private function resolveDateRange(Request $request): array
    {
        $range = $request->string('range')->toString() ?: 'month';

        if (! in_array($range, ['today', 'week', 'month', 'custom'], true)) {
            $range = 'month';
        }

        if ($range === 'custom') {
            $validated = $request->validate([
                'from' => ['required', 'date'],
                'to' => ['required', 'date', 'after_or_equal:from'],
            ]);

            return [
                'start' => Carbon::parse($validated['from'])->startOfDay(),
                'end' => Carbon::parse($validated['to'])->endOfDay(),
                'range' => 'custom',
            ];
        }

        $today = today();

        return match ($range) {
            'today' => ['start' => $today->copy()->startOfDay(), 'end' => $today->copy()->endOfDay(), 'range' => 'today'],
            'week' => ['start' => $today->copy()->startOfWeek(), 'end' => $today->copy()->endOfWeek(), 'range' => 'week'],
            default => ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->endOfMonth(), 'range' => 'month'],
        };
    }

    /**
     * Stream a CSV download built from a header row and an array of row arrays.
     */
    private function csv(string $filename, array $header, array $rows): StreamedResponse
    {
        $callback = function () use ($header, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $header);

            foreach ($rows as $row) {
                fputcsv($handle, array_map($this->sanitizeCsvCell(...), $row));
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, "{$filename}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Neutralize CSV/formula injection: spreadsheet apps treat a cell
     * starting with =, +, -, @, a tab, or a carriage return as a formula.
     * Prefixing it with a leading single quote forces it to render as
     * plain text instead of executing when the file is opened.
     */
    private function sanitizeCsvCell(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (preg_match('/^[=+\-@\t\r]/', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
