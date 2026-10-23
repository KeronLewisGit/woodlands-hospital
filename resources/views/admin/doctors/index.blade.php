<x-layouts.admin title="Doctors">
    <x-slot:actions><a href="{{ route('admin.doctors.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add a doctor</a></x-slot:actions>
    <p class="mb-5 max-w-3xl text-ink-2">The public <a class="text-link underline" href="{{ route('doctors') }}" target="_blank">doctor directory</a>. Only add doctors who have agreed to be listed, with the title and qualifications they supply.</p>
    @if ($doctors->isEmpty())
        <div class="a-card p-8 text-center"><p class="font-bold">The directory is empty</p><p class="mt-1 text-ink-2">The website shows “coming soon” and the clinic extension until you add the first doctor.</p></div>
    @else
        <section class="a-card overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Department</th><th>Clinic sessions</th><th>On website</th><th></th></tr></thead>
                <tbody>
                    @foreach ($doctors as $d)
                        <tr><td class="font-bold">{{ $d->name }}<span class="block text-sm font-normal text-ink-3">{{ $d->title }}</span></td><td>{{ $d->departmentName() }}</td><td class="num">{{ $d->sessions_count }}</td><td>@if($d->is_published)<x-chip tone="good" icon="check">Listed</x-chip>@else<x-chip tone="muted" icon="x">Hidden</x-chip>@endif</td><td class="r"><a href="{{ route('admin.doctors.edit', $d) }}" class="a-btn a-btn-ghost">Edit</a></td></tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</x-layouts.admin>
