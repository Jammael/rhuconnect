@php
    $inputClass = 'mt-2 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-green-700 focus:ring-green-700';
    $labelClass = 'block text-sm font-semibold text-slate-700';
    $textareaClass = $inputClass.' min-h-20 resize-y';
@endphp

<form method="POST" action="{{ route('vitals.store', $appointment) }}" class="mt-4 space-y-6">
    @csrf

    @error('appointment')
        <p class="text-sm font-semibold text-red-600">{{ $message }}</p>
    @enderror

    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <label for="blood_pressure_systolic" class="{{ $labelClass }}">BP Systolic (mmHg)</label>
            <input type="number" id="blood_pressure_systolic" name="blood_pressure_systolic" value="{{ old('blood_pressure_systolic') }}" min="40" max="300" required class="{{ $inputClass }}">
            @error('blood_pressure_systolic') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="blood_pressure_diastolic" class="{{ $labelClass }}">BP Diastolic (mmHg)</label>
            <input type="number" id="blood_pressure_diastolic" name="blood_pressure_diastolic" value="{{ old('blood_pressure_diastolic') }}" min="20" max="200" required class="{{ $inputClass }}">
            @error('blood_pressure_diastolic') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="temperature_celsius" class="{{ $labelClass }}">Temperature (&deg;C)</label>
            <input type="number" id="temperature_celsius" name="temperature_celsius" value="{{ old('temperature_celsius') }}" min="30" max="45" step="0.1" required class="{{ $inputClass }}">
            @error('temperature_celsius') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="pulse_rate" class="{{ $labelClass }}">Pulse Rate (bpm)</label>
            <input type="number" id="pulse_rate" name="pulse_rate" value="{{ old('pulse_rate') }}" min="20" max="250" required class="{{ $inputClass }}">
            @error('pulse_rate') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="respiratory_rate" class="{{ $labelClass }}">Respiratory Rate (breaths/min)</label>
            <input type="number" id="respiratory_rate" name="respiratory_rate" value="{{ old('respiratory_rate') }}" min="5" max="80" required class="{{ $inputClass }}">
            @error('respiratory_rate') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="oxygen_saturation" class="{{ $labelClass }}">Oxygen Saturation (%)</label>
            <input type="number" id="oxygen_saturation" name="oxygen_saturation" value="{{ old('oxygen_saturation') }}" min="1" max="100" required class="{{ $inputClass }}">
            @error('oxygen_saturation') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="weight_kg" class="{{ $labelClass }}">Weight (kg)</label>
            <input type="number" id="weight_kg" name="weight_kg" value="{{ old('weight_kg') }}" min="0.5" max="300" step="0.01" required class="{{ $inputClass }}">
            @error('weight_kg') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="height_cm" class="{{ $labelClass }}">Height (cm)</label>
            <input type="number" id="height_cm" name="height_cm" value="{{ old('height_cm') }}" min="20" max="250" step="0.1" required class="{{ $inputClass }}">
            @error('height_cm') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="chief_complaint" class="{{ $labelClass }}">Chief Complaint (optional)</label>
        <textarea id="chief_complaint" name="chief_complaint" class="{{ $textareaClass }}">{{ old('chief_complaint') }}</textarea>
        @error('chief_complaint') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <button type="submit" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
        Save Vitals
    </button>
</form>
