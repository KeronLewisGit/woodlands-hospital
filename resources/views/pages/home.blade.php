@php
    $main = setting('phone_main');
    $ambulance = setting('ambulance_phone');
    $depts = collect(woodlands('departments'));
    $always = $depts->where('group', 'always');
    $groups = woodlands('groups');
@endphp
<x-layouts.site>
    {{-- Hero: the hospital's own CT suite, whose forest mural gives the site its woodland green. --}}
    <section class="on-dark relative isolate overflow-hidden bg-forest-950 text-white">
        <div class="absolute inset-0 -z-10">
            <x-photo key="ct-suite" alt="" :eager="true" sizes="100vw" class="hero-photo h-full w-full" img-class="object-[70%_center]" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
        <div class="wrap grid gap-10 pb-12 pt-[38vw] sm:pt-56 lg:grid-cols-[1.35fr_1fr] lg:items-end lg:gap-16 lg:py-24">
            <div>
                <x-sign code="69">Woodlands Hospital · Georgetown</x-sign>
                <h1 class="display-1 mt-5 max-w-3xl">Care for Georgetown, <em>around the clock</em>, since 1969.</h1>
                <p class="lead mt-6 text-white/85">A private acute-care hospital on Carmichael Street, with a 24-hour Emergency Room, laboratory, radiology and pharmacy, an Intensive Care Unit and specialist clinics.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('appointments.create') }}" class="btn btn-clay">Request an appointment<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('portal.login') }}" class="btn btn-on-dark"><x-icon name="lock" class="h-4 w-4" />View your results</a>
                </div>
            </div>

            {{-- Lobby directory board: what is open right now, and how to reach it. --}}
            <div class="board bg-forest-900/90 ring-1 ring-white/15 backdrop-blur-sm" data-reveal>
                <div class="flex items-center justify-between gap-3 bg-white/[0.06] px-4 py-3">
                    <p class="sign text-white"><span class="sign-code">24</span>Open now</p>
                    <p class="text-sm text-white/75">Every day, all night</p>
                </div>
                <a href="{{ route('emergency') }}" class="board-row">
                    <span class="sign-code is-er" aria-hidden="true"><x-icon name="siren" class="h-4 w-4" /></span>
                    <span><span class="block font-bold">Emergency Room</span><span class="block text-sm text-white/75">Walk in, any hour</span></span>
                    <x-icon name="arrow-right" class="h-5 w-5 text-white/75" />
                </a>
                <a href="{{ tel_href($ambulance) }}" class="board-row">
                    <span class="sign-code is-er" aria-hidden="true"><x-icon name="ambulance" class="h-4 w-4" /></span>
                    <span><span class="block font-bold">Ambulance</span><span class="block text-sm text-white/75">Call {{ format_phone($ambulance) }} or ext {{ setting('ambulance_ext') }}</span></span>
                    <x-icon name="phone" class="h-5 w-5 text-white/75" />
                </a>
                @foreach (['laboratory', 'radiology', 'pharmacy'] as $key)
                    @php($d = $depts[$key])
                    <a href="{{ route('services') }}#{{ $key }}" class="board-row">
                        <span class="sign-code" aria-hidden="true"><x-icon :name="$d['icon']" class="h-4 w-4" /></span>
                        <span><span class="block font-bold">{{ $d['name'] }}</span>
                            <span class="block text-sm text-white/75">@if($key === 'pharmacy') WhatsApp {{ format_phone(setting('pharmacy_whatsapp')) }} @else Ext {{ setting($d['ext']) }} @endif</span></span>
                        <x-icon name="arrow-right" class="h-5 w-5 text-white/75" />
                    </a>
                @endforeach
            </div>
        </div>
        <div class="canopy" aria-hidden="true"></div>
    </section>

    {{-- The four things people come to the site to do. --}}
    <section class="wrap -mt-px pt-14 md:pt-20" aria-labelledby="tasks-title">
        <h2 id="tasks-title" class="sr-only">What would you like to do?</h2>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['appointments.create', 'calendar', 'Request an appointment', 'Clinic, specialist, scan or lab test. We confirm a time with you.', 'clay'],
                ['portal.login', 'lock', 'See your results', 'Lab and radiology reports in a private portal, on any phone.', null],
                ['pharmacy', 'pill', 'Refill a prescription', 'Send your list online or on WhatsApp. We tell you when it’s ready.', null],
                ['clinics', 'stethoscope', 'Find a clinic', 'Specialist clinic days and who to call to book.', null],
            ] as $i => [$route, $icon, $title, $text, $tone])
                <a href="{{ route($route) }}" class="card-link group flex flex-col p-6" data-reveal style="--reveal-delay: {{ $i * 70 }}ms">
                    <span @class(['flex h-12 w-12 items-center justify-center rounded-lg', 'bg-clay text-white' => $tone === 'clay', 'bg-mint-soft text-primary' => ! $tone])><x-icon :name="$icon" class="h-6 w-6" /></span>
                    <span class="font-display mt-5 text-[1.45rem] leading-tight">{{ $title }}</span>
                    <span class="mt-2 text-ink-2">{{ $text }}</span>
                    <span class="mt-auto flex items-center gap-1.5 pt-5 font-bold text-link">Start<x-icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" /></span>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Department directory, set out like the lobby sign. --}}
    <section class="wrap mt-24" aria-labelledby="dept-title">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <x-sign code="A–Z">Departments</x-sign>
                <h2 id="dept-title" class="display-2 mt-4 max-w-2xl">Emergency to ophthalmology, under one roof</h2>
            </div>
            <a href="{{ route('services') }}" class="btn btn-outline">All services<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <div class="mt-10 grid items-start gap-6 lg:grid-cols-3">
            @foreach ($groups as $gKey => $g)
                <div class="board board-light panel" data-reveal style="--reveal-delay: {{ $loop->index * 80 }}ms">
                    <div class="flex items-center gap-3 border-b border-line bg-surface-2 px-4 py-3">
                        <span class="sign-code">{{ $g['code'] }}</span><h3 class="text-lg font-semibold" style="font-family: var(--ff-sans); letter-spacing: 0">{{ $g['name'] }}</h3>
                    </div>
                    @foreach ($depts->where('group', $gKey)->take($gKey === 'clinic' ? 8 : 6) as $key => $d)
                        <a href="{{ route('services') }}#{{ $key }}" class="board-row">
                            <x-icon :name="$d['icon']" class="h-5 w-5 text-primary" />
                            <span class="font-semibold">{{ $d['name'] }}</span>
                            <x-icon name="chevron-right" class="h-4 w-4 text-ink-3" />
                        </a>
                    @endforeach
                    @if ($gKey === 'clinic')
                        <a href="{{ route('services') }}#clinics" class="board-row text-link"><x-icon name="plus" class="h-5 w-5" /><span class="font-bold">{{ $depts->where('group', 'clinic')->count() - 8 }} more specialist clinics</span><x-icon name="chevron-right" class="h-4 w-4" /></a>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    {{-- Results portal: the pitch centrepiece, in plain words. --}}
    <section class="mt-24 bg-surface-2/60 py-16 md:py-24" aria-labelledby="portal-title">
        <div class="wrap grid items-center gap-12 lg:grid-cols-[1fr_1.05fr]">
            <div>
                <x-sign code="RX" tone="clay">Secure results portal</x-sign>
                <h2 id="portal-title" class="display-2 mt-4">Your results, privately, on your phone</h2>
                <p class="lead mt-5 text-ink-2">When the laboratory or radiology team finishes your report, they publish it to your own portal account. We email you that it is ready, never the result itself.</p>
                <ul class="mt-8 space-y-5">
                    @foreach ([
                        ['shield', 'Only you can open it', 'Each report is stored privately and shown only to the patient it belongs to, after they sign in.'],
                        ['mail', 'A heads-up, not a leak', 'The email says a report is waiting. Test names and findings stay inside the portal.'],
                        ['download', 'Keep and share a copy', 'Open the PDF on your phone, save it, or bring it to your doctor.'],
                    ] as [$icon, $t, $d])
                        <li class="flex gap-4">
                            <span class="flex h-11 w-11 flex-none items-center justify-center rounded-lg bg-surface text-primary ring-1 ring-line"><x-icon :name="$icon" /></span>
                            <span><span class="block font-bold">{{ $t }}</span><span class="block text-ink-2">{{ $d }}</span></span>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('portal.login') }}" class="btn btn-primary"><x-icon name="lock" class="h-4 w-4" />Sign in to the portal</a>
                    <a href="{{ route('patients') }}#results" class="btn btn-outline">How to get access</a>
                </div>
            </div>

            {{-- Illustration of the portal screen (example data, labelled as such). --}}
            <figure class="relative" data-reveal aria-label="Example of the results portal screen">
                <div class="panel overflow-hidden shadow-md">
                    <div class="flex items-center gap-3 border-b border-line bg-forest-900 px-5 py-3.5 text-white">
                        <x-icon name="lock" class="h-4 w-4 text-leaf" /><span class="font-semibold">Your results</span>
                        <span class="ml-auto rounded bg-white/15 px-2 py-0.5 text-xs font-bold uppercase tracking-wider">Example</span>
                    </div>
                    <ul class="divide-y divide-line">
                        @foreach ([['flask', 'Full blood count', 'Laboratory', 'New'], ['scan', 'Ultrasound, abdomen', 'Radiology', null], ['flask', 'Lipid profile', 'Laboratory', null]] as [$icon, $t, $k, $new])
                            <li class="flex items-center gap-3 px-4 py-4 sm:gap-4 sm:px-5">
                                <span class="hidden h-10 w-10 flex-none items-center justify-center rounded-lg bg-mint-soft text-primary min-[400px]:flex"><x-icon :name="$icon" /></span>
                                <span class="min-w-0 flex-1"><span class="block truncate font-bold">{{ $t }}</span><span class="block text-sm text-ink-3">{{ $k }} · PDF</span></span>
                                @if ($new)<span class="chip" data-tone="attention"><x-icon name="dot" />{{ $new }}</span>@endif
                                <span class="inline-flex h-10 flex-none items-center gap-1.5 rounded-lg px-3 text-sm font-bold text-link ring-1 ring-line"><x-icon name="eye" class="h-4 w-4" /><span class="hidden sm:inline">Open</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <figcaption class="mt-3 text-sm text-ink-3">Illustration with example reports. Replaces the old portal link to a bare IP address.</figcaption>
            </figure>
        </div>
    </section>

    {{-- About: the hospital's own published figures. --}}
    <section class="wrap mt-24 grid items-center gap-12 lg:grid-cols-2" aria-labelledby="about-title">
        <x-photo key="nurses-station" alt="The nurses’ station and ward corridor at Woodlands Hospital" sizes="(min-width: 1024px) 50vw, 100vw" class="aspect-[4/3] rounded-xl" data-reveal />
        <div>
            <x-sign code="IP">Care with a human touch</x-sign>
            <h2 id="about-title" class="display-2 mt-4">From one operating theatre in 1969 to a 55-bed hospital today</h2>
            <p class="mt-5 text-ink-2 measure">Woodlands opened in 1969 with an outpatient department, small male, female and maternity wards and one operating theatre. Today it is a multi-specialty hospital in the heart of Georgetown that, in its own words, provides care “respectful of and responsive to individual patient preference, needs and values.”</p>
            <dl class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-line ring-1 ring-line sm:grid-cols-4 lg:grid-cols-2">
                @foreach (woodlands('facts') as $fact)
                    <div class="bg-surface p-4">
                        <dt class="text-sm text-ink-3">{{ $fact['label'] }}</dt>
                        <dd class="font-display num mt-1 text-4xl">{{ $fact['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
            <p class="mt-3 text-sm text-ink-3">Figures as published by Woodlands Hospital (About Us, 2024).</p>
            <a href="{{ route('about') }}" class="btn btn-outline mt-7">About Woodlands<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
    </section>

    {{-- Extension directory, from the hospital's own contact table. --}}
    <section class="wrap mt-24 grid gap-10 lg:grid-cols-[1fr_1.2fr]" aria-labelledby="ext-title">
        <div>
            <x-sign code="☎">Direct lines</x-sign>
            <h2 id="ext-title" class="display-2 mt-4">Call the right desk first time</h2>
            <p class="mt-5 text-ink-2 measure">Call the main line, {{ format_phone($main, true) }}, and ask for the extension, or tap a row on your phone to dial it directly.</p>
            @if ($hours = setting('clinic_hours_note'))<p class="callout mt-6">{{ $hours }}</p>@endif
        </div>
        <div class="board board-light panel">
            @foreach (extension_list() as $row)
                <a href="{{ tel_href($main, $row['ext']) }}" class="board-row">
                    <span class="sign-code num">{{ $row['ext'] }}</span>
                    <span class="font-semibold">{{ $row['label'] }}</span>
                    <x-icon name="phone" class="h-4 w-4 text-ink-3" />
                </a>
            @endforeach
            <a href="{{ whatsapp_href(setting('pharmacy_whatsapp'), 'Hello Woodlands Pharmacy, ') }}" class="board-row" rel="noopener">
                <span class="sign-code"><x-icon name="whatsapp" class="h-4 w-4" /></span>
                <span><span class="block font-semibold">Pharmacy, prescriptions &amp; enquiries</span><span class="block text-sm text-ink-3">WhatsApp message only: {{ format_phone(setting('pharmacy_whatsapp')) }}</span></span>
                <x-icon name="external" class="h-4 w-4 text-ink-3" />
            </a>
        </div>
    </section>

    {{-- Careers + location --}}
    <section class="wrap mt-24 grid gap-6 lg:grid-cols-2">
        <div class="on-dark relative overflow-hidden rounded-xl bg-forest-900 p-8 text-white md:p-10">
            <x-sign code="HR">Careers</x-sign>
            <h2 class="display-3 mt-4 max-w-md">Nurses, technologists, pharmacists: join the team</h2>
            <p class="mt-3 max-w-md text-white/80">{{ $openJobs ? $openJobs.' '.\Illuminate\Support\Str::plural('role', $openJobs).' open right now. Apply online with your CV.' : 'No roles are advertised right now. You can still send us your CV.' }}</p>
            <a href="{{ route('jobs.index') }}" class="btn btn-on-dark mt-7">See open roles<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            <div class="canopy absolute inset-x-0 bottom-0" aria-hidden="true"></div>
        </div>
        <div class="panel grid overflow-hidden sm:grid-cols-[1fr_1.1fr]">
            <x-photo key="georgetown-cathedral" alt="St George’s Cathedral, a few streets from the hospital in Georgetown" sizes="(min-width: 1024px) 25vw, 100vw" class="aspect-[4/3] sm:aspect-auto" />
            <div class="p-7">
                <x-sign code="GT">Find us</x-sign>
                <address class="mt-4 not-italic text-lg leading-relaxed">{{ woodlands('address.street') }}<br>{{ woodlands('address.area') }}, Georgetown</address>
                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ woodlands('map_url') }}" class="btn btn-sm btn-outline" target="_blank" rel="noopener"><x-icon name="map-pin" class="h-4 w-4" />Map</a>
                    <a href="{{ route('contact') }}" class="btn btn-sm btn-outline">Contact</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
