@props(['trail' => []])

@if (count($trail) > 1)
    <nav aria-label="Breadcrumb">
        <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
            @foreach ($trail as $index => $crumb)
                @php
                    $isCurrent = $loop->last;
                    $label = $crumb['label'] ?? '';
                    $link = $crumb['link'] ?? null;
                @endphp

                <li class="flex items-center gap-2">
                    @if ($link && ! $isCurrent)
                        <a href="{{ $link }}" class="inline-flex items-center gap-1.5 font-semibold text-slate-500 transition-colors duration-150 hover:text-green-700">
                            @if ($index === 0 && $label === 'Dashboard')
                                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="m3 10 9-7 9 7" />
                                    <path d="M5 9.5V21h14V9.5" />
                                </svg>
                            @endif
                            <span>{{ $label }}</span>
                        </a>
                    @else
                        <span class="font-bold text-slate-900" @if ($isCurrent) aria-current="page" @endif>{{ $label }}</span>
                    @endif

                    @unless ($isCurrent)
                        <svg class="h-4 w-4 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    @endunless
                </li>
            @endforeach
        </ol>
    </nav>
@endif
