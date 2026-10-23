<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>Staff sign-in · Woodlands Hospital</title>
    <meta name="robots" content="noindex">
</head>
<body class="on-dark grid min-h-screen place-items-center bg-forest-950 px-4 py-10 text-white">
    <main class="w-full max-w-md">
        <a href="{{ route('home') }}" class="inline-block rounded"><x-logo tone="light" /></a>
        <div class="mt-8 rounded-xl bg-surface p-6 text-ink shadow-md md:p-8">
            <h1 class="display-3">Staff sign-in</h1>
            <p class="mt-1 text-ink-2">Front desk, laboratory, radiology and administration.</p>
            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5" novalidate>
                @csrf
                <x-field name="email" type="email" label="Work email" autocomplete="username" autofocus />
                <x-field name="password" type="password" label="Password" autocomplete="current-password" />
                <label class="flex min-h-11 items-center gap-3"><input type="checkbox" name="remember" value="1" class="h-5 w-5 accent-[var(--primary)]">Keep me signed in on this computer</label>
                <button class="btn btn-primary w-full">Sign in</button>
            </form>
        </div>
        <p class="mt-6 text-sm text-white/75">Patients: <a class="underline" href="{{ route('portal.login') }}">sign in to the results portal</a> instead.</p>
        <div class="canopy mt-8" aria-hidden="true"></div>
    </main>
</body>
</html>
