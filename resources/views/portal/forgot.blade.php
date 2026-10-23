<x-layouts.portal-auth title="Reset your password" lead="Enter the email address the hospital has on file for you.">
    <form method="POST" action="{{ route('portal.password.email') }}" class="space-y-5" novalidate>
        @csrf
        <x-field name="email" type="email" label="Email" autocomplete="email" hint="If you have never activated your portal, we send a new invitation instead." />
        <button class="btn btn-primary w-full">Email me a link</button>
        <p class="text-center"><a class="text-link" href="{{ route('portal.login') }}">Back to sign in</a></p>
    </form>
</x-layouts.portal-auth>
