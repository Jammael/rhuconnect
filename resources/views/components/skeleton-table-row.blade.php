{{--
    skeleton-table-row.blade.php
    ────────────────────────────
    Animated placeholder for a single table row with multiple columns.

    INTENDED USE:
    Show N copies of this component inside a <tbody> while the parent view is
    waiting for row data to arrive from a JS/Alpine fetch() call.

    When a table is later converted to load its rows client-side (e.g. a live
    patient queue or appointment list), render a fixed number of these rows
    while the `loading` flag is true, then replace them with real <tr> rows
    once the fetch resolves.

    PROPS:
        $cols  — number of <td> placeholder cells to render (default: 5)

    USAGE:
        @foreach (range(1, 5) as $_)
            <x-skeleton-table-row :cols="5" />
        @endforeach
--}}
@props(['cols' => 5])

<tr class="animate-pulse">
    @for ($i = 0; $i < $cols; $i++)
        <td class="whitespace-nowrap px-5 py-4">
            @if ($i === 0)
                {{-- First column: avatar + two-line text block --}}
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 shrink-0 rounded-full bg-slate-200"></div>
                    <div class="space-y-1.5">
                        <div class="h-3.5 w-28 rounded bg-slate-200"></div>
                        <div class="h-3 w-20 rounded bg-slate-200"></div>
                    </div>
                </div>
            @elseif ($i === $cols - 1)
                {{-- Last column: pill/badge placeholder --}}
                <div class="h-5 w-16 rounded-full bg-slate-200"></div>
            @else
                {{-- Middle columns: plain text line --}}
                <div class="h-3.5 w-24 rounded bg-slate-200"></div>
            @endif
        </td>
    @endfor
</tr>

