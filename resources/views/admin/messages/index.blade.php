<x-layouts.admin title="Messages">
    <nav class="flex flex-wrap gap-2">
        <a href="{{ route('admin.messages.index') }}" class="a-btn {{ $showHandled ? 'a-btn-ghost' : 'a-btn-primary' }}">To answer <span class="num opacity-80">{{ $openCount }}</span></a>
        <a href="{{ route('admin.messages.index', ['show' => 'handled']) }}" class="a-btn {{ $showHandled ? 'a-btn-primary' : 'a-btn-ghost' }}">Handled</a>
    </nav>
    <div class="mt-5 space-y-4">
        @forelse ($messages as $m)
            <article class="a-card p-5">
                <div class="flex flex-wrap items-center gap-3"><span class="chip"><x-icon name="message" />{{ $m->topic->label() }}</span><span class="text-sm text-ink-3">{{ $m->created_at->format('D j M, g:i a') }}</span></div>
                <h2 class="mt-3 font-bold">{{ $m->name }}</h2>
                <p class="text-sm text-ink-2">@if($m->email)<a class="text-link" href="mailto:{{ $m->email }}">{{ $m->email }}</a>@endif @if($m->email && $m->phone) · @endif @if($m->phone)<a class="text-link" href="{{ tel_href($m->phone) }}">{{ format_phone($m->phone) }}</a>@endif</p>
                <p class="mt-3 max-w-3xl whitespace-pre-line">{{ $m->body }}</p>
                <form method="POST" action="{{ route('admin.messages.update', $m) }}" class="mt-4">
                    @csrf @method('PATCH')
                    <input type="hidden" name="handled" value="{{ $m->handled_at ? 0 : 1 }}">
                    <button class="a-btn {{ $m->handled_at ? 'a-btn-ghost' : 'a-btn-primary' }}"><x-icon :name="$m->handled_at ? 'arrow-left' : 'check'" class="h-4 w-4" />{{ $m->handled_at ? 'Move back to open' : 'Mark as handled' }}</button>
                </form>
            </article>
        @empty
            <p class="a-card p-8 text-center text-ink-2">{{ $showHandled ? 'No handled messages yet.' : 'No messages waiting.' }}</p>
        @endforelse
    </div>
    <div class="mt-5">{{ $messages->links() }}</div>
</x-layouts.admin>
