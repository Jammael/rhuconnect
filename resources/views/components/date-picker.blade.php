@props([
    'name',
    'id',
    'value' => null,
    'min' => null,
    'max' => null,
    'disabledDates' => [],
    'placeholder' => 'Select date',
    'required' => false,
    'disabled' => false,
])

<div
    class="relative"
    x-data="{
        open: false,
        selectedDate: @js($value),
        minDate: @js($min),
        maxDate: @js($max),
        disabledDates: @js(array_values($disabledDates)),
        viewMonth: 0,
        viewYear: 0,
        init() {
            const selected = this.dateFromString(this.selectedDate) ?? new Date();
            this.viewMonth = selected.getMonth();
            this.viewYear = selected.getFullYear();
        },
        dateFromString(value) {
            if (! value) return null;

            const [year, month, day] = value.split('-').map(Number);
            const date = new Date(year, month - 1, day, 12);

            return Number.isNaN(date.getTime()) ? null : date;
        },
        toIso(year, month, day) {
            return `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        },
        isToday(iso) {
            const today = new Date();

            return iso === this.toIso(today.getFullYear(), today.getMonth(), today.getDate());
        },
        isSelected(iso) {
            return iso === this.selectedDate;
        },
        isDisabled(iso) {
            return (this.minDate && iso < this.minDate)
                || (this.maxDate && iso > this.maxDate)
                || this.disabledDates.includes(iso);
        },
        days() {
            const firstWeekday = new Date(this.viewYear, this.viewMonth, 1).getDay();
            const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const daysInPreviousMonth = new Date(this.viewYear, this.viewMonth, 0).getDate();
            const calendarDays = [];

            for (let index = 0; index < 42; index++) {
                const offset = index - firstWeekday + 1;
                let year = this.viewYear;
                let month = this.viewMonth;
                let day = offset;
                let inMonth = true;

                if (offset <= 0) {
                    month -= 1;
                    if (month < 0) {
                        month = 11;
                        year -= 1;
                    }
                    day = daysInPreviousMonth + offset;
                    inMonth = false;
                } else if (offset > daysInMonth) {
                    month += 1;
                    if (month > 11) {
                        month = 0;
                        year += 1;
                    }
                    day = offset - daysInMonth;
                    inMonth = false;
                }

                const iso = this.toIso(year, month, day);
                calendarDays.push({ day, iso, inMonth, disabled: ! inMonth || this.isDisabled(iso) });
            }

            return calendarDays;
        },
        previousMonth() {
            if (this.viewMonth === 0) {
                this.viewMonth = 11;
                this.viewYear -= 1;
            } else {
                this.viewMonth -= 1;
            }
        },
        nextMonth() {
            if (this.viewMonth === 11) {
                this.viewMonth = 0;
                this.viewYear += 1;
            } else {
                this.viewMonth += 1;
            }
        },
        months() {
            return Array.from({ length: 12 }, (_, month) => ({
                value: month,
                label: new Intl.DateTimeFormat(undefined, { month: 'long' }).format(new Date(2026, month, 1)),
            }));
        },
        years() {
            const currentYear = new Date().getFullYear();
            const selectedYear = this.dateFromString(this.selectedDate)?.getFullYear() ?? currentYear;
            const minYear = this.minDate ? Number(this.minDate.slice(0, 4)) : (this.maxDate ? Number(this.maxDate.slice(0, 4)) - 125 : currentYear - 100);
            const maxYear = this.maxDate ? Number(this.maxDate.slice(0, 4)) : Math.max(currentYear + 10, selectedYear + 10);

            return Array.from({ length: maxYear - minYear + 1 }, (_, index) => minYear + index);
        },
        select(iso) {
            if (this.isDisabled(iso)) return;

            this.selectedDate = iso;
            this.open = false;
            this.$dispatch('date-selected', { value: iso });
        },
        formattedDate() {
            const date = this.dateFromString(this.selectedDate);

            return date ? new Intl.DateTimeFormat(undefined, { month: 'short', day: 'numeric', year: 'numeric' }).format(date) : @js($placeholder);
        },
    }"
    x-on:keydown.escape.window="open = false"
>
    <input
        type="date"
        id="{{ $id }}"
        name="{{ $name }}"
        x-model="selectedDate"
        @if ($required) required @endif
        @if ($min) min="{{ $min }}" @endif
        @if ($max) max="{{ $max }}" @endif
        hidden
    >

    <button
        type="button"
        class="flex h-10 w-full items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 text-left text-sm font-semibold text-slate-800 shadow-xs transition-colors duration-150 hover:border-green-300 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open.toString()"
        aria-haspopup="dialog"
        aria-controls="{{ $id }}-calendar"
        @if ($disabled) disabled @endif
    >
        <svg class="h-4 w-4 shrink-0 text-green-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="5" width="18" height="16" rx="2" />
            <path d="M8 3v4" />
            <path d="M16 3v4" />
            <path d="M3 10h18" />
        </svg>
        <span class="min-w-0 flex-1 truncate" x-text="formattedDate()"></span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-150" x-bind:class="{ 'rotate-180': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div
        id="{{ $id }}-calendar"
        x-cloak
        x-show="open"
        x-on:click.outside="open = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="translate-y-1 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-1 opacity-0"
        class="absolute left-0 z-50 mt-2 w-80 rounded-xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-200/70"
        role="dialog"
        aria-label="Choose date"
    >
        <div class="mb-4 flex items-center justify-between gap-2">
            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-600" x-on:click="previousMonth()" aria-label="Previous month">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6" /></svg>
            </button>
            <div class="flex min-w-0 flex-1 items-center justify-center gap-2">
                <label class="sr-only" for="{{ $id }}-month">Month</label>
                <select id="{{ $id }}-month" x-model.number="viewMonth" class="h-8 max-w-28 rounded-lg border-slate-200 bg-slate-50 px-2 pr-7 text-xs font-extrabold text-slate-800 focus:border-green-600 focus:ring-green-600">
                    <template x-for="month in months()" x-bind:key="month.value">
                        <option x-bind:value="month.value" x-text="month.label"></option>
                    </template>
                </select>
                <label class="sr-only" for="{{ $id }}-year">Year</label>
                <select id="{{ $id }}-year" x-model.number="viewYear" class="h-8 w-20 rounded-lg border-slate-200 bg-slate-50 px-2 pr-7 text-xs font-extrabold text-slate-800 focus:border-green-600 focus:ring-green-600">
                    <template x-for="year in years()" x-bind:key="year">
                        <option x-bind:value="year" x-text="year"></option>
                    </template>
                </select>
            </div>
            <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-600" x-on:click="nextMonth()" aria-label="Next month">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-bold uppercase tracking-wide text-slate-400">
            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
        </div>

        <div class="mt-2 grid grid-cols-7 gap-1">
            <template x-for="date in days()" x-bind:key="date.iso">
                <button
                    type="button"
                    class="relative flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-1 disabled:cursor-not-allowed"
                    x-text="date.day"
                    x-bind:disabled="date.disabled"
                    x-bind:aria-label="date.iso"
                    x-on:click="select(date.iso)"
                    x-bind:class="{
                        'bg-green-600 text-white hover:bg-green-700': isSelected(date.iso),
                        'text-slate-300': ! date.inMonth || date.disabled,
                        'text-slate-700 hover:bg-green-50 hover:text-green-700': date.inMonth && ! date.disabled && ! isSelected(date.iso),
                        'ring-1 ring-green-400 ring-offset-1': isToday(date.iso) && ! isSelected(date.iso),
                    }"
                ></button>
            </template>
        </div>
    </div>
</div>
