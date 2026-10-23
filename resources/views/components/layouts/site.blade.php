@props(['title' => null, 'description' => null, 'jsonLd' => [], 'mobileBar' => true, 'ogImage' => 'ct-suite'])
@php
    $short = woodlands('short_name');
    $pageTitle = $title ? $title.' · '.$short : $short.' · Private hospital in Georgetown, Guyana';
    $metaDescription = $description ?? 'Woodlands Hospital, 110–111 Carmichael Street, Georgetown: 24-hour Emergency Room, laboratory, radiology and pharmacy, specialist clinics, and a secure online results portal. Since 1969.';
    $main = setting('phone_main');
    $ambulance = setting('ambulance_phone');
    $nav = [
        ['route' => 'services', 'match' => 'services', 'label' => 'Services'],
        ['route' => 'clinics', 'match' => 'clinics', 'label' => 'Clinics'],
        ['route' => 'doctors', 'match' => 'doctors', 'label' => 'Doctors'],
        ['route' => 'patients', 'match' => 'patients', 'label' => 'Patients & visitors'],
        ['route' => 'jobs.index', 'match' => ['jobs.*', 'apply.*'], 'label' => 'Careers'],
        ['route' => 'contact', 'match' => 'contact', 'label' => 'Contact'],
    ];
    $address = woodlands('address');
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'Hospital',
        'name' => woodlands('name'),
        'alternateName' => $short,
        'url' => route('home'),
        'telephone' => '+'.gy_digits($main),
        'email' => setting('email_care'),
        'foundingDate' => (string) woodlands('founded'),
        'logo' => asset('favicon.svg'),
        'image' => asset('img/ct-suite-1920.webp'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $address['street'],
            'addressLocality' => $address['city'],
            'addressRegion' => $address['area'],
            'addressCountry' => $address['country_code'],
        ],
        'geo' => ['@type' => 'GeoCoordinates', 'latitude' => woodlands('geo.lat'), 'longitude' => woodlands('geo.lng')],
        'openingHours' => 'Mo-Su 00:00-23:59',
        'availableService' => collect(woodlands('departments'))->map(fn ($d) => ['@type' => 'MedicalSpecialty', 'name' => $d['name']])->values()->all(),
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if (woodlands('preview'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ woodlands('name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/'.$ogImage.'-1920.webp') }}">
    <meta property="og:locale" content="en_GY">
    <meta name="twitter:card" content="summary_large_image">
    @foreach (array_merge([$organization], $jsonLd) as $ld)
        <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="min-h-screen {{ $mobileBar ? 'pb-[5.25rem] lg:pb-0' : '' }}" x-data="{ menu: false }" @keydown.escape.window="menu = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-surface focus:px-4 focus:py-3 focus:shadow-md">Skip to content</a>

    @if (woodlands('preview'))
        <div class="bg-clay-soft text-sm text-ink">
            <p class="wrap flex items-start gap-2 py-2.5"><x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" /><span><strong>Concept preview.</strong> This is a proposed redesign, not the official Woodlands Hospital website. In an emergency call {{ format_phone($ambulance) }}.</span></p>
        </div>
    @endif

    {{-- Emergency rail: on every page, above everything else. --}}
    <div class="on-dark bg-forest-950 text-[0.9375rem] text-white">
        <div class="wrap flex min-h-11 flex-wrap items-center gap-x-6 gap-y-1 py-1.5">
            <p class="flex items-center gap-2.5 font-semibold"><span class="pulse-dot h-2.5 w-2.5 flex-none rounded-full bg-leaf" aria-hidden="true"></span>Emergency Room, laboratory, radiology &amp; pharmacy open 24/7</p>
            <div class="ml-auto hidden items-center gap-5 md:flex">
                <a href="{{ tel_href($main) }}" class="inline-flex min-h-11 items-center gap-2 text-white/85 hover:text-white"><x-icon name="phone" class="h-4 w-4" />Main line {{ format_phone($main) }}</a>
                <a href="{{ tel_href($ambulance) }}" class="inline-flex min-h-9 items-center gap-2 rounded-md bg-er px-3 font-bold text-white hover:bg-[var(--er-hover)]"><x-icon name="ambulance" class="h-4 w-4" />Ambulance {{ format_phone($ambulance) }}</a>
            </div>
        </div>
    </div>

    @if ($note = setting('announcement'))
        <div class="border-b border-line bg-clay-soft">
            <p class="wrap flex items-start gap-2 py-2.5 text-[0.9375rem]"><x-icon name="alert" class="mt-1 h-4 w-4 flex-none text-clay-text" />{{ $note }}</p>
        </div>
    @endif

    <header class="site-header sticky top-0 z-40 border-b border-line bg-surface/95 backdrop-blur">
        <div class="wrap flex h-[4.5rem] items-center gap-2 lg:h-20">
            <a href="{{ route('home') }}" class="mr-auto flex-none rounded" aria-label="{{ woodlands('name') }}, home"><x-logo /></a>

            <nav class="hidden items-center xl:flex" aria-label="Main">
                @foreach ($nav as $item)
                    @php($current = request()->routeIs($item['match']))
                    <a href="{{ route($item['route']) }}" @class(['relative whitespace-nowrap px-3 py-2.5 font-semibold transition-colors', 'text-ink' => $current, 'text-ink-2 hover:text-ink' => ! $current]) @if($current) aria-current="page" @endif>
                        {{ $item['label'] }}
                        @if ($current)<span class="absolute inset-x-3 bottom-0 h-[3px] rounded-full bg-clay" aria-hidden="true"></span>@endif
                    </a>
                @endforeach
            </nav>

            <a href="{{ route('portal.login') }}" class="btn btn-sm btn-outline ml-2 hidden flex-none lg:inline-flex"><x-icon name="lock" class="h-4 w-4" />Results</a>
            <a href="{{ route('appointments.create') }}" class="btn btn-sm btn-clay hidden flex-none lg:inline-flex">Request appointment</a>
            <button type="button" onclick="toggleTheme()" class="hidden h-11 w-11 flex-none items-center justify-center rounded-lg text-ink-2 hover:bg-surface-2 sm:inline-flex" aria-label="Switch between light and dark theme">
                <x-icon name="moon" class="dark-hide h-5 w-5" /><x-icon name="sun" class="dark-show hidden h-5 w-5" />
            </button>
            <button type="button" class="inline-flex h-11 min-w-11 flex-none items-center justify-center gap-2 rounded-lg px-3 font-semibold ring-1 ring-line-strong xl:hidden" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu">
                <x-icon name="menu" x-show="!menu" /><x-icon name="x" x-show="menu" x-cloak /><span class="hidden sm:inline">Menu</span><span class="sr-only sm:hidden">Menu</span>
            </button>
        </div>
        <div id="mobile-menu" class="border-t border-line bg-surface xl:hidden" x-show="menu" x-cloak x-transition.opacity>
            <nav class="wrap grid gap-x-8 py-3 sm:grid-cols-2" aria-label="Mobile">
                <a href="{{ route('home') }}" class="font-display rounded py-3 text-2xl">Home</a>
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="font-display rounded py-3 text-2xl" @if(request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('emergency') }}" class="font-display rounded py-3 text-2xl text-er-text">Emergency &amp; ambulance</a>
                <a href="{{ route('pharmacy') }}" class="font-display rounded py-3 text-2xl">Pharmacy refills</a>
                <div class="mt-3 grid gap-3 border-t border-line pt-5 sm:col-span-2 sm:grid-cols-2">
                    <a href="{{ route('appointments.create') }}" class="btn btn-clay">Request an appointment</a>
                    <a href="{{ route('portal.login') }}" class="btn btn-outline"><x-icon name="lock" class="h-4 w-4" />Results portal</a>
                </div>
                <button type="button" onclick="toggleTheme()" class="mt-3 flex min-h-11 items-center gap-2 rounded text-left font-semibold text-ink-2"><x-icon name="moon" class="h-5 w-5" />Switch light / dark theme</button>
            </nav>
        </div>
    </header>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="on-dark mt-24 bg-forest-950 text-white/80">
        <div class="canopy" aria-hidden="true"></div>
        <div class="wrap grid gap-12 py-14 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.2fr]">
            <div>
                <x-logo tone="light" />
                <p class="mt-5 max-w-xs text-white/75">Private acute-care hospital in the heart of Georgetown, open around the clock since 1969.</p>
                <a href="{{ tel_href($ambulance) }}" class="btn btn-er mt-6"><x-icon name="ambulance" class="h-5 w-5" />Ambulance {{ format_phone($ambulance) }}</a>
            </div>
            <div>
                <h2 class="sign text-white/80">Patients</h2>
                <ul class="mt-4 space-y-1">
                    @foreach ([['appointments.create', 'Request an appointment'], ['portal.login', 'Results portal'], ['pharmacy', 'Pharmacy refills'], ['clinics', 'Clinic timetable'], ['patients', 'Patients & visitors'], ['emergency', 'Emergency & ambulance']] as [$r, $l])
                        <li><a class="inline-flex min-h-10 items-center hover:text-white" href="{{ route($r) }}">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="sign text-white/80">Hospital</h2>
                <ul class="mt-4 space-y-1">
                    @foreach ([['services', 'Services'], ['doctors', 'Find a doctor'], ['about', 'About Woodlands'], ['jobs.index', 'Careers'], ['contact', 'Contact']] as [$r, $l])
                        <li><a class="inline-flex min-h-10 items-center hover:text-white" href="{{ route($r) }}">{{ $l }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="sign text-white/80">Find us</h2>
                <address class="mt-4 not-italic leading-relaxed">{{ $address['street'] }}<br>{{ $address['area'] }}, {{ $address['city'] }}<br>{{ $address['country'] }}</address>
                <a class="mt-1 inline-flex min-h-11 items-center gap-1.5 hover:text-white" href="{{ woodlands('map_url') }}" rel="noopener" target="_blank">Open in map<x-icon name="external" class="h-3.5 w-3.5" /></a>
                <p class="mt-3"><a class="inline-flex min-h-10 items-center gap-2 hover:text-white" href="{{ tel_href($main) }}"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($main, true) }}</a></p>
                <p><a class="inline-flex min-h-10 items-center gap-2 break-all hover:text-white" href="mailto:{{ setting('email_care') }}"><x-icon name="mail" class="h-4 w-4 flex-none" />{{ setting('email_care') }}</a></p>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="wrap flex flex-col gap-3 py-6 text-sm text-white/70 md:flex-row md:items-center md:justify-between">
                <p>© {{ date('Y') }} {{ woodlands('name') }}. Information on this site does not replace advice from your doctor.</p>
                <p class="flex flex-wrap gap-x-5 gap-y-1">
                    <a class="inline-flex min-h-10 items-center hover:text-white" href="{{ route('privacy') }}">Privacy</a>
                    <a class="inline-flex min-h-10 items-center hover:text-white" href="{{ route('credits') }}">Photo credits</a>
                    <a class="inline-flex min-h-10 items-center hover:text-white" href="{{ route('login') }}">Staff sign-in</a>
                </p>
            </div>
        </div>
    </footer>

    @if ($mobileBar)
        {{-- Thumb-reach actions on phones. The ambulance call is always one tap away. --}}
        <nav aria-label="Quick actions" class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur lg:hidden">
            <div class="mx-auto flex max-w-xl gap-2">
                <a href="{{ tel_href($ambulance) }}" class="btn btn-er flex-1 px-2"><x-icon name="ambulance" class="h-5 w-5" /><span>Ambulance</span></a>
                <a href="{{ tel_href($main) }}" class="btn btn-outline flex-none px-3.5" aria-label="Call the hospital, {{ format_phone($main) }}"><x-icon name="phone" /></a>
                <a href="{{ route('appointments.create') }}" class="btn btn-clay flex-1 px-2">Book</a>
            </div>
        </nav>
    @endif
</body>
</html>
