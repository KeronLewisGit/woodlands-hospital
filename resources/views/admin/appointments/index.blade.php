@use('App\Enums\AppointmentStatus')
@use('App\Enums\AppointmentService')
<x-layouts.admin title="Appointment requests">
    @include('admin._notify-warning')
    <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
        <a href="{{ route('admin.appointments.index', array_filter(['service' => $filters['service'], 'q' => $filters['q']])) }}" class="a-btn {{ $filters['status'] ? 'a-btn-ghost' : 'a-btn-primary' }}">All <span class="num opacity-80">{{ $counts->sum() }}</span></a>
        @foreach (AppointmentStatus::cases() as $s)
            <a href="{{ route('admin.appointments.index', array_filter(['status' => $s->value, 'service' => $filters['service'], 'q' => $filters['q']])) }}" class="a-btn {{ $filters['status'] === $s->value ? 'a-btn-primary' : 'a-btn-ghost' }}" @if($filters['status'] === $s->value) aria-current="true" @endif>{{ $s->label() }} <span class="num opacity-80">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>
    <form method="GET" class="mt-4 flex flex-wrap gap-2">
        @if ($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <label class="sr-only" for="service">Service</label>
        <select id="service" name="service" class="a-input" onchange="this.form.submit()">
            <option value="">Every service</option>
            @foreach (AppointmentService::cases() as $s)<option value="{{ $s->value }}" @selected($filters['service'] === $s->value)>{{ $s->label() }}</option>@endforeach
        </select>
        @if ($filters['q'])<span class="chip self-center"><x-icon name="search" />“{{ $filters['q'] }}” <a href="{{ route('admin.appointments.index', array_filter(['status' => $filters['status'], 'service' => $filters['service']])) }}" class="ml-1 underline">clear</a></span>@endif
        <noscript><button class="a-btn a-btn-ghost">Filter</button></noscript>
    </form>

    <section class="a-card mt-5 overflow-hidden">
        @if ($appointments->isEmpty())
            <p class="p-8 text-center text-ink-2">No requests match these filters.</p>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Patient</th><th>Service</th><th>Wants</th><th>Status</th><th class="hidden sm:table-cell">Received</th></tr></thead>
                    <tbody>
                        @foreach ($appointments as $a)
                            @php($soon = $a->status === AppointmentStatus::Requested && $a->preferred_date->lte(today()->addDay()))
                            <tr>
                                <td class="min-w-44"><a href="{{ route('admin.appointments.show', $a) }}" class="font-bold hover:text-link">{{ $a->full_name }}</a><span class="block whitespace-nowrap text-sm text-ink-3"><span class="num">{{ $a->reference }}</span> · {{ format_phone($a->phone) }}</span></td>
                                <td>{{ $a->serviceLabel() }}</td>
                                <td class="whitespace-nowrap">@if($a->scheduled_for && $a->status !== AppointmentStatus::Cancelled)<span class="font-semibold">{{ $a->scheduled_for->format('D j M, g:i a') }}</span>@else<x-chip :tone="$soon ? 'attention' : 'neutral'" icon="calendar">{{ $a->preferred_date->isToday() ? 'Today' : ($a->preferred_date->isTomorrow() ? 'Tomorrow' : $a->preferred_date->format('D j M')) }} · {{ ucfirst($a->preferred_window) }}</x-chip>@endif</td>
                                <td><x-status :status="$a->status" /></td>
                                <td class="hidden whitespace-nowrap text-ink-2 sm:table-cell">{{ $a->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    <div class="mt-5">{{ $appointments->links() }}</div>
</x-layouts.admin>
