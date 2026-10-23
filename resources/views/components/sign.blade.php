@props(['code' => null, 'tone' => null])
{{-- Ward-signage label: square code tile + uppercase label, like a hospital direction sign. --}}
<span {{ $attributes->merge(['class' => 'sign']) }}>
    @if ($code)<span @class(['sign-code', 'is-er' => $tone === 'er', 'is-clay' => $tone === 'clay']) aria-hidden="true">{{ $code }}</span>@endif
    <span>{{ $slot }}</span>
</span>
