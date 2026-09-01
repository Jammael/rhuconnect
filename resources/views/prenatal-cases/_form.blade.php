@csrf

@php
    $inputClass = 'mt-2 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-green-700 focus:ring-green-700';
    $labelClass = 'block text-sm font-semibold text-slate-700';
    $textareaClass = $inputClass.' min-h-24 resize-y';
    $isMidwife = auth()->user()->hasRole('Midwife');
@endphp

<div
    x-data="{
        patientQuery: '',
        patientResults: [],
        showPatientResults: false,
        patientDebounce: null,
        selectedPatientId: @js(old('patient_id', $case->patient_id)),
        selectedPatientName: @js(old('patient_name', optional($case->patient)->full_name)),
        lmp: @js(old('last_menstrual_period', optional($case->last_menstrual_period)->format('Y-m-d'))),
        edd: @js(old('estimated_due_date', optional($case->estimated_due_date)->format('Y-m-d'))),
        eddManuallySet: @js($case->exists),
        isHighRisk: @js(old('is_high_risk', $case->is_high_risk ?? false)),

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

        onLmpChange() {
            if (! this.lmp || this.eddManuallySet) {
                return;
            }

            const date = new Date(this.lmp);
            date.setDate(date.getDate() + 280);
            this.edd = date.toISOString().slice(0, 10);
        },

        onEddInput() {
            this.eddManuallySet = true;
        },
    }"
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
                @unless ($case->exists)
                    <button type="button" x-on:click="clearPatient()" class="text-sm font-semibold text-green-700 hover:text-green-900">
                        Change
                    </button>
                @endunless
            </div>

            @error('patient_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </section>

    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Case Details</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            @if ($isMidwife)
                <input type="hidden" name="midwife_id" value="{{ auth()->id() }}">
                <div class="md:col-span-2">
                    <p class="{{ $labelClass }}">Assigned Midwife</p>
                    <p class="mt-2 text-sm text-slate-600">{{ auth()->user()->name }} (you)</p>
                </div>
            @else
                <div class="md:col-span-2">
                    <label for="midwife_id" class="{{ $labelClass }}">Assigned Midwife</label>
                    <select id="midwife_id" name="midwife_id" required class="{{ $inputClass }}">
                        <option value="">Select midwife</option>
                        @foreach ($midwives as $midwife)
                            <option value="{{ $midwife->id }}" @selected((string) old('midwife_id', $case->midwife_id ?? '') === (string) $midwife->id)>
                                {{ $midwife->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('midwife_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label for="last_menstrual_period" class="{{ $labelClass }}">Last Menstrual Period</label>
                <input id="last_menstrual_period" name="last_menstrual_period" type="date" x-model="lmp" x-on:change="onLmpChange()" max="{{ now()->format('Y-m-d') }}" required class="{{ $inputClass }}">
                @error('last_menstrual_period') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="estimated_due_date" class="{{ $labelClass }}">Estimated Due Date</label>
                <input id="estimated_due_date" name="estimated_due_date" type="date" x-model="edd" x-on:input="onEddInput()" required class="{{ $inputClass }}">
                <p class="mt-1 text-xs text-slate-500">Auto-filled from LMP (+280 days) — adjust if ultrasound-based EDD differs.</p>
                @error('estimated_due_date') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="gravida" class="{{ $labelClass }}">Gravida (pregnancies)</label>
                <input id="gravida" name="gravida" type="number" min="1" max="20" value="{{ old('gravida', $case->gravida ?? 1) }}" required class="{{ $inputClass }}">
                @error('gravida') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="para" class="{{ $labelClass }}">Para (live births)</label>
                <input id="para" name="para" type="number" min="0" max="20" value="{{ old('para', $case->para ?? 0) }}" required class="{{ $inputClass }}">
                @error('para') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="status" class="{{ $labelClass }}">Status</label>
                <select id="status" name="status" required class="{{ $inputClass }}">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}" @selected(old('status', $case->status ?? 'Active') === $statusOption)>{{ $statusOption }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 md:col-span-2">
                <input id="is_high_risk" name="is_high_risk" type="checkbox" value="1" x-model="isHighRisk" class="h-4 w-4 rounded border-slate-300 text-green-700 focus:ring-green-700">
                <label for="is_high_risk" class="text-sm font-semibold text-slate-700">Flag as high-risk pregnancy</label>
            </div>

            <div x-show="isHighRisk" x-cloak class="md:col-span-2">
                <label for="risk_notes" class="{{ $labelClass }}">Risk Notes</label>
                <textarea id="risk_notes" name="risk_notes" class="{{ $textareaClass }}">{{ old('risk_notes', $case->risk_notes) }}</textarea>
                @error('risk_notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="{{ $labelClass }}">Notes (optional)</label>
                <textarea id="notes" name="notes" class="{{ $textareaClass }}">{{ old('notes', $case->notes) }}</textarea>
                @error('notes') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('prenatal-cases.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
        {{ $submitLabel }}
    </button>
</div>
