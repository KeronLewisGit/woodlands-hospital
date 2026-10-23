@props(['title', 'lead' => null])
{{-- Sign-in style screens for the results portal: same site chrome, narrow centred card. --}}
<x-layouts.site :title="$title.' · Results portal'">
    <x-page-header :title="$title" sign="Secure results portal" code="RX" tone="clay" :lead="$lead" />
    <div class="wrap mt-10 grid gap-10 lg:grid-cols-[minmax(0,28rem)_1fr]">
        <div class="panel p-6 shadow-sm md:p-8">
            @if (session('status'))
                <div class="callout mb-6" role="status"><p class="flex gap-2"><x-icon name="check-circle" class="mt-0.5 h-5 w-5 flex-none text-primary" />{{ session('status') }}</p></div>
            @endif
            {{ $slot }}
        </div>
        <aside class="space-y-4 text-ink-2 lg:pt-2">
            <p class="flex gap-3"><x-icon name="shield" class="mt-0.5 h-5 w-5 flex-none text-primary" /><span>Reports are shown only to the patient they belong to, after signing in.</span></p>
            <p class="flex gap-3"><x-icon name="key" class="mt-0.5 h-5 w-5 flex-none text-primary" /><span>No account yet? There is no public sign-up. Ask the front desk or laboratory to add your email to your record; we will email you a link to choose a password.</span></p>
            <p class="flex gap-3"><x-icon name="phone" class="mt-0.5 h-5 w-5 flex-none text-primary" /><span>Help: {{ format_phone(setting('phone_main'), true) }}, laboratory extension {{ setting('ext_lab') }}.</span></p>
        </aside>
    </div>
</x-layouts.site>
