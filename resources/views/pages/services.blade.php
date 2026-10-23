@php
    $depts = collect(woodlands('departments'));
    $main = setting('phone_main');
@endphp
<x-layouts.site title="Services & departments" description="Woodlands Hospital services: 24-hour Emergency Room, ambulance, laboratory, radiology and pharmacy; ICU, theatres and wards; and specialist clinics from cardiology to physiotherapy.">
    <x-page-header title="Services & departments" sign="Hospital directory" code="A–Z" photo="ultrasound" photo-alt="An ultrasound room at Woodlands Hospital"
        lead="Everything the hospital lists as a service, grouped the way you would find it in the building. Tap a department to call the right desk.">
        <nav class="mt-7 flex flex-wrap gap-2" aria-label="Jump to a group">
            @foreach (woodlands('groups') as $key => $g)
                <a href="#{{ $key === 'clinic' ? 'clinics' : $key }}" class="btn btn-sm btn-on-dark"><span class="sign-code !h-6 !min-w-6 !text-[0.65rem]">{{ $g['code'] }}</span>{{ $g['name'] }}</a>
            @endforeach
        </nav>
    </x-page-header>

    @foreach (woodlands('groups') as $gKey => $g)
        <section id="{{ $gKey === 'clinic' ? 'clinics' : $gKey }}" class="wrap mt-16 md:mt-20" aria-labelledby="g-{{ $gKey }}">
            <div class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-5">
                <div>
                    <x-sign :code="$g['code']" :tone="$gKey === 'always' ? 'er' : null">{{ $gKey === 'always' ? 'Day and night' : ($gKey === 'inpatient' ? 'Admitted care' : 'Outpatient') }}</x-sign>
                    <h2 id="g-{{ $gKey }}" class="display-3 mt-3">{{ $g['name'] }}</h2>
                </div>
                @if ($gKey === 'clinic')
                    <a href="{{ route('clinics') }}" class="btn btn-sm btn-outline"><x-icon name="calendar" class="h-4 w-4" />Clinic timetable</a>
                @endif
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-2 {{ $gKey === 'clinic' ? 'lg:grid-cols-3' : '' }}">
                @foreach ($depts->where('group', $gKey) as $key => $d)
                    <article id="{{ $key }}" class="panel flex flex-col overflow-hidden scroll-mt-32">
                        @if (! empty($d['photo']))
                            <x-photo :key="$d['photo']" :alt="''" sizes="(min-width: 768px) 50vw, 100vw" class="aspect-[16/7]" />
                        @endif
                        <div class="flex flex-1 flex-col p-6">
                            <div class="flex items-center gap-3">
                                <span @class(['flex h-11 w-11 flex-none items-center justify-center rounded-lg', 'bg-er text-white' => in_array($key, ['emergency', 'ambulance']), 'bg-mint-soft text-primary' => ! in_array($key, ['emergency', 'ambulance'])])><x-icon :name="$d['icon']" /></span>
                                <h3 class="text-[1.4rem] leading-tight">{{ $d['name'] }}</h3>
                            </div>
                            <p class="mt-3 text-ink-2">{{ $d['blurb'] }}</p>
                            @if (($doctorCounts[$key] ?? 0) > 0)
                                <p class="mt-2 text-sm text-ink-3">{{ $doctorCounts[$key] }} {{ \Illuminate\Support\Str::plural('doctor', $doctorCounts[$key]) }} in the directory</p>
                            @endif
                            <div class="mt-auto flex flex-wrap gap-2 pt-5">
                                @if ($key === 'emergency')
                                    <a href="{{ route('emergency') }}" class="btn btn-sm btn-er">Emergency information</a>
                                @elseif ($key === 'ambulance')
                                    <a href="{{ tel_href(setting('ambulance_phone')) }}" class="btn btn-sm btn-er"><x-icon name="ambulance" class="h-4 w-4" />Call {{ format_phone(setting('ambulance_phone')) }}</a>
                                @elseif ($key === 'pharmacy')
                                    <a href="{{ route('pharmacy') }}" class="btn btn-sm btn-primary">Request a refill</a>
                                    <a href="{{ whatsapp_href(setting('pharmacy_whatsapp')) }}" class="btn btn-sm btn-outline" rel="noopener"><x-icon name="whatsapp" class="h-4 w-4" />WhatsApp</a>
                                @else
                                    @if ($d['ext'])
                                        <a href="{{ tel_href($main, setting($d['ext'])) }}" class="btn btn-sm btn-outline"><x-icon name="phone" class="h-4 w-4" />Ext {{ setting($d['ext']) }}</a>
                                    @endif
                                    @if ($gKey === 'clinic' || in_array($key, ['laboratory', 'radiology']))
                                        <a href="{{ route('appointments.create', ['service' => match ($key) { 'laboratory' => 'lab', 'radiology' => 'radiology', default => 'specialist' }, 'department' => $gKey === 'clinic' ? $key : null]) }}" class="btn btn-sm btn-primary">Request appointment</a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="wrap mt-16">
        <p class="callout measure">This list follows the services Woodlands Hospital publishes. If you can’t see what you need, call {{ format_phone($main, true) }} and the switchboard will direct you.</p>
    </section>
</x-layouts.site>
