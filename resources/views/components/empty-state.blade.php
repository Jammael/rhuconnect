{{--
    empty-state.blade.php
    ──────────────────────
    Standard "nothing here yet" placeholder: icon badge + message + optional
    hint line. Mirrors the icon-box style already used by the command
    palette's zero-query state (h-10 w-10 rounded-xl bg-slate-100 icon).

    USAGE:
        <x-empty-state message="No patient records found." />
        <x-empty-state message="No one is waiting." hint="New check-ins will appear here." />

    Inside a table row, wrap in a <td colspan="..."> as usual — this
    component only renders the centered icon/message block.
--}}
@props(['message', 'hint' => null])

<div class="flex flex-col items-center justify-center gap-3 px-5 py-10 text-center">
    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 12h-6l-2 3h-4l-2-3H2" />
            <path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" />
        </svg>
    </span>
    <div>
        <p class="text-sm font-semibold text-slate-700">{{ $message }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
        @endif
    </div>
</div>
