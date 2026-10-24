<x-layouts.site title="Careers" description="Nursing, laboratory, radiology, pharmacy and other jobs at Woodlands Hospital, Georgetown.">
    <x-page-header title="Work at Woodlands" sign="Careers" code="HR" photo="georgetown-seawall" photo-alt="The Georgetown seawall and beach (illustrative photo)"
        lead="Nurses, technologists, pharmacists and support staff keep a 24-hour hospital running. Apply online with your CV.">
        <div class="mt-7"><a href="{{ route('apply.create') }}" class="btn btn-clay">Send your CV<x-icon name="arrow-right" class="nudge h-4 w-4" /></a></div>
    </x-page-header>

    <div class="wrap mt-12">
        @if ($total === 0)
            <section class="panel p-8 md:p-10">
                <h2 class="display-3">No roles are advertised right now</h2>
                <p class="mt-3 max-w-2xl text-ink-2">We still welcome applications from registered nurses, medical technologists, radiographers and pharmacists. Send your CV and we will keep it on file for the next opening.</p>
                <a href="{{ route('apply.create') }}" class="btn btn-primary mt-6">Send your CV</a>
            </section>
        @else
            @if ($types->count() > 1)
                <nav class="tag-row" aria-label="Filter by area">
                    <a href="{{ route('jobs.index') }}" class="tag" @if(! $active) aria-current="true" @endif>All <span class="count">{{ $total }}</span></a>
                    @foreach ($types as $t)
                        <a href="{{ route('jobs.index', ['type' => $t['type']->value]) }}" class="tag" @if($active === $t['type']) aria-current="true" @endif>{{ $t['type']->label() }} <span class="count">{{ $t['count'] }}</span></a>
                    @endforeach
                </nav>
            @endif
            <ul class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($jobs as $job)
                    <li>
                        <a href="{{ route('jobs.show', $job) }}" class="card-link flex h-full flex-col p-6">
                            <x-sign :code="mb_strtoupper(mb_substr($job->category->label(), 0, 2))">{{ $job->category->label() }}</x-sign>
                            <h2 class="mt-3 text-[1.5rem] leading-tight">{{ $job->title }}</h2>
                            <p class="mt-2 text-ink-2">{{ $job->summary }}</p>
                            <p class="mt-auto flex flex-wrap gap-2 pt-5 text-sm">
                                <span class="chip"><x-icon name="briefcase" />{{ $job->employment_type->label() }}</span>
                                <span class="chip"><x-icon name="map-pin" />{{ $job->location }}</span>
                                @if ($job->openings > 1)<span class="chip"><x-icon name="users" />{{ $job->openings }} openings</span>@endif
                                @if ($job->ends_on)<span class="chip"><x-icon name="calendar" />Closes {{ $job->ends_on->format('j M') }}</span>@endif
                            </p>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.site>
