<x-layouts.site :title="$title" :mobile-bar="true">
    <x-page-header :title="$title" sign="Reference" code="✓" :lead="$lead" />
    <div class="wrap mt-12 grid gap-10 lg:grid-cols-[1fr_1fr]">
        <div>
            <p class="text-ink-2">Your reference number</p>
            <p class="font-display num mt-1 text-5xl tracking-tight md:text-6xl">{{ $reference }}</p>
            <p class="mt-3 text-ink-2">Please keep it. Quote it if you call us about this request.</p>
            <button type="button" onclick="window.print()" class="btn btn-outline mt-6"><x-icon name="printer" class="h-4 w-4" />Print this page</button>
        </div>
        <div class="panel p-6">
            <h2 class="display-3">What happens next</h2>
            <ol class="mt-5 space-y-4">
                @foreach ($steps as $i => $step)
                    <li class="flex gap-4"><span class="sign-code flex-none">{{ $i + 1 }}</span><span class="pt-0.5">{{ $step }}</span></li>
                @endforeach
            </ol>
            <p class="mt-6 border-t border-line pt-4 text-[0.9375rem] text-ink-2">
                @if (! empty($whatsapp))
                    Questions: WhatsApp the pharmacy on {{ format_phone(setting('pharmacy_whatsapp')) }}.
                @elseif ($extKey)
                    Questions: call {{ format_phone(setting('phone_main'), true) }}, extension {{ setting($extKey) }}.
                @else
                    Questions: email <a class="text-link" href="mailto:{{ setting('email_care') }}">{{ setting('email_care') }}</a>.
                @endif
            </p>
        </div>
    </div>
</x-layouts.site>
