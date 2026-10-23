@use('App\Enums\ApplicationStatus')
@php($a = $application)
<x-layouts.admin :title="$a->full_name">
    <x-slot:actions><a href="{{ route('admin.applications.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />All applications</a></x-slot:actions>
    <div class="grid gap-5 xl:grid-cols-[1fr_22rem]">
        <section class="a-card p-5">
            <div class="flex flex-wrap items-center gap-3"><x-status :status="$a->status" /><span class="num text-ink-3">{{ $a->reference }} · {{ $a->created_at->format('D j M Y, g:i a') }}</span></div>
            <dl class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-sm text-ink-3">Role</dt><dd class="font-semibold">{{ $a->jobPosting->title ?? 'General application' }}</dd></div>
                <div><dt class="text-sm text-ink-3">Area</dt><dd class="font-semibold">{{ $a->category->label() }}</dd></div>
                <div><dt class="text-sm text-ink-3">Phone</dt><dd><a class="font-semibold text-link" href="{{ tel_href($a->phone) }}">{{ format_phone($a->phone, true) }}</a></dd></div>
                <div><dt class="text-sm text-ink-3">Email</dt><dd><a class="font-semibold text-link break-all" href="mailto:{{ $a->email }}">{{ $a->email }}</a></dd></div>
                <div><dt class="text-sm text-ink-3">Registration</dt><dd>{{ $a->registration ?: '—' }}</dd></div>
                <div><dt class="text-sm text-ink-3">Experience</dt><dd>{{ $a->years_experience !== null ? $a->years_experience.' years' : '—' }}</dd></div>
            </dl>
            @if ($a->message)<div class="mt-5 rounded-lg bg-surface-2 p-4"><p class="text-sm text-ink-3">Message</p><p class="mt-1 whitespace-pre-line">{{ $a->message }}</p></div>@endif
            @if ($a->cv_path)<a href="{{ route('admin.applications.cv', $a) }}" class="a-btn a-btn-ghost mt-5"><x-icon name="download" class="h-4 w-4" />Download CV ({{ $a->cv_name }})</a>@endif
        </section>
        <section class="a-card p-5">
            <form method="POST" action="{{ route('admin.applications.update', $a) }}" class="space-y-4">
                @csrf @method('PATCH')
                <div><label class="a-label" for="status">Status</label><select id="status" name="status" class="a-input w-full">@foreach (ApplicationStatus::cases() as $s)<option value="{{ $s->value }}" @selected($a->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                <div><label class="a-label" for="staff_notes">Notes</label><textarea id="staff_notes" name="staff_notes" rows="5" class="a-input w-full">{{ $a->staff_notes }}</textarea></div>
                <button class="a-btn a-btn-primary w-full">Save</button>
            </form>
        </section>
    </div>
</x-layouts.admin>
