@props([
    'name' => 'password',
    'id' => null,
    'label' => null,
    'placeholder' => 'Enter password',
    'required' => false,
    'autocomplete' => 'current-password',
    'showStrengthMeter' => true,
    'value' => '',
    'disabled' => false,
    'autofocus' => false,
    'helperText' => null,
])

@php
    $inputId = $id ?? $name;
@endphp

<div
    x-data="{
        showPassword: false,
        password: @js($value),
        focused: false,
        get lengthValid() { return (this.password || '').length >= 8; },
        get upperValid() { return /[A-Z]/.test(this.password || ''); },
        get lowerValid() { return /[a-z]/.test(this.password || ''); },
        get numberValid() { return /[0-9]/.test(this.password || ''); },
        get specialValid() { return /[^A-Za-z0-9]/.test(this.password || ''); },
        get score() {
            if (! this.password || this.password.length === 0) return 0;
            let s = 0;
            if (this.lengthValid) s++;
            if (this.upperValid) s++;
            if (this.lowerValid) s++;
            if (this.numberValid) s++;
            if (this.specialValid) s++;
            return s;
        },
        get strength() {
            if (this.score === 0) return { label: 'Enter password', width: '0%', color: 'bg-slate-200', textClass: 'text-slate-400' };
            if (this.score <= 2) return { label: 'Weak', width: '25%', color: 'bg-red-500', textClass: 'text-red-600' };
            if (this.score <= 4) return { label: 'Medium', width: '60%', color: 'bg-amber-500', textClass: 'text-amber-600' };
            return { label: 'Strong', width: '100%', color: 'bg-green-600', textClass: 'text-green-700' };
        }
    }"
    class="w-full"
>
    @if ($label)
        <div class="flex items-center justify-between gap-4">
            <label for="{{ $inputId }}" class="block text-sm font-semibold text-slate-700">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500">*</span>
                @endif
            </label>
            {{ $headerExtra ?? '' }}
        </div>
    @endif

    <div class="relative @if ($label) mt-2 @endif">
        <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-slate-400" aria-hidden="true">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="11" x="3" y="11" rx="2" />
                <path d="M7 11V7a5 5 0 0 1 10 0v4" />
            </svg>
        </span>

        <input
            id="{{ $inputId }}"
            name="{{ $name }}"
            x-model="password"
            x-bind:type="showPassword ? 'text' : 'password'"
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            @if ($autofocus) autofocus @endif
            autocomplete="{{ $autocomplete }}"
            placeholder="{{ $placeholder }}"
            x-on:focus="focused = true"
            {{ $attributes->merge([
                'class' => 'block h-12 w-full rounded-lg border border-slate-300 bg-white pl-11 pr-11 text-sm text-slate-900 shadow-xs outline-none transition-all duration-200 placeholder:text-slate-400 focus:border-green-600 focus:ring-2 focus:ring-green-500/20'
            ]) }}
        />

        <button
            type="button"
            class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-slate-400 transition hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500"
            x-on:click="showPassword = ! showPassword"
            x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
        >
            <svg x-show="! showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M2.06 12.35a1 1 0 0 1 0-.7 11 11 0 0 1 19.88 0 1 1 0 0 1 0 .7 11 11 0 0 1-19.88 0" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <svg x-cloak x-show="showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m2 2 20 20" />
                <path d="M6.7 6.7A10.9 10.9 0 0 0 2.06 11.65a1 1 0 0 0 0 .7 11 11 0 0 0 15.24 5.3" />
                <path d="M10.8 10.8A3 3 0 0 0 15.2 15.2" />
                <path d="M12 5c4.4 0 8.1 2.6 9.94 6.65a1 1 0 0 1 0 .7 10.8 10.8 0 0 1-2.1 3.1" />
            </svg>
        </button>
    </div>

    @if ($helperText)
        <p class="mt-1.5 text-xs text-slate-500">{{ $helperText }}</p>
    @endif

    @if ($showStrengthMeter)
        <div class="mt-3 space-y-2.5 rounded-lg border border-slate-100 bg-slate-50/70 p-3" x-cloak x-show="password && password.length > 0" x-transition aria-live="polite">
            {{-- Strength Meter Bar & Label --}}
            <div>
                <div class="flex items-center justify-between gap-3 text-xs">
                    <span class="font-bold text-slate-600">Password Strength:</span>
                    <span
                        class="font-extrabold transition-colors duration-200"
                        :class="strength.textClass"
                        x-text="strength.label"
                        aria-live="polite"
                    ></span>
                </div>
                <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-200">
                    <div
                        class="h-full rounded-full transition-all duration-300"
                        :class="strength.color"
                        :style="'width: ' + strength.width"
                    ></div>
                </div>
            </div>

            {{-- Live Validation Checklist --}}
            <div class="grid grid-cols-1 gap-1.5 pt-1 sm:grid-cols-2 text-xs">
                <div class="flex items-center gap-1.5 transition-colors duration-150" :class="lengthValid ? 'text-green-700 font-semibold' : 'text-slate-400 font-medium'">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                        <svg x-show="lengthValid" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span x-show="! lengthValid" class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                    </span>
                    <span>At least 8 characters</span>
                </div>

                <div class="flex items-center gap-1.5 transition-colors duration-150" :class="upperValid ? 'text-green-700 font-semibold' : 'text-slate-400 font-medium'">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                        <svg x-show="upperValid" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span x-show="! upperValid" class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                    </span>
                    <span>Uppercase letter (A-Z)</span>
                </div>

                <div class="flex items-center gap-1.5 transition-colors duration-150" :class="lowerValid ? 'text-green-700 font-semibold' : 'text-slate-400 font-medium'">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                        <svg x-show="lowerValid" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span x-show="! lowerValid" class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                    </span>
                    <span>Lowercase letter (a-z)</span>
                </div>

                <div class="flex items-center gap-1.5 transition-colors duration-150" :class="numberValid ? 'text-green-700 font-semibold' : 'text-slate-400 font-medium'">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                        <svg x-show="numberValid" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span x-show="! numberValid" class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                    </span>
                    <span>Number (0-9)</span>
                </div>

                <div class="flex items-center gap-1.5 transition-colors duration-150 sm:col-span-2" :class="specialValid ? 'text-green-700 font-semibold' : 'text-slate-400 font-medium'">
                    <span class="flex h-4 w-4 shrink-0 items-center justify-center">
                        <svg x-show="specialValid" class="h-4 w-4 text-green-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        <span x-show="! specialValid" class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                    </span>
                    <span>Special character (!@#$%^&*...)</span>
                </div>
            </div>
        </div>
    @endif
</div>
