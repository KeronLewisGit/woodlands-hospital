@props(['tone' => 'auto', 'compact' => false])
{{-- Woodlands' own mark: a rounded double-V "W" with the red cross on its right arm (as on the hospital's logo). --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 48 40" class="h-9 w-[2.7rem] flex-none" aria-hidden="true">
        <path d="M7 8 15.5 31 24 8l8.5 23L41 8" fill="none" stroke="currentColor" stroke-width="9" stroke-linecap="round" stroke-linejoin="round" class="{{ $tone === 'light' ? 'text-white' : 'text-ink' }}"/>
        <path d="M38.2 2.6h4.4v4.6h4.6v4.4h-4.6v4.6h-4.4v-4.6h-4.6V7.2h4.6z" fill="#e3192d"/>
    </svg>
    <span class="leading-none {{ $tone === 'light' ? 'text-white' : 'text-ink' }}">
        <span class="font-display block text-[1.55rem] font-semibold tracking-[-0.01em]">Woodlands</span>
        @unless ($compact)
            <span class="mt-1 block whitespace-nowrap text-[0.625rem] font-bold uppercase tracking-[0.2em] {{ $tone === 'light' ? 'text-white/75' : 'text-ink-3' }}">Hospital · Est. 1969</span>
        @endunless
    </span>
</span>
