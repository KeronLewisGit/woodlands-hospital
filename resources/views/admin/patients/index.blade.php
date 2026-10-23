<x-layouts.admin title="Patients & results">
    <x-slot:actions><a href="{{ route('admin.patients.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />New portal record</a></x-slot:actions>
    <p class="mb-5 max-w-3xl text-ink-2">Patients with a results-portal account. Create a record with the patient’s email; they receive a one-time link to set a password. {{ auth()->user()->hasRole(\App\Enums\Role::Lab) ? 'Open a record to publish a PDF report.' : 'Laboratory and radiology staff publish the reports.' }}</p>
    <form method="GET" class="mb-5 flex max-w-xl gap-2" role="search">
        <label for="q" class="sr-only">Search</label>
        <input id="q" name="q" value="{{ $q }}" class="a-input flex-1" placeholder="Name, email or hospital number">
        <button class="a-btn a-btn-ghost"><x-icon name="search" class="h-4 w-4" />Search</button>
    </form>
    <section class="a-card overflow-x-auto">
        @if ($patients->isEmpty())
            <p class="p-8 text-center text-ink-2">{{ $q ? 'No patients match “'.$q.'”.' : 'No portal records yet.' }}</p>
        @else
            <table class="data-table">
                <thead><tr><th>Patient</th><th>Hospital no.</th><th>Portal</th><th class="r">Reports</th><th>Last sign-in</th></tr></thead>
                <tbody>
                    @foreach ($patients as $p)
                        <tr>
                            <td><a href="{{ route('admin.patients.show', $p) }}" class="font-bold hover:text-link">{{ $p->last_name }}, {{ $p->first_name }}</a><span class="block text-sm text-ink-3">{{ $p->email }}</span></td>
                            <td class="num">{{ $p->hospital_number ?? '—' }}</td>
                            <td>@if($p->isActivated())<x-chip tone="good" icon="check">Active</x-chip>@elseif($p->invited_at)<x-chip tone="attention" icon="mail">Invited {{ $p->invited_at->diffForHumans() }}</x-chip>@else<x-chip tone="neutral" icon="dot">Not invited</x-chip>@endif</td>
                            <td class="r">{{ $p->results_count }}</td>
                            <td class="text-ink-2">{{ $p->last_login_at?->diffForHumans() ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
    <div class="mt-5">{{ $patients->links() }}</div>
</x-layouts.admin>
