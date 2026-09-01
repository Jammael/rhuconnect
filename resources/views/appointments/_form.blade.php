@csrf

@php
    $inputClass = 'mt-2 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-green-700 focus:ring-green-700';
    $labelClass = 'block text-sm font-semibold text-slate-700';
    $textareaClass = $inputClass . ' min-h-24 resize-y';
@endphp

<div
    x-data="{
        patientQuery: '',
        patientResults: [],
        showPatientResults: false,
        patientDebounce: null,
        selectedPatientId: @js(old('patient_id', $appointment->patient_id)),
        selectedPatientName: @js(old('patient_name', optional($appointment->patient)->full_name)),
        doctorId: @js(old('doctor_id', $appointment->doctor_id)),
        appointmentDate: @js(old('appointment_date', optional($appointment->appointment_date)->format('Y-m-d'))),
        appointmentTime: @js(old('appointment_time', $appointment->appointment_time ? substr($appointment->appointment_time, 0, 5) : '')),
        excludeAppointmentId: @js($appointment->id),
        slots: [],
        loadingSlots: false,

        init() {
            if (this.doctorId && this.appointmentDate) {
                this.fetchSlots();
            }
        },

        searchPatients() {
            clearTimeout(this.patientDebounce);
            const q = this.patientQuery.trim();

            if (q.length < 2) {
                this.patientResults = [];
                this.showPatientResults = false;
                return;
            }

            this.patientDebounce = setTimeout(async () => {
                try {
                    const response = await fetch(`/search?q=${encodeURIComponent(q)}`, {
                        headers: { 'Accept': 'application/json' },
                    });

                    if (response.ok) {
                        const data = await response.json();
                        this.patientResults = data.patients ?? [];
                        this.showPatientResults = true;
                    }
                } catch (err) {
                    console.error('Patient search failed:', err);
                }
            }, 200);
        },

        selectPatient(patient) {
            this.selectedPatientId = patient.id;
            this.selectedPatientName = patient.name;
            this.patientQuery = '';
            this.patientResults = [];
            this.showPatientResults = false;
        },

        clearPatient() {
            this.selectedPatientId = null;
            this.selectedPatientName = '';
        },

        async fetchSlots() {
            if (! this.doctorId || ! this.appointmentDate) {
                this.slots = [];
                return;
            }

            this.loadingSlots = true;

            try {
                const params = new URLSearchParams({ doctor_id: this.doctorId, date: this.appointmentDate });
                if (this.excludeAppointmentId) {
                    params.set('exclude_appointment_id', this.excludeAppointmentId);
                }

                const response = await fetch(`{{ route('appointments.available-slots') }}?${params.toString()}`, {
                    headers: { 'Accept': 'application/json' },
                });

                if (response.ok) {
                    const data = await response.json();
                    this.slots = data.slots ?? [];
                    if (! this.slots.includes(this.appointmentTime)) {
                        this.appointmentTime = '';
                    }
                } else {
                    this.slots = [];
                }
            } catch (err) {
                console.error('Fetching available slots failed:', err);
                this.slots = [];
            } finally {
                this.loadingSlots = false;
            }
        },
    }"
    x-on:date-selected="appointmentDate = $event.detail.value; fetchSlots()"
    class="space-y-8"
>
    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Patient</h2>
        <div class="mt-4">
            <input type="hidden" name="patient_id" x-model="selectedPatientId">

            <div x-show="! selectedPatientId" x-cloak class="relative" x-on:click.outside="showPatientResults = false">
                <label for="patient_search" class="{{ $labelClass }}">Search Patient</label>
                <input
                    id="patient_search"
                    type="text"
                    x-model="patientQuery"
                    x-on:input="searchPatients()"
                    placeholder="Search by name or contact number"
                    class="{{ $inputClass }}"
                    autocomplete="off"
                >

                <div
                    x-show="showPatientResults && patientResults.length > 0"
                    x-cloak
                    class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-lg"
                >
                    <template x-for="patient in patientResults" x-bind:key="patient.id">
                        <button
                            type="button"
                            x-on:click="selectPatient(patient)"
                            class="flex w-full flex-col items-start px-4 py-2 text-left text-sm transition hover:bg-green-50"
                        >
                            <span class="font-semibold text-slate-900" x-text="patient.name"></span>
                            <span class="text-xs text-slate-500" x-text="patient.meta"></span>
                        </button>
                    </template>
                </div>

                <p x-show="showPatientResults && patientResults.length === 0" x-cloak class="mt-2 text-xs font-medium text-slate-500">
                    No matching patients found.
                </p>
            </div>

            <div x-show="selectedPatientId" x-cloak class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                <span class="text-sm font-bold text-slate-900" x-text="selectedPatientName"></span>
                <button type="button" x-on:click="clearPatient()" class="text-sm font-semibold text-green-700 hover:text-green-900">
                    Change
                </button>
            </div>

            @error('patient_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </section>

    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Schedule</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-3">
            <div>
                <label for="doctor_id" class="{{ $labelClass }}">Doctor</label>
                <select id="doctor_id" name="doctor_id" x-model="doctorId" x-on:change="fetchSlots()" required class="{{ $inputClass }}">
                    <option value="">Select doctor</option>
                    @foreach ($doctors as $doctor)
                        <option value="{{ $doctor->id }}">{{ $doctor->name }}</option>
                    @endforeach
                </select>
                @error('doctor_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="appointment_date" class="{{ $labelClass }}">Date</label>
                <div class="mt-2">
                    <x-date-picker
                        name="appointment_date"
                        id="appointment_date"
                        :value="old('appointment_date', optional($appointment->appointment_date)->format('Y-m-d'))"
                        :min="now()->format('Y-m-d')"
                        required
                    />
                </div>
                @error('appointment_date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="appointment_time" class="{{ $labelClass }}">Time Slot</label>
                <select id="appointment_time" name="appointment_time" x-model="appointmentTime" required class="{{ $inputClass }}" x-bind:disabled="slots.length === 0">
                    <option value="" x-text="loadingSlots ? 'Loading slots...' : (slots.length === 0 ? (doctorId && appointmentDate ? 'No available slots' : 'Select doctor and date first') : 'Select a time')"></option>
                    <template x-for="slot in slots" x-bind:key="slot">
                        <option x-bind:value="slot" x-text="slot"></option>
                    </template>
                </select>
                @error('appointment_time') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Visit Details</h2>
        <div class="mt-4 grid gap-6">
            <div>
                <label for="purpose_of_visit" class="{{ $labelClass }}">Purpose of Visit</label>
                <input id="purpose_of_visit" name="purpose_of_visit" type="text" value="{{ old('purpose_of_visit', $appointment->purpose_of_visit) }}" required class="{{ $inputClass }}">
                @error('purpose_of_visit') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="remarks" class="{{ $labelClass }}">Remarks</label>
                <textarea id="remarks" name="remarks" class="{{ $textareaClass }}">{{ old('remarks', $appointment->remarks) }}</textarea>
                @error('remarks') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('appointments.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
        {{ $submitLabel }}
    </button>
</div>
