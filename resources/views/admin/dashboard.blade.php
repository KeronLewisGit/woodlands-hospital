@php
    $step = collect([1, 2, 5, 10, 20, 50, 100])->first(fn ($s) => $dailyMax / $s <= 4) ?? 100;
    $yMax = (int) (ceil($dailyMax / $step) * $step);
    $ticks = range(0, $yMax, $step);
    $svcMax = max(1, $byService->max('count'));
@endphp
<x-layouts.admin title="Overview">
    @include('admin._notify-warning')

    {{-- The one number the front desk must act on. --}}
    <section class="a-card flex flex-col gap-5 p-5 md:flex-row md:items-center md:justify-between" aria-labelledby="verdict">
        @if ($awaitingCount > 0)
            <div>
                <p id="verdict" class="font-semibold text-ink-2">Appointment requests waiting to be confirmed</p>
                <p class="mt-1 text-5xl font-bold tracking-tight md:text-6xl"><span class="num">{{ number_format($awaitingCount) }}</span> <span class="text-2xl font-semibold text-ink-2 md:text-3xl">{{ \Illuminate\Support\Str::plural('request', $awaitingCount) }}</span></p>
                <p class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-ink-2">
                    @if ($urgentCount)<span class="chip" data-tone="attention"><x-icon name="clock" />{{ $urgentCount }} want today or tomorrow</span>@endif
                    @if ($oldestAwaiting)<span class="flex items-center gap-1.5"><x-icon name="inbox" class="h-4 w-4" />Oldest arrived {{ $oldestAwaiting->diffForHumans() }}</span>@endif
                </p>
            </div>
            <a href="{{ route('admin.appointments.index', ['status' => 'requested']) }}" class="a-btn a-btn-clay self-start px-5 md:self-center">Confirm soonest first<x-icon name="arrow-right" class="h-4 w-4" /></a>
        @else
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-mint-soft"><x-icon name="check" class="h-6 w-6 text-[color:var(--good)]" /></span>
                <div>
                    <p id="verdict" class="text-2xl font-bold">All requests confirmed</p>
                    <p class="text-ink-2">New appointment requests appear here as they arrive.</p>
                </div>
            </div>
        @endif
    </section>

    <section class="mt-5 grid gap-5 sm:grid-cols-2 xl:grid-cols-4" aria-label="Key figures">
        @foreach ($tiles as $tile)
            <x-stat-tile :label="$tile['label']" :value="$tile['value']" :previous="$tile['previous'] ?? null" :period="$tile['period'] ?? null" :note="$tile['note'] ?? null" :href="$tile['href']" />
        @endforeach
    </section>

    <div class="mt-5 grid gap-5 xl:grid-cols-[1.6fr_1fr]">
        <section class="a-card flex flex-col p-5" aria-labelledby="daily-title">
            <h2 id="daily-title" class="font-bold">Appointment requests per day</h2>
            <p class="text-sm text-ink-3">Last {{ $chartDays }} days · {{ number_format($daily->sum('count')) }} total</p>
            <div class="relative mt-6 flex flex-1 flex-col" x-data="columnChart" @mouseleave="hide()">
                <div class="relative min-h-56 flex-1 pl-8" role="img" aria-label="Column chart of appointment requests per day over the last {{ $chartDays }} days, peaking at {{ $dailyMax }}. The table below lists every value.">
                    @foreach ($ticks as $t)
                        <div class="absolute inset-x-0 flex items-center" style="bottom: {{ $yMax ? $t / $yMax * 100 : 0 }}%">
                            <span class="num w-7 -translate-y-px pr-2 text-right text-xs text-ink-3">{{ $t }}</span>
                            <span class="h-px flex-1 bg-line"></span>
                        </div>
                    @endforeach
                    <div class="absolute inset-y-0 left-8 right-0 flex items-end gap-[2px]">
                        @foreach ($daily as $d)
                            <div class="flex h-full flex-1 items-end justify-center" @mouseenter="show($el)" data-label="{{ $d['date']->format('D j M') }}" data-value="{{ $d['count'] }}">
                                <div class="bar w-full max-w-6 rounded-t bg-series transition-opacity" :class="tip && tip.label !== @js($d['date']->format('D j M')) && 'opacity-40'" style="height: {{ $yMax ? $d['count'] / $yMax * 100 : 0 }}%; min-height: {{ $d['count'] ? '2px' : '0' }}"></div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="mt-2 flex pl-8 text-xs text-ink-3">
                    @foreach ($daily as $d)
                        <span class="relative h-4 flex-1">@if (($loop->count - 1 - $loop->index) % 7 === 0)<span class="absolute left-1/2 -translate-x-1/2 whitespace-nowrap">{{ $d['date']->format('j M') }}</span>@endif</span>
                    @endforeach
                </div>
                <div x-show="tip" style="display:none" class="pointer-events-none absolute z-10 -translate-x-1/2 -translate-y-full rounded-lg bg-ink px-3 py-2 text-sm text-bg shadow-lg" :style="tip && `left:${tip.x}px; top:${tip.y - 8}px`">
                    <span class="block text-xs opacity-75" x-text="tip?.label"></span>
                    <span class="font-bold" x-text="tip ? `${tip.value} ${tip.value === '1' ? 'request' : 'requests'}` : ''"></span>
                </div>
            </div>
            <details class="mt-1 text-sm">
                <summary class="cursor-pointer py-3 font-semibold text-link">Show as table</summary>
                <div class="mt-3 max-h-64 overflow-auto">
                    <table class="data-table"><thead><tr><th>Date</th><th class="r">Requests</th></tr></thead>
                        <tbody>@foreach ($daily->reverse() as $d)<tr><td>{{ $d['date']->format('D j M') }}</td><td class="r">{{ $d['count'] }}</td></tr>@endforeach</tbody>
                    </table>
                </div>
            </details>
        </section>

        <div class="grid content-start gap-5">
            <section class="a-card overflow-hidden" aria-labelledby="today-title">
                <div class="flex items-center justify-between p-5 pb-3">
                    <h2 id="today-title" class="font-bold">Booked for today</h2>
                    <span class="text-sm text-ink-3">{{ today()->format('l j M') }}</span>
                </div>
                @if ($today->isEmpty())
                    <p class="px-5 pb-5 text-ink-2">No confirmed appointments today.</p>
                @else
                    <ul class="divide-y divide-line border-t border-line">
                        @foreach ($today as $a)
                            <li><a href="{{ route('admin.appointments.show', $a) }}" class="flex min-h-12 items-center gap-3 px-5 py-2 hover:bg-surface-2"><span class="num w-20 flex-none whitespace-nowrap font-bold">{{ $a->scheduled_for?->format('g:i a') }}</span><span class="min-w-0 flex-1 truncate">{{ $a->full_name }}</span><span class="truncate text-sm text-ink-3">{{ $a->service->label() }}</span></a></li>
                        @endforeach
                    </ul>
                @endif
            </section>
            <section class="a-card p-5" aria-labelledby="svc-title">
                <h2 id="svc-title" class="font-bold">By service</h2>
                <p class="text-sm text-ink-3">Requests, last {{ $chartDays }} days</p>
                <ul class="mt-4 space-y-3">
                    @foreach ($byService as $row)
                        <li>
                            <a href="{{ route('admin.appointments.index', ['service' => $row['value']]) }}" class="group grid grid-cols-[10rem_1fr_2.5rem] items-center gap-3 text-sm">
                                <span class="truncate text-ink-2 group-hover:text-ink">{{ $row['label'] }}</span>
                                <span class="h-5"><span class="bar block h-full rounded-r bg-series" style="width: {{ $row['count'] / $svcMax * 100 }}%; min-width: {{ $row['count'] ? '2px' : '0' }}"></span></span>
                                <span class="num text-right font-semibold">{{ number_format($row['count']) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-layouts.admin>
