<x-layouts.portal-auth title="Choose a new password">
    <form method="POST" action="{{ route('portal.password.update') }}" class="space-y-5" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" type="email" label="Email" autocomplete="username" :value="$email" />
        <x-field name="password" type="password" label="New password" autocomplete="new-password" hint="At least 10 characters. A short sentence is easy to remember." />
        <x-field name="password_confirmation" type="password" label="Type it again" autocomplete="new-password" />
        <button class="btn btn-primary w-full">Save password</button>
    </form>
</x-layouts.portal-auth>
