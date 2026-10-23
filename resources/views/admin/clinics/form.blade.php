<x-layouts.admin :title="$clinic->exists ? 'Edit clinic session' : 'Add clinic session'">
    <form method="POST" action="{{ $clinic->exists ? route('admin.clinics.update', $clinic) : route('admin.clinics.store') }}" class="a-card max-w-2xl space-y-4 p-5">
        @csrf @if ($clinic->exists) @method('PUT') @endif
        <div><label class="a-label" for="department">Clinic / specialty</label>
            <select id="department" name="department" class="a-input w-full" required>@foreach (department_options(['clinic', 'inpatient', 'always']) as $k => $n)<option value="{{ $k }}" @selected(old('department', $clinic->department) === $k)>{{ $n }}</option>@endforeach</select></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="a-label" for="doctor_id">Doctor</label>
                <select id="doctor_id" name="doctor_id" class="a-input w-full"><option value="">— None —</option>@foreach ($doctors as $d)<option value="{{ $d->id }}" @selected((string) old('doctor_id', $clinic->doctor_id) === (string) $d->id)>{{ $d->name }}</option>@endforeach</select></div>
            <div><label class="a-label" for="label">…or a clinic name</label><input id="label" name="label" class="a-input w-full" value="{{ old('label', $clinic->label) }}" placeholder="e.g. Antenatal clinic"></div>
        </div>
        <div class="grid gap-4 sm:grid-cols-3">
            <div><label class="a-label" for="weekday">Day</label><select id="weekday" name="weekday" class="a-input w-full">@foreach (\App\Models\ClinicSession::WEEKDAYS as $n => $day)<option value="{{ $n }}" @selected((int) old('weekday', $clinic->weekday) === $n)>{{ $day }}</option>@endforeach</select></div>
            <div><label class="a-label" for="starts_at">Starts</label><input id="starts_at" name="starts_at" type="time" class="a-input w-full" value="{{ old('starts_at', substr((string) $clinic->starts_at, 0, 5)) }}" required></div>
            <div><label class="a-label" for="ends_at">Ends</label><input id="ends_at" name="ends_at" type="time" class="a-input w-full" value="{{ old('ends_at', substr((string) $clinic->ends_at, 0, 5)) }}" required></div>
        </div>
        <div><label class="a-label" for="note">Note <span class="font-normal text-ink-3">(optional)</span></label><input id="note" name="note" class="a-input w-full" value="{{ old('note', $clinic->note) }}" placeholder="e.g. By appointment only"></div>
        <label class="flex min-h-11 items-center gap-3"><input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-[var(--primary)]" @checked(old('is_active', $clinic->is_active))>Show on the website</label>
        <div class="flex gap-2"><button class="a-btn a-btn-primary">Save</button><a href="{{ route('admin.clinics.index') }}" class="a-btn">Cancel</a></div>
    </form>
    @if ($clinic->exists)
        <form method="POST" action="{{ route('admin.clinics.destroy', $clinic) }}" class="mt-8" onsubmit="return confirm('Remove this clinic session?')">@csrf @method('DELETE')<button class="a-btn text-[color:var(--critical-text)] hover:bg-surface-2">Remove session</button></form>
    @endif
</x-layouts.admin>
