@php $editing = $job->exists; @endphp
<x-layouts.admin :title="$editing ? 'Edit job' : 'New job'">
    <form method="POST" action="{{ $editing ? route('admin.jobs.update', $job) : route('admin.jobs.store') }}" class="max-w-4xl space-y-5">
        @csrf @if ($editing) @method('PUT') @endif
        <section class="a-card grid gap-4 p-5 md:grid-cols-3">
            <div class="md:col-span-3"><label class="a-label" for="title">Job title</label><input id="title" name="title" class="a-input w-full" value="{{ old('title', $job->title) }}" required maxlength="120"></div>
            <div><label class="a-label" for="status">Status</label><select id="status" name="status" class="a-input w-full">@foreach (\App\Enums\JobStatus::cases() as $s)<option value="{{ $s->value }}" @selected(old('status', $job->status?->value) === $s->value)>{{ $s->label() }}</option>@endforeach</select><p class="mt-1 text-xs text-ink-3">Only open jobs show on the website.</p></div>
            <div><label class="a-label" for="category">Area</label><select id="category" name="category" class="a-input w-full">@foreach (\App\Enums\JobCategory::cases() as $c)<option value="{{ $c->value }}" @selected(old('category', $job->category?->value) === $c->value)>{{ $c->label() }}</option>@endforeach</select></div>
            <div><label class="a-label" for="employment_type">Type</label><select id="employment_type" name="employment_type" class="a-input w-full">@foreach (\App\Enums\EmploymentType::cases() as $t)<option value="{{ $t->value }}" @selected(old('employment_type', $job->employment_type?->value) === $t->value)>{{ $t->label() }}</option>@endforeach</select></div>
            <div><label class="a-label" for="location">Location</label><input id="location" name="location" class="a-input w-full" value="{{ old('location', $job->location) }}" required></div>
            <div><label class="a-label" for="openings">Openings</label><input id="openings" name="openings" type="number" min="1" class="a-input num w-full" value="{{ old('openings', $job->openings) }}" required></div>
            <div><label class="a-label" for="ends_on">Closing date</label><input id="ends_on" name="ends_on" type="date" class="a-input w-full" value="{{ old('ends_on', $job->ends_on?->toDateString()) }}"></div>
        </section>
        <section class="a-card space-y-4 p-5">
            <div><label class="a-label" for="summary">Summary</label><textarea id="summary" name="summary" rows="3" class="a-input w-full" required maxlength="600">{{ old('summary', $job->summary) }}</textarea></div>
            <div><label class="a-label" for="duties">Duties <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="duties" name="duties" rows="5" class="a-input w-full">{{ old('duties', $job->duties) }}</textarea></div>
            <div><label class="a-label" for="requirements">Requirements <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="requirements" name="requirements" rows="5" class="a-input w-full">{{ old('requirements', $job->requirements) }}</textarea></div>
        </section>
        <div class="flex gap-2"><button class="a-btn a-btn-primary px-6">{{ $editing ? 'Save job' : 'Create job' }}</button><a href="{{ route('admin.jobs.index') }}" class="a-btn">Cancel</a></div>
    </form>
    @if ($editing)
        <form method="POST" action="{{ route('admin.jobs.destroy', $job) }}" class="mt-12 border-t border-line pt-6" onsubmit="return confirm('Delete this job? If people applied, it will be closed instead.')">@csrf @method('DELETE')<button class="a-btn text-[color:var(--critical-text)] hover:bg-surface-2">Delete job</button></form>
    @endif
</x-layouts.admin>
