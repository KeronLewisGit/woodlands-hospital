@php($main = setting('phone_main'))
<x-layouts.site title="Specialist clinic timetable" description="Weekly specialist clinic days and times at Woodlands Hospital, Georgetown, and how to book.">
    <x-page-header title="Specialist clinic timetable" sign="Outpatient clinics" code="OP"
        lead="Which clinics run on which day. Clinic times can change: please call to confirm before you travel.">
        <div class="mt-7 flex flex-wrap gap-3">
            <a href="{{ route('appointments.create', ['service' => 'specialist']) }}" class="btn btn-clay">Request a clinic appointment</a>
            <a href="{{ tel_href($main, setting('ext_clinics')) }}" class="btn btn-on-dark"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($main) }} ext {{ setting('ext_clinics') }}</a>
        </div>
    </x-page-header>

    <div class="wrap mt-12">
        @if ($note = setting('clinic_hours_note'))<p class="callout mb-8 measure">{{ $note }}</p>@endif

        @if ($sessions->isEmpty() && ! $active)
            {{-- No timetable published yet: say so plainly and give the phone route. --}}
            <section class="panel grid gap-8 p-8 md:grid-cols-[auto_1fr] md:p-10">
                <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-mint-soft text-primary"><x-icon name="calendar" class="h-7 w-7" /></span>
                <div>
                    <h2 class="display-3">The clinic timetable is being updated</h2>
                    <p class="mt-3 text-ink-2 measure">For specialist clinic days, times and appointments, call the clinic desk on {{ format_phone($main, true) }}, extension {{ setting('ext_clinics') }}, or send an appointment request and we will call you back with a time.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ tel_href($main, setting('ext_clinics')) }}" class="btn btn-primary"><x-icon name="phone" class="h-4 w-4" />Call ext {{ setting('ext_clinics') }}</a>
                        <a href="{{ route('services') }}#clinics" class="btn btn-outline">See all specialist clinics</a>
                    </div>
                </div>
            </section>
        @else
            @if ($departments->count() > 1)
                <nav class="tag-row" aria-label="Filter by clinic">
                    <a href="{{ route('clinics') }}" class="tag" @if(! $active) aria-current="true" @endif>All clinics</a>
                    @foreach ($departments as $key => $name)
                        <a href="{{ route('clinics', ['department' => $key]) }}" class="tag" @if($active === $key) aria-current="true" @endif>{{ $name }}</a>
                    @endforeach
                </nav>
            @endif
            <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach (\App\Models\ClinicSession::WEEKDAYS as $num => $day)
                    @continue(! $byDay->has($num))
                    <section class="panel overflow-hidden" aria-labelledby="day-{{ $num }}">
                        <h2 id="day-{{ $num }}" class="flex items-center gap-3 border-b border-line bg-surface-2 px-5 py-3 text-lg font-bold" style="font-family: var(--ff-sans); letter-spacing: 0"><span class="sign-code">{{ mb_substr($day, 0, 2) }}</span>{{ $day }}</h2>
                        <ul class="divide-y divide-line">
                            @foreach ($byDay[$num] as $s)
                                <li class="px-5 py-4">
                                    <p class="num font-bold">{{ $s->timeRange() }}</p>
                                    <p class="mt-0.5">{{ department($s->department)['name'] ?? $s->department }}@if($s->who()) · <span class="text-ink-2">{{ $s->who() }}</span>@endif</p>
                                    @if ($s->note)<p class="mt-1 text-sm text-ink-3">{{ $s->note }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
            @if ($sessions->isEmpty())<p class="mt-8 text-ink-2">No sessions are listed for this clinic. Call extension {{ setting('ext_clinics') }} to ask.</p>@endif
        @endif
    </div>
</x-layouts.site>
