@use('App\Enums\ApplicationStatus')
<x-layouts.admin title="Job applications">
    <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
        <a href="{{ route('admin.applications.index', array_filter(['job' => $filters['job'], 'q' => $filters['q']])) }}" class="a-btn {{ $filters['status'] ? 'a-btn-ghost' : 'a-btn-primary' }}">All <span class="num opacity-80">{{ $counts->sum() }}</span></a>
        @foreach (ApplicationStatus::cases() as $s)
            <a href="{{ route('admin.applications.index', array_filter(['status' => $s->value, 'job' => $filters['job'], 'q' => $filters['q']])) }}" class="a-btn {{ $filters['status'] === $s->value ? 'a-btn-primary' : 'a-btn-ghost' }}">{{ $s->label() }} <span class="num opacity-80">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>
    <form method="GET" class="mt-4 flex flex-wrap gap-2">
        @if ($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <label class="sr-only" for="job">Job</label>
        <select id="job" name="job" class="a-input min-w-0 max-w-full"><option value="">Every job</option>@foreach ($jobs as $j)<option value="{{ $j->id }}" @selected($filters['job'] === (string) $j->id)>{{ $j->title }}</option>@endforeach</select>
        <label class="sr-only" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] }}" class="a-input" placeholder="Name, email, reference">
        <button class="a-btn a-btn-ghost">Filter</button>
    </form>
    <section class="a-card mt-5 overflow-x-auto">
        @if ($applications->isEmpty())
            <p class="p-8 text-center text-ink-2">No applications match.</p>
        @else
            <table class="data-table">
                <thead><tr><th>Applicant</th><th>Role</th><th>Experience</th><th>Status</th><th>Received</th></tr></thead>
                <tbody>
                    @foreach ($applications as $a)
                        <tr>
                            <td><a href="{{ route('admin.applications.show', $a) }}" class="font-bold hover:text-link">{{ $a->full_name }}</a><span class="block text-sm text-ink-3"><span class="num">{{ $a->reference }}</span> · {{ $a->email }}</span></td>
                            <td>{{ $a->jobPosting->title ?? $a->category->label().' (general)' }}</td>
                            <td class="num">{{ $a->years_experience !== null ? $a->years_experience.' yrs' : '—' }}</td>
                            <td><x-status :status="$a->status" /></td>
                            <td class="whitespace-nowrap text-ink-2">{{ $a->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
    <div class="mt-5">{{ $applications->links() }}</div>
</x-layouts.admin>
