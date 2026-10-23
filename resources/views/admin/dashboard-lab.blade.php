<x-layouts.admin title="Overview">
    <x-slot:actions><a href="{{ route('admin.patients.index') }}" class="a-btn a-btn-primary"><x-icon name="upload" class="h-4 w-4" />Publish a report</a></x-slot:actions>

    {{-- The number lab staff act on: reports patients have not opened after 3 days (someone may need a call). --}}
    <section class="a-card flex flex-col gap-5 p-5 md:flex-row md:items-center md:justify-between" aria-labelledby="verdict">
        @if ($unopenedCount > 0)
            <div>
                <p id="verdict" class="font-semibold text-ink-2">Reports not opened after 3 days</p>
                <p class="mt-1 text-5xl font-bold tracking-tight md:text-6xl"><span class="num">{{ $unopenedCount }}</span> <span class="text-2xl font-semibold text-ink-2 md:text-3xl">{{ \Illuminate\Support\Str::plural('report', $unopenedCount) }}</span></p>
                <p class="mt-2 text-ink-2">These patients may not have seen the email. Consider a phone call for anything urgent.</p>
            </div>
        @else
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-mint-soft"><x-icon name="check" class="h-6 w-6 text-[color:var(--good)]" /></span>
                <div><p id="verdict" class="text-2xl font-bold">Every report has been opened</p><p class="text-ink-2">Reports not opened within 3 days are listed here.</p></div>
            </div>
        @endif
    </section>

    @if ($unopened->isNotEmpty())
        <section class="a-card mt-5 overflow-hidden">
            <table class="data-table">
                <thead><tr><th>Patient</th><th>Report</th><th>Published</th></tr></thead>
                <tbody>
                    @foreach ($unopened as $r)
                        <tr><td><a class="font-semibold hover:text-link" href="{{ route('admin.patients.show', $r->patient) }}">{{ $r->patient->full_name }}</a><span class="block text-sm text-ink-3">{{ $r->patient->phone ? format_phone($r->patient->phone) : $r->patient->email }}</span></td><td>{{ $r->title }}</td><td class="whitespace-nowrap">{{ $r->created_at->diffForHumans() }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif

    <section class="mt-5 grid gap-5 sm:grid-cols-3" aria-label="Key figures">
        @foreach ($tiles as $tile)
            <x-stat-tile :label="$tile['label']" :value="$tile['value']" :previous="$tile['previous'] ?? null" :period="$tile['period'] ?? null" :note="$tile['note'] ?? null" :href="$tile['href']" />
        @endforeach
    </section>

    <section class="a-card mt-5 overflow-hidden" aria-labelledby="latest">
        <h2 id="latest" class="p-5 pb-3 font-bold">Latest published reports</h2>
        @if ($latest->isEmpty())
            <p class="px-5 pb-5 text-ink-2">Nothing published yet. Open a patient record to upload a PDF report.</p>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Patient</th><th>Report</th><th>Type</th><th>By</th><th>Published</th><th>Opened</th></tr></thead>
                    <tbody>
                        @foreach ($latest as $r)
                            <tr>
                                <td><a class="font-semibold hover:text-link" href="{{ route('admin.patients.show', $r->patient) }}">{{ $r->patient->full_name }}</a></td>
                                <td>{{ $r->title }}</td>
                                <td>{{ $r->kind->label() }}</td>
                                <td class="text-ink-2">{{ $r->uploader->name ?? '—' }}</td>
                                <td class="whitespace-nowrap">{{ $r->created_at->format('j M, g:i a') }}</td>
                                <td>@if($r->first_viewed_at)<x-chip tone="good" icon="check">Opened</x-chip>@else<x-chip tone="neutral" icon="dot">Not yet</x-chip>@endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.admin>
