<x-layouts.portal-auth title="Sign in to your results" lead="View and download your laboratory and radiology reports.">
    <form method="POST" action="{{ route('portal.login') }}" class="space-y-5" novalidate>
        @csrf
        <x-field name="email" type="email" label="Email" autocomplete="username" autofocus />
        <x-field name="password" type="password" label="Password" autocomplete="current-password" />
        <button class="btn btn-primary w-full"><x-icon name="lock" class="h-4 w-4" />Sign in</button>
        <p class="text-center"><a class="text-link" href="{{ route('portal.password.request') }}">Forgot your password, or never received your invitation?</a></p>
    </form>
</x-layouts.portal-auth>
