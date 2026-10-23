@use('App\Enums\AppointmentStatus')
@php
    $suggest = $a->scheduled_for ?? $a->preferred_date->copy()->setTime(match ($a->preferred_window) { 'afternoon' => 13, 'evening' => 16, default => 9 }, 0);
@endphp
<x-layouts.admin :title="$a->full_name">
    <x-slot:actions><a href="{{ route('admin.appointments.index', ['status' => 'requested']) }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Waiting list</a></x-slot:actions>

    <div class="grid gap-5 xl:grid-cols-[1fr_26rem]">
        <section class="a-card p-5">
            <div class="flex flex-wrap items-center gap-3">
                <x-status :status="$a->status" />
                <span class="num text-ink-3">{{ $a->reference }}</span>
                <span class="text-ink-3">· received {{ $a->created_at->format('D j M, g:i a') }}</span>
            </div>
            <dl class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                <div><dt class="text-sm text-ink-3">Service</dt><dd class="font-semibold">{{ $a->serviceLabel() }}</dd></div>
                @if ($a->doctor)<div><dt class="text-sm text-ink-3">Doctor asked for</dt><dd class="font-semibold">{{ $a->doctor->name }}</dd></div>@endif
                <div><dt class="text-sm text-ink-3">Preferred</dt><dd class="font-semibold">{{ $a->preferred_date->format('l j F') }} · {{ $a->windowLabel() }}</dd></div>
                @if ($a->alternate_date)<div><dt class="text-sm text-ink-3">Also possible</dt><dd class="font-semibold">{{ $a->alternate_date->format('l j F') }}</dd></div>@endif
                @if ($a->scheduled_for)<div><dt class="text-sm text-ink-3">{{ $a->status === AppointmentStatus::Rescheduled ? 'Offered' : 'Booked for' }}</dt><dd class="font-semibold">{{ $a->scheduled_for->format('l j F, g:i a') }}</dd></div>@endif
                <div><dt class="text-sm text-ink-3">Phone</dt><dd><a class="font-semibold text-link" href="{{ tel_href($a->phone) }}">{{ format_phone($a->phone, true) }}</a></dd></div>
                <div><dt class="text-sm text-ink-3">Email</dt><dd class="font-semibold break-all">{{ $a->email ?: 'None given: confirm by phone' }}</dd></div>
                <div><dt class="text-sm text-ink-3">Date of birth</dt><dd>{{ $a->date_of_birth?->format('j M Y') ?? '—' }}</dd></div>
                <div><dt class="text-sm text-ink-3">Patient</dt><dd>{{ $a->returning_patient ? 'Returning' : 'New to Woodlands' }}{{ $a->has_referral ? ' · has a referral' : '' }}</dd></div>
            </dl>
            @if ($a->reason)<div class="mt-5 rounded-lg bg-surface-2 p-4"><p class="text-sm text-ink-3">Patient’s note</p><p class="mt-1 whitespace-pre-line">{{ $a->reason }}</p></div>@endif
            @if ($a->message_to_patient)<p class="mt-4 text-sm text-ink-2"><strong>Last message sent:</strong> {{ $a->message_to_patient }}</p>@endif
            @if ($a->handler)<p class="mt-4 text-sm text-ink-3">Handled by {{ $a->handler->name }} {{ $a->status_changed_at?->diffForHumans() }}</p>@endif

            <form method="POST" action="{{ route('admin.appointments.update', $a) }}" class="mt-6 border-t border-line pt-5">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="notes">
                <label for="staff_notes" class="a-label">Staff notes <span class="font-normal text-ink-3">(never shown to the patient)</span></label>
                <textarea id="staff_notes" name="staff_notes" rows="3" class="a-input w-full">{{ old('staff_notes', $a->staff_notes) }}</textarea>
                <button class="a-btn a-btn-ghost mt-2">Save notes</button>
            </form>

            @if ($history->isNotEmpty())
                <div class="mt-6 border-t border-line pt-5">
                    <h2 class="font-bold">Earlier requests from this phone or email</h2>
                    <ul class="mt-2 space-y-1 text-sm">@foreach ($history as $h)<li><a class="text-link" href="{{ route('admin.appointments.show', $h) }}">{{ $h->reference }}</a> · {{ $h->serviceLabel() }} · {{ $h->created_at->format('j M Y') }} · {{ $h->status->label() }}</li>@endforeach</ul>
                </div>
            @endif
        </section>

        <section class="a-card p-5" aria-labelledby="decide" x-data="{ action: '{{ $a->status === AppointmentStatus::Requested ? 'confirm' : 'reschedule' }}' }">
            <h2 id="decide" class="font-bold">{{ $a->status === AppointmentStatus::Requested ? 'Confirm this request' : 'Change this appointment' }}</h2>
            <form method="POST" action="{{ route('admin.appointments.update', $a) }}" class="mt-4 space-y-4">
                @csrf @method('PATCH')
                <fieldset class="grid gap-2">
                    <legend class="sr-only">Decision</legend>
                    <label class="flex min-h-11 items-center gap-3 rounded-lg border border-line px-3"><input type="radio" name="action" value="confirm" x-model="action" class="h-4 w-4 accent-[var(--primary)]" @checked($a->status === AppointmentStatus::Requested)><span><strong>Confirm</strong> at the time below</span></label>
                    <label class="flex min-h-11 items-center gap-3 rounded-lg border border-line px-3"><input type="radio" name="action" value="reschedule" x-model="action" class="h-4 w-4 accent-[var(--primary)]" @checked($a->status !== AppointmentStatus::Requested)><span><strong>Offer a different time</strong></span></label>
                    <label class="flex min-h-11 items-center gap-3 rounded-lg border border-line px-3"><input type="radio" name="action" value="cancel" x-model="action" class="h-4 w-4 accent-[var(--primary)]"><span><strong>Can’t book</strong> (cancel)</span></label>
                </fieldset>
                <div class="grid grid-cols-2 gap-3" x-show="action !== 'cancel'">
                    <div><label for="date" class="a-label">Date</label><input id="date" name="date" type="date" class="a-input w-full" value="{{ old('date', $suggest->toDateString()) }}" min="{{ today()->toDateString() }}"></div>
                    <div><label for="time" class="a-label">Time</label><input id="time" name="time" type="time" step="300" class="a-input w-full" value="{{ old('time', $suggest->format('H:i')) }}"></div>
                </div>
                <div>
                    <label for="message_to_patient" class="a-label">Message to the patient <span class="font-normal text-ink-3">(optional, included in the email)</span></label>
                    <textarea id="message_to_patient" name="message_to_patient" rows="3" class="a-input w-full" placeholder="e.g. Please fast for 8 hours before your blood test.">{{ old('message_to_patient') }}</textarea>
                </div>
                @if ($a->email)
                    <label class="flex min-h-11 items-center gap-3"><input type="hidden" name="notify" value="0"><input type="checkbox" name="notify" value="1" checked class="h-4 w-4 accent-[var(--primary)]">Email {{ $a->first_name }} at {{ $a->email }}</label>
                @else
                    <p class="rounded-lg bg-surface-2 p-3 text-sm"><strong>No email on file.</strong> Phone {{ format_phone($a->phone) }} to tell them the time.</p>
                @endif
                <button class="a-btn a-btn-primary w-full">Save and go to next request<x-icon name="arrow-right" class="h-4 w-4" /></button>
            </form>
        </section>
    </div>
</x-layouts.admin>
