@csrf

@php
    $inputClass = 'mt-2 block w-full rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-green-700 focus:ring-green-700';
    $labelClass = 'block text-sm font-semibold text-slate-700';
    $textareaClass = $inputClass . ' min-h-20 resize-y';
    $addressTextareaClass = $inputClass . ' min-h-24 resize-y';
@endphp

<div
    x-data="{
        birthdate: @js(old('date_of_birth', optional($patient->birthdate)->format('Y-m-d'))),
        age() {
            if (! this.birthdate) return null;
            const dob = new Date(this.birthdate);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDelta = today.getMonth() - dob.getMonth();
            if (monthDelta < 0 || (monthDelta === 0 && today.getDate() < dob.getDate())) age--;
            return Number.isNaN(age) || age < 0 ? null : age;
        },
    }"
    x-on:date-selected="birthdate = $event.detail.value"
    class="space-y-8"
>
    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Basic Information</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-3">
            <div>
                <label for="first_name" class="{{ $labelClass }}">First Name</label>
                <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $patient->first_name) }}" required autofocus class="{{ $inputClass }}">
                @error('first_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="middle_name" class="{{ $labelClass }}">Middle Name</label>
                <input id="middle_name" name="middle_name" type="text" value="{{ old('middle_name', $patient->middle_name) }}" class="{{ $inputClass }}">
                @error('middle_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="last_name" class="{{ $labelClass }}">Last Name</label>
                <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $patient->last_name) }}" required class="{{ $inputClass }}">
                @error('last_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="flex items-center justify-between gap-3">
                    <label for="date_of_birth" class="{{ $labelClass }}">Date of Birth</label>
                    <span x-show="age() !== null" x-text="`(${age()} years old)`" class="text-xs font-semibold text-green-700"></span>
                </div>
                <div class="mt-2">
                    <x-date-picker
                        name="date_of_birth"
                        id="date_of_birth"
                        :value="old('date_of_birth', optional($patient->birthdate)->format('Y-m-d'))"
                        :max="now()->format('Y-m-d')"
                        required
                    />
                </div>
                @error('date_of_birth') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="sex" class="{{ $labelClass }}">Sex</label>
                <select id="sex" name="sex" required class="{{ $inputClass }}">
                    <option value="">Select sex</option>
                    @foreach ($sexes as $sex)
                        <option value="{{ $sex }}" @selected(old('sex', $patient->sex) === $sex || old('sex') === strtoupper($sex))>{{ $sex }}</option>
                    @endforeach
                </select>
                @error('sex') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="blood_type" class="{{ $labelClass }}">Blood Type</label>
                <select id="blood_type" name="blood_type" class="{{ $inputClass }}">
                    <option value="">Unknown</option>
                    @foreach ($bloodTypes as $bloodType)
                        <option value="{{ $bloodType }}" @selected(old('blood_type', $patient->blood_type) === $bloodType)>{{ $bloodType }}</option>
                    @endforeach
                </select>
                @error('blood_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Contact & Address</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <div>
                <label for="contact_number" class="{{ $labelClass }}">Contact Number</label>
                <input id="contact_number" name="contact_number" type="text" value="{{ old('contact_number', $patient->contact_number) }}" required class="{{ $inputClass }}">
                @error('contact_number') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="philhealth_id" class="{{ $labelClass }}">PhilHealth ID</label>
                <input id="philhealth_id" name="philhealth_id" type="text" value="{{ old('philhealth_id', $patient->philhealth_id) }}" class="{{ $inputClass }}">
                @error('philhealth_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="address" class="{{ $labelClass }}">Address</label>
                <textarea id="address" name="address" required class="{{ $addressTextareaClass }}">{{ old('address', $patient->address) }}</textarea>
                @error('address') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Guardian Information</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <div>
                <label for="guardian_name" class="{{ $labelClass }}">Guardian Name</label>
                <input id="guardian_name" name="guardian_name" type="text" value="{{ old('guardian_name', $patient->guardian_name) }}" class="{{ $inputClass }}">
                @error('guardian_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="guardian_contact" class="{{ $labelClass }}">Guardian Contact</label>
                <input id="guardian_contact" name="guardian_contact" type="text" value="{{ old('guardian_contact', $patient->guardian_contact) }}" class="{{ $inputClass }}">
                @error('guardian_contact') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Clinical Information</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-3">
            <div>
                <label for="known_allergies" class="{{ $labelClass }}">Known Allergies</label>
                <textarea id="known_allergies" name="known_allergies" class="{{ $textareaClass }}">{{ old('known_allergies', $patient->known_allergies) }}</textarea>
                @error('known_allergies') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="existing_conditions" class="{{ $labelClass }}">Existing Conditions</label>
                <textarea id="existing_conditions" name="existing_conditions" class="{{ $textareaClass }}">{{ old('existing_conditions', $patient->existing_conditions) }}</textarea>
                @error('existing_conditions') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="current_medications" class="{{ $labelClass }}">Current Medications</label>
                <textarea id="current_medications" name="current_medications" class="{{ $textareaClass }}">{{ old('current_medications', $patient->current_medications) }}</textarea>
                @error('current_medications') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    <section>
        <h2 class="text-lg font-extrabold text-slate-900">Emergency Contact</h2>
        <div class="mt-4 grid gap-6 md:grid-cols-2">
            <div>
                <label for="emergency_contact_name" class="{{ $labelClass }}">Emergency Contact Name</label>
                <input id="emergency_contact_name" name="emergency_contact_name" type="text" value="{{ old('emergency_contact_name', $patient->emergency_contact_name) }}" class="{{ $inputClass }}">
                @error('emergency_contact_name') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="emergency_contact_number" class="{{ $labelClass }}">Emergency Contact Number</label>
                <input id="emergency_contact_number" name="emergency_contact_number" type="text" value="{{ old('emergency_contact_number', $patient->emergency_contact_number) }}" class="{{ $inputClass }}">
                @error('emergency_contact_number') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>
</div>

<div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
    <a href="{{ route('patients.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
        Cancel
    </a>
    <button type="submit" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2">
        {{ $submitLabel }}
    </button>
</div>
