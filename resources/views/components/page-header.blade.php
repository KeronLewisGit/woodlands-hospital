@props(['title', 'lead' => null, 'sign' => null, 'code' => null, 'photo' => null, 'photoAlt' => '', 'tone' => null])
{{--
    The one header every inner page uses: a forest band with a ward-sign label, the title in
    Newsreader and an optional photo "window" on the right (below the text on phones).
    The canopy line runs along its bottom edge. Extra content (actions, chips) goes in the slot.
--}}
<section {{ $attributes->merge(['class' => 'on-dark relative isolate overflow-hidden bg-forest-900 text-white']) }}>
    <div class="wrap grid items-center gap-8 pb-10 pt-10 md:pt-14 {{ $photo ? 'lg:grid-cols-[1.25fr_1fr] lg:gap-14 lg:pb-14' : 'md:pb-14' }}">
        <div>
            @if ($sign)<x-sign :code="$code" :tone="$tone">{{ $sign }}</x-sign>@endif
            <h1 class="display-2 mt-4 max-w-3xl">{{ $title }}</h1>
            @if ($lead)<p class="lead mt-4 text-white/85">{{ $lead }}</p>@endif
            {{ $slot }}
        </div>
        @if ($photo)
            <x-photo :key="$photo" :alt="$photoAlt" :eager="true" sizes="(min-width: 1024px) 40vw, 100vw" class="aspect-[16/10] rounded-xl shadow-md ring-1 ring-white/10 lg:aspect-[4/3]" />
        @endif
    </div>
    <div class="canopy" aria-hidden="true"></div>
</section>
