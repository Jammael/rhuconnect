{{--
    skeleton-card.blade.php
    ──────────────────────
    Animated placeholder for a stat card (icon badge + large number + caption).

    INTENDED USE:
    Show this component while the parent view is waiting for async stat data.
    It is currently wired via an Alpine `loaded` flag with an artificial delay
    in overview.blade.php as a visual demonstration.

    When a view is later converted to fetch stat data via a JS/Alpine fetch()
    call (e.g. live-updating totals), replace the artificial delay with a
    flag that is set to `true` once the fetch resolves — no markup changes
    needed here.

    USAGE:
        <x-skeleton-card />
--}}
<article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 animate-pulse">
    <div class="flex items-start justify-between gap-4">
        {{-- Label placeholder --}}
        <div class="h-4 w-28 rounded bg-slate-200"></div>
        {{-- Icon badge placeholder --}}
        <div class="h-10 w-10 shrink-0 rounded-xl bg-slate-200"></div>
    </div>
    {{-- Big number placeholder --}}
    <div class="mt-5 h-9 w-20 rounded bg-slate-200"></div>
    {{-- Caption placeholder --}}
    <div class="mt-2 h-3.5 w-36 rounded bg-slate-200"></div>
</article>

