@php
    $ld = [
        '@context' => 'https://schema.org', '@type' => 'JobPosting',
        'title' => $job->title, 'description' => nl2br(e($job->summary."\n\n".$job->duties."\n\n".$job->requirements)),
        'datePosted' => $job->created_at->toDateString(), 'employmentType' => $job->employment_type->schema(),
        'hiringOrganization' => ['@type' => 'Organization', 'name' => woodlands('name'), 'sameAs' => route('home')],
        'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'streetAddress' => woodlands('address.street'), 'addressLocality' => 'Georgetown', 'addressCountry' => 'GY']],
    ] + ($job->ends_on ? ['validThrough' => $job->ends_on->endOfDay()->toAtomString()] : []);
@endphp
<x-layouts.site :title="$job->title" :description="$job->summary" :json-ld="$isOpen ? [$ld] : []">
    <x-page-header :title="$job->title" :sign="$job->category->label()" :code="mb_strtoupper(mb_substr($job->category->label(), 0, 2))" :lead="$job->summary">
        <p class="mt-6 flex flex-wrap gap-2 text-sm">
            <span class="chip"><x-icon name="briefcase" />{{ $job->employment_type->label() }}</span>
            <span class="chip"><x-icon name="map-pin" />{{ $job->location }}</span>
            @if ($job->ends_on)<span class="chip"><x-icon name="calendar" />Closes {{ $job->ends_on->format('j F Y') }}</span>@endif
        </p>
    </x-page-header>
    <div class="wrap mt-12 grid gap-12 lg:grid-cols-[1fr_20rem]">
        <div class="prose-copy measure">
            @if (! $isOpen)<p class="callout">This role is no longer open. You can still <a href="{{ route('apply.create') }}">send your CV</a>.</p>@endif
            @if ($job->lines('duties'))<h2 class="!mt-0">What you’ll do</h2><ul>@foreach ($job->lines('duties') as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
            @if ($job->lines('requirements'))<h2>What you’ll need</h2><ul>@foreach ($job->lines('requirements') as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
        </div>
        <aside class="lg:sticky lg:top-28 lg:self-start">
            @if ($isOpen)<a href="{{ route('apply.create', ['job' => $job->slug]) }}" class="btn btn-clay w-full">Apply for this role<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>@endif
            <a href="{{ route('jobs.index') }}" class="btn btn-outline mt-3 w-full"><x-icon name="arrow-left" class="h-4 w-4" />All roles</a>
        </aside>
    </div>
</x-layouts.site>
