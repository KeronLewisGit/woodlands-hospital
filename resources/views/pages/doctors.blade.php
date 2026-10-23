<x-layouts.site title="Find a doctor" description="Consultants and doctors at Woodlands Hospital, Georgetown, by specialty.">
    <x-page-header title="Find a doctor" sign="Consultants" code="Dr"
        lead="Woodlands works with more than 70 consultants across its specialist clinics. Search by name or specialty, then request an appointment." />

    <div class="wrap mt-12">
        @if ($total === 0)
            {{-- Directory not published yet. No placeholder people: just the way to book. --}}
            <section class="panel grid gap-8 p-8 md:grid-cols-[auto_1fr] md:p-10">
                <span class="flex h-14 w-14 items-center justify-center rounded-xl bg-mint-soft text-primary"><x-icon name="stethoscope" class="h-7 w-7" /></span>
                <div>
                    <h2 class="display-3">Our doctor directory is coming soon</h2>
                    <p class="mt-3 text-ink-2 measure">While we prepare it, the clinic desk can tell you which consultant runs each clinic and when. Call {{ format_phone(setting('phone_main'), true) }}, extension {{ setting('ext_clinics') }}, or send a request and we will match you to the right specialist.</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('appointments.create', ['service' => 'specialist']) }}" class="btn btn-clay">Request an appointment</a>
                        <a href="{{ route('services') }}#clinics" class="btn btn-outline">Browse specialties</a>
                    </div>
                </div>
            </section>
        @else
            <form method="GET" class="flex flex-col gap-3 sm:flex-row" role="search">
                @if ($active)<input type="hidden" name="department" value="{{ $active }}">@endif
                <label for="q" class="sr-only">Search doctors</label>
                <input id="q" name="q" value="{{ $q }}" class="input sm:max-w-md" placeholder="Name or specialty" autocomplete="off">
                <button class="btn btn-primary"><x-icon name="search" class="h-4 w-4" />Search</button>
            </form>
            <nav class="tag-row mt-5" aria-label="Filter by specialty">
                <a href="{{ route('doctors', array_filter(['q' => $q])) }}" class="tag" @if(! $active) aria-current="true" @endif>All <span class="count">{{ $total }}</span></a>
                @foreach ($departments as $key => $d)
                    <a href="{{ route('doctors', array_filter(['department' => $key, 'q' => $q])) }}" class="tag" @if($active === $key) aria-current="true" @endif>{{ $d['name'] }} <span class="count">{{ $d['count'] }}</span></a>
                @endforeach
            </nav>

            @if ($doctors->isEmpty())
                <p class="mt-10 text-ink-2">No doctors match “{{ $q }}”. <a class="text-link" href="{{ route('doctors') }}">Clear the search</a>.</p>
            @else
                <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($doctors as $doctor)
                        <li class="panel flex flex-col p-6">
                            <div class="flex items-start gap-4">
                                <span class="font-display flex h-14 w-14 flex-none items-center justify-center rounded-full bg-forest-900 text-xl text-white" aria-hidden="true">{{ $doctor->initials() }}</span>
                                <div>
                                    <h2 class="text-[1.35rem] leading-tight">{{ $doctor->name }}</h2>
                                    <p class="mt-1 text-ink-2">{{ $doctor->title ?: $doctor->departmentName() }}</p>
                                    @if ($doctor->qualifications)<p class="mt-0.5 text-sm text-ink-3">{{ $doctor->qualifications }}</p>@endif
                                </div>
                            </div>
                            @if ($doctor->bio)<p class="mt-4 text-[0.9375rem] text-ink-2">{{ $doctor->bio }}</p>@endif
                            <div class="mt-auto flex flex-wrap items-center gap-3 pt-5">
                                <span class="chip"><x-icon :name="department($doctor->department)['icon'] ?? 'stethoscope'" />{{ $doctor->departmentName() }}</span>
                                <a href="{{ route('appointments.create', ['service' => 'specialist', 'department' => $doctor->department]) }}" class="ml-auto font-bold text-link">Request<span class="sr-only"> an appointment with {{ $doctor->name }}</span> →</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>
</x-layouts.site>
