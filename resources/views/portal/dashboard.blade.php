<x-layouts.site title="Your results" :mobile-bar="false">
    <section class="on-dark bg-forest-900 text-white">
        <div class="wrap flex flex-wrap items-end justify-between gap-6 py-10 md:py-12">
            <div>
                <x-sign code="RX" tone="clay">Secure results portal</x-sign>
                <h1 class="display-2 mt-4">Hello, {{ $patient->first_name }}</h1>
                <p class="mt-2 text-white/80">Signed in as {{ $patient->email }}@if($patient->hospital_number) · Hospital no. {{ $patient->hospital_number }}@endif</p>
            </div>
            <form method="POST" action="{{ route('portal.logout') }}">@csrf<button class="btn btn-on-dark"><x-icon name="log-out" class="h-4 w-4" />Sign out</button></form>
        </div>
        <div class="canopy" aria-hidden="true"></div>
    </section>

    <div class="wrap mt-10">
        @if (session('status'))
            <div class="callout mb-8" role="status"><p class="flex gap-2"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-primary" />{{ session('status') }}</p></div>
        @endif
        <div class="flex flex-wrap items-baseline justify-between gap-3">
            <h2 class="display-3">Your reports</h2>
            <p class="text-ink-3">{{ $results->count() }} {{ \Illuminate\Support\Str::plural('report', $results->count()) }}</p>
        </div>

        @if ($results->isEmpty())
            <div class="panel mt-6 p-8 text-center">
                <x-icon name="inbox" class="mx-auto h-10 w-10 text-ink-3" />
                <p class="mt-3 font-bold">No reports yet</p>
                <p class="mx-auto mt-1 max-w-md text-ink-2">When the laboratory or radiology team publishes a report for you, it appears here and we email you.</p>
            </div>
        @else
            <ul class="panel mt-6 divide-y divide-line overflow-hidden">
                @foreach ($results as $r)
                    <li class="flex flex-wrap items-center gap-4 p-5">
                        <span class="flex h-12 w-12 flex-none items-center justify-center rounded-lg bg-mint-soft text-primary"><x-icon :name="$r->kind->icon()" class="h-6 w-6" /></span>
                        <div class="min-w-0 flex-1">
                            <p class="flex flex-wrap items-center gap-2 font-bold">{{ $r->title }} @if(! $r->first_viewed_at)<span class="chip" data-tone="attention"><x-icon name="dot" />New</span>@endif</p>
                            <p class="text-[0.9375rem] text-ink-2">{{ $r->kind->label() }} · Published {{ $r->created_at->format('j M Y') }}@if($r->collected_on) · Sample/scan {{ $r->collected_on->format('j M Y') }}@endif · {{ $r->sizeLabel() }}</p>
                        </div>
                        <div class="flex w-full gap-2 sm:w-auto">
                            <a href="{{ route('portal.results.download', $r) }}" target="_blank" rel="noopener" class="btn btn-sm btn-primary flex-1 sm:flex-none"><x-icon name="eye" class="h-4 w-4" />Open</a>
                            <a href="{{ route('portal.results.download', ['result' => $r, 'download' => 1]) }}" class="btn btn-sm btn-outline flex-1 sm:flex-none"><x-icon name="download" class="h-4 w-4" />Save PDF</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        <p class="callout mt-8 measure">Please discuss your results with the doctor who requested them. For questions about a report, call {{ format_phone(setting('phone_main'), true) }}, laboratory extension {{ setting('ext_lab') }} or radiology extension {{ setting('ext_radiology') }}.</p>
    </div>
</x-layouts.site>
