<x-layouts.admin title="Clinic timetable">
    <x-slot:actions><a href="{{ route('admin.clinics.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add a session</a></x-slot:actions>
    <p class="mb-5 max-w-3xl text-ink-2">Weekly specialist clinic sessions shown on the public <a class="text-link underline" href="{{ route('clinics') }}" target="_blank">clinic timetable</a>. While this is empty, the website tells patients to call extension {{ setting('ext_clinics') }}.</p>
    @if ($sessions->isEmpty())
        <div class="a-card p-8 text-center"><p class="font-bold">No clinic sessions yet</p><p class="mt-1 text-ink-2">Add each regular clinic (for example “Cardiology, Tuesday 9:00–12:00”).</p></div>
    @else
        <div class="grid gap-5 lg:grid-cols-2">
            @foreach ($sessions as $day => $list)
                <section class="a-card overflow-hidden">
                    <h2 class="border-b border-line bg-surface-2 px-5 py-3 font-bold">{{ \App\Models\ClinicSession::WEEKDAYS[$day] }}</h2>
                    <ul class="divide-y divide-line">
                        @foreach ($list as $s)
                            <li class="flex items-center gap-3 px-5 py-3">
                                <span class="num w-36 font-semibold">{{ $s->timeRange() }}</span>
                                <span class="min-w-0 flex-1">{{ department($s->department)['name'] ?? $s->department }}<span class="block text-sm text-ink-3">{{ $s->who() ?? '—' }}{{ $s->note ? ' · '.$s->note : '' }}</span></span>
                                @unless ($s->is_active)<x-chip tone="muted" icon="x">Hidden</x-chip>@endunless
                                <a href="{{ route('admin.clinics.edit', $s) }}" class="a-btn a-btn-ghost">Edit</a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif
</x-layouts.admin>
