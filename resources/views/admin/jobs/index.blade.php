<x-layouts.admin title="Jobs">
    <x-slot:actions><a href="{{ route('admin.jobs.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />New job</a></x-slot:actions>
    <section class="a-card overflow-hidden">
        @if ($jobs->isEmpty())
            <div class="px-5 py-14 text-center">
                <x-icon name="briefcase" class="mx-auto h-10 w-10 text-ink-3" />
                <p class="mt-3 font-bold">No jobs yet</p>
                <p class="mt-1 text-sm text-ink-3">Post the first one and it appears on the careers page and in Google job search.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Job</th><th>Area</th><th>Type</th><th class="r">Applicants</th><th class="r">New</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($jobs as $job)
                            <tr>
                                <td><a href="{{ route('admin.jobs.edit', $job) }}" class="font-bold hover:text-link">{{ $job->title }}</a><span class="block text-sm text-ink-3">{{ $job->location }}{{ $job->ends_on ? ' · closes '.$job->ends_on->format('j M') : '' }}</span></td>
                                <td>{{ $job->category->label() }}</td>
                                <td class="whitespace-nowrap text-ink-2">{{ $job->employment_type->label() }}</td>
                                <td class="r"><a class="hover:text-link" href="{{ route('admin.applications.index', ['job' => $job->id]) }}">{{ $job->applications_count }}</a></td>
                                <td class="r">{{ $job->new_count }}</td>
                                <td><x-chip :tone="$job->status->tone()" :icon="$job->status === \App\Enums\JobStatus::Open ? 'check' : 'dot'">{{ $job->status->label() }}</x-chip></td>
                                <td class="r whitespace-nowrap">@if ($job->status === \App\Enums\JobStatus::Open)<a href="{{ route('jobs.show', $job) }}" target="_blank" rel="noopener" class="font-semibold text-link">View</a> · @endif<a href="{{ route('admin.jobs.edit', $job) }}" class="font-semibold text-link">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</x-layouts.admin>
