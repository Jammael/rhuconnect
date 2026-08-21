@php
    $toneClasses = [
        'green' => ['icon' => 'bg-green-100 text-green-700', 'badge' => 'bg-green-100 text-green-700'],
        'orange' => ['icon' => 'bg-orange-100 text-orange-700', 'badge' => 'bg-orange-100 text-orange-700'],
        'amber' => ['icon' => 'bg-amber-100 text-amber-700', 'badge' => 'bg-amber-100 text-amber-700'],
        'blue' => ['icon' => 'bg-blue-100 text-blue-700', 'badge' => 'bg-blue-100 text-blue-700'],
        'purple' => ['icon' => 'bg-purple-100 text-purple-700', 'badge' => 'bg-purple-100 text-purple-700'],
        'red' => ['icon' => 'bg-red-100 text-red-700', 'badge' => 'bg-red-100 text-red-700'],
        'gray' => ['icon' => 'bg-slate-100 text-slate-600', 'badge' => 'bg-slate-100 text-slate-600'],
    ];
@endphp

{{--
    SKELETON DEMO — stat card loading state.
    `loaded` is set to true after a 400 ms artificial delay so the skeleton
    placeholder is visible during development / review. When these stat cards
    are later converted to fetch data asynchronously via Alpine/JS, replace
    `setTimeout(() => loaded = true, 400)` with the callback that fires once
    your fetch() resolves — no other markup changes are needed.
--}}
<div
    class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4"
    x-data="{ loaded: false }"
    x-init="setTimeout(() => loaded = true, 400)"
>
    {{-- Skeleton placeholders — shown for ~400 ms on page load --}}
    @foreach ($stats as $_)
        <template x-if="! loaded">
            <x-skeleton-card />
        </template>
    @endforeach

    {{-- Real stat cards — revealed once `loaded` flips to true --}}
    @foreach ($stats as $stat)
        @php $tone = $toneClasses[$stat['tone']] ?? $toneClasses['green']; @endphp
        <template x-if="loaded">
            <article class="card-hover rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                <div class="flex items-start justify-between gap-4">
                    <p class="text-sm font-bold text-slate-500">{{ $stat['label'] }}</p>
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $tone['icon'] }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 19V5" />
                            <path d="M8 17V9" />
                            <path d="M12 17V7" />
                            <path d="M16 17v-5" />
                            <path d="M20 17V4" />
                        </svg>
                    </span>
                </div>
                <p class="mt-5 text-3xl font-extrabold tracking-normal text-slate-900">{{ $stat['value'] }}</p>
                <p class="mt-2 text-sm font-medium text-slate-500">{{ $stat['caption'] }}</p>
            </article>
        </template>
    @endforeach
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <section class="card-hover min-w-0 rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60 xl:col-span-2">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-lg font-extrabold text-slate-900">{{ $primary['title'] }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach ($primary['columns'] as $column)
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach ($primary['rows'] as $row)
                        @php $badgeTone = $toneClasses[$row['status']['tone']] ?? $toneClasses['gray']; @endphp
                        <tr>
                            @if (isset($row['queue']))
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-extrabold text-slate-800">{{ $row['queue'] }}</td>
                            @endif
                            <td class="whitespace-nowrap px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-xs font-extrabold text-green-700">
                                        {{ $row['patient']['initials'] }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">{{ $row['patient']['name'] }}</p>
                                        <p class="text-xs font-medium text-slate-500">{{ $row['patient']['meta'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600">{{ $row['service'] }}</td>
                            @if (isset($row['time']))
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600">{{ $row['time'] }}</td>
                            @endif
                            <td class="whitespace-nowrap px-5 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $badgeTone['badge'] }}">{{ $row['status']['label'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="min-w-0 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
        <h3 class="text-lg font-extrabold text-slate-900">Quick Actions</h3>
        <div class="mt-4 space-y-3">
            @foreach ($actions as $action)
                @php
                    $tone = $toneClasses[$action['tone']] ?? $toneClasses['green'];
                    $href = isset($action['route']) && $action['route'] && Route::has($action['route']) ? route($action['route']) : '#';
                @endphp
                <a href="{{ $href }}" class="card-hover group flex items-center gap-3 rounded-xl border border-slate-100 p-3 transition hover:border-green-100 hover:bg-green-50/50">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $tone['icon'] }}">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 5v14" />
                            <path d="M5 12h14" />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-extrabold text-slate-800 group-hover:text-green-700">{{ $action['label'] }}</span>
                        <span class="mt-0.5 block text-xs font-medium leading-5 text-slate-500">{{ $action['description'] }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
</div>

@isset($secondary)
    <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
        <h3 class="text-lg font-extrabold text-slate-900">{{ $secondary['title'] }}</h3>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach ($secondary['items'] as $item)
                @php $badgeTone = $toneClasses[$item['status']['tone']] ?? $toneClasses['gray']; @endphp
                <div class="card-hover flex items-center justify-between gap-4 rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                    <div>
                        <p class="text-sm font-extrabold text-slate-900">{{ $item['title'] }}</p>
                        <p class="mt-1 text-sm font-medium text-slate-500">{{ $item['subtitle'] }}</p>
                    </div>
                    <span class="shrink-0 rounded-full px-3 py-1 text-xs font-extrabold {{ $badgeTone['badge'] }}">{{ $item['status']['label'] }}</span>
                </div>
            @endforeach
        </div>
    </section>
@endisset
