<x-layouts.portal-auth title="Activate your results portal" lead="Welcome, {{ $patient->first_name }}. Choose a password to finish setting up your account.">
    <form method="POST" action="{{ $action }}" class="space-y-5" novalidate>
        @csrf
        <p class="rounded-lg bg-surface-2 px-4 py-3 text-[0.9375rem]">Account: <strong class="break-all">{{ $patient->email }}</strong></p>
        @if ($patient->date_of_birth)
            <x-field name="date_of_birth" type="date" label="Your date of birth" autocomplete="bday" hint="To confirm it is you." />
        @endif
        <x-field name="password" type="password" label="Choose a password" autocomplete="new-password" hint="At least 10 characters." />
        <x-field name="password_confirmation" type="password" label="Type it again" autocomplete="new-password" />
        <button class="btn btn-primary w-full"><x-icon name="key" class="h-4 w-4" />Activate my portal</button>
    </form>
</x-layouts.portal-auth>
