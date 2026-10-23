<x-layouts.admin :title="$doctor->exists ? 'Edit doctor' : 'Add a doctor'">
    <form method="POST" action="{{ $doctor->exists ? route('admin.doctors.update', $doctor) : route('admin.doctors.store') }}" class="a-card max-w-2xl space-y-4 p-5">
        @csrf @if ($doctor->exists) @method('PUT') @endif
        <div><label class="a-label" for="name">Name, as it should appear</label><input id="name" name="name" class="a-input w-full" value="{{ old('name', $doctor->name) }}" required placeholder="Dr Firstname Lastname"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="a-label" for="department">Department</label><select id="department" name="department" class="a-input w-full" required>@foreach (department_options() as $k => $n)<option value="{{ $k }}" @selected(old('department', $doctor->department) === $k)>{{ $n }}</option>@endforeach</select></div>
            <div><label class="a-label" for="title">Title <span class="font-normal text-ink-3">(optional)</span></label><input id="title" name="title" class="a-input w-full" value="{{ old('title', $doctor->title) }}" placeholder="e.g. Consultant Cardiologist"></div>
        </div>
        <div><label class="a-label" for="qualifications">Qualifications <span class="font-normal text-ink-3">(optional)</span></label><input id="qualifications" name="qualifications" class="a-input w-full" value="{{ old('qualifications', $doctor->qualifications) }}"></div>
        <div><label class="a-label" for="bio">Short bio <span class="font-normal text-ink-3">(optional)</span></label><textarea id="bio" name="bio" rows="4" class="a-input w-full">{{ old('bio', $doctor->bio) }}</textarea></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="a-label" for="sort">Order</label><input id="sort" name="sort" type="number" min="0" max="999" class="a-input w-full" value="{{ old('sort', $doctor->sort) }}"><p class="mt-1 text-xs text-ink-3">Lower numbers show first.</p></div>
            <label class="flex min-h-11 items-center gap-3 self-center"><input type="checkbox" name="is_published" value="1" class="h-4 w-4 accent-[var(--primary)]" @checked(old('is_published', $doctor->is_published))>Show on the website</label>
        </div>
        <div class="flex gap-2"><button class="a-btn a-btn-primary">Save</button><a href="{{ route('admin.doctors.index') }}" class="a-btn">Cancel</a></div>
    </form>
    @if ($doctor->exists)
        <form method="POST" action="{{ route('admin.doctors.destroy', $doctor) }}" class="mt-8" onsubmit="return confirm('Remove this doctor from the directory?')">@csrf @method('DELETE')<button class="a-btn text-[color:var(--critical-text)] hover:bg-surface-2">Remove doctor</button></form>
    @endif
</x-layouts.admin>
