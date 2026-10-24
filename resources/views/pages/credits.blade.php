<x-layouts.site title="Photo credits" description="Credits and licences for the photographs on this website.">
    <x-page-header title="Photo credits" sign="Acknowledgements" code="©" lead="Photographs on this site come from Woodlands Hospital and from Wikimedia Commons contributors, used under the licences below." />
    <div class="wrap mt-12">
        <ul class="grid gap-4 md:grid-cols-2">
            @foreach (woodlands('photos') as $key => $meta)
                @php($c = config('photo-credits.'.$meta['credit']))
                @continue(! $c)
                <li class="panel flex min-w-0 gap-4 p-4">
                    <x-photo :key="$key" alt="" sizes="160px" class="h-24 w-32 flex-none rounded-lg" />
                    <div class="min-w-0 break-words text-[0.9375rem]">
                        <p class="font-bold break-words">{{ $c['title'] }}</p>
                        <p class="text-ink-2">{{ $c['author'] }} · @if($c['license_url'])<a class="text-link" href="{{ $c['license_url'] }}" rel="noopener license" target="_blank">{{ $c['license'] }}</a>@else{{ $c['license'] }}@endif</p>
                        <p><a class="text-link" href="{{ $c['source'] }}" rel="noopener" target="_blank">Source</a>@unless($meta['ours']) · <span class="text-ink-3">Illustrative, not taken at Woodlands</span>@endunless</p>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="mt-8 text-ink-3 measure">Some photos were cropped and resized. Fonts: Newsreader (Production Type) and Atkinson Hyperlegible Next (Braille Institute), both under the SIL Open Font License.</p>
    </div>
</x-layouts.site>
