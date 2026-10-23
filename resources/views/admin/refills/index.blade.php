@use('App\Enums\RefillStatus')
<x-layouts.admin title="Pharmacy refills">
    <nav class="flex flex-wrap gap-2" aria-label="Filter by status">
        <a href="{{ route('admin.refills.index') }}" class="a-btn {{ $active ? 'a-btn-ghost' : 'a-btn-primary' }}">Open</a>
        @foreach (RefillStatus::cases() as $s)
            <a href="{{ route('admin.refills.index', ['status' => $s->value]) }}" class="a-btn {{ $active === $s ? 'a-btn-primary' : 'a-btn-ghost' }}">{{ $s->label() }} <span class="num opacity-80">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>
    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        @forelse ($refills as $r)
            <article class="a-card p-5">
                <div class="flex flex-wrap items-center gap-3"><x-status :status="$r->status" /><span class="num text-sm text-ink-3">{{ $r->reference }} · {{ $r->created_at->diffForHumans() }}</span></div>
                <h2 class="mt-3 text-lg font-bold">{{ $r->name }}</h2>
                <p class="text-sm text-ink-2"><a class="text-link" href="{{ tel_href($r->phone) }}">{{ format_phone($r->phone, true) }}</a> · <a class="text-link" href="{{ whatsapp_href($r->phone) }}" rel="noopener">WhatsApp</a>@if($r->email) · {{ $r->email }}@endif @if($r->date_of_birth) · DOB {{ $r->date_of_birth->format('j M Y') }}@endif</p>
                <p class="mt-3 whitespace-pre-line rounded-lg bg-surface-2 p-3">{{ $r->medications }}</p>
                @if ($r->prescriber)<p class="mt-2 text-sm text-ink-2">Prescriber: {{ $r->prescriber }}</p>@endif
                @if ($r->notes)<p class="mt-1 text-sm text-ink-2">Note: {{ $r->notes }}</p>@endif
                <form method="POST" action="{{ route('admin.refills.update', $r) }}" class="mt-4 flex flex-wrap items-end gap-2 border-t border-line pt-4">
                    @csrf @method('PATCH')
                    <div><label class="a-label" for="status-{{ $r->id }}">Status</label>
                        <select id="status-{{ $r->id }}" name="status" class="a-input">@foreach (RefillStatus::cases() as $s)<option value="{{ $s->value }}" @selected($r->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                    <div class="min-w-48 flex-1"><label class="a-label" for="notes-{{ $r->id }}">Staff note</label><input id="notes-{{ $r->id }}" name="staff_notes" value="{{ $r->staff_notes }}" class="a-input w-full"></div>
                    <button class="a-btn a-btn-primary">Save</button>
                </form>
            </article>
        @empty
            <p class="a-card p-8 text-center text-ink-2 lg:col-span-2">No refill requests here.</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $refills->links() }}</div>
</x-layouts.admin>
