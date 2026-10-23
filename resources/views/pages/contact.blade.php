@php($main = setting('phone_main'))
<x-layouts.site title="Contact" description="Contact Woodlands Hospital, 110–111 Carmichael Street, Georgetown. Main line {{ format_phone($main, true) }}.">
    <x-page-header title="Contact Woodlands" sign="Switchboard" code="☎" photo="georgetown-cathedral" photo-alt="St George’s Cathedral, Georgetown, a few streets from the hospital"
        lead="For an emergency, call the ambulance. For everything else, phone the switchboard or send a message and we will reply." />

    <div class="wrap mt-12 grid gap-12 lg:grid-cols-[1fr_1.15fr]">
        <div class="space-y-8">
            <div class="board board-light panel">
                <a href="{{ tel_href($main) }}" class="board-row"><span class="sign-code"><x-icon name="phone" class="h-4 w-4" /></span><span><span class="block font-bold">Main line</span><span class="block text-ink-2">{{ format_phone($main, true) }}</span></span><x-icon name="chevron-right" class="h-4 w-4 text-ink-3" /></a>
                <a href="{{ tel_href(setting('ambulance_phone')) }}" class="board-row"><span class="sign-code is-er"><x-icon name="ambulance" class="h-4 w-4" /></span><span><span class="block font-bold">Ambulance, 24 hours</span><span class="block text-ink-2">{{ format_phone(setting('ambulance_phone'), true) }}</span></span><x-icon name="chevron-right" class="h-4 w-4 text-ink-3" /></a>
                <a href="mailto:{{ setting('email_care') }}" class="board-row"><span class="sign-code"><x-icon name="mail" class="h-4 w-4" /></span><span class="min-w-0"><span class="block font-bold">Email</span><span class="block break-all text-ink-2">{{ setting('email_care') }}</span></span><x-icon name="chevron-right" class="h-4 w-4 text-ink-3" /></a>
                @if (setting('email_customer'))
                    <a href="mailto:{{ setting('email_customer') }}" class="board-row"><span class="sign-code"><x-icon name="users" class="h-4 w-4" /></span><span class="min-w-0"><span class="block font-bold">Customer care</span><span class="block break-all text-ink-2">{{ setting('email_customer') }}</span></span><x-icon name="chevron-right" class="h-4 w-4 text-ink-3" /></a>
                @endif
            </div>
            <div>
                <h2 class="display-3">Extensions</h2>
                <ul class="mt-4 divide-y divide-line rounded-xl ring-1 ring-line">
                    @foreach (extension_list() as $row)
                        <li><a href="{{ tel_href($main, $row['ext']) }}" class="flex min-h-12 items-center gap-4 px-4 hover:bg-surface-2"><span class="num w-12 font-bold text-primary">{{ $row['ext'] }}</span><span>{{ $row['label'] }}</span></a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="display-3">Address</h2>
                <address class="mt-3 not-italic text-lg">{{ woodlands('address.street') }}<br>{{ woodlands('address.area') }}, Georgetown, Guyana</address>
                <a href="{{ woodlands('map_url') }}" target="_blank" rel="noopener" class="btn btn-outline mt-4"><x-icon name="map-pin" class="h-4 w-4" />Open in OpenStreetMap</a>
            </div>
        </div>

        <section id="message" class="panel scroll-mt-32 p-6 md:p-8" aria-labelledby="msg-title">
            <h2 id="msg-title" class="display-3">Send a message</h2>
            <p class="mt-2 text-ink-2">For questions about billing, records or feedback. Please don’t include medical details: we will call you to discuss anything clinical.</p>
            @if (session('sent'))
                <div class="callout mt-6" role="status"><p class="flex items-center gap-2 font-bold"><x-icon name="check-circle" class="h-5 w-5 text-primary" />Thank you. Your message has been sent.</p><p class="mt-1 text-ink-2">We reply during office hours. For anything urgent, please call.</p></div>
            @else
                <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-5" novalidate>
                    @csrf
                    <x-form-errors />
                    <div>
                        <label for="topic" class="label">What is it about?</label>
                        <select id="topic" name="topic" class="input" required>
                            @foreach (\App\Enums\MessageTopic::cases() as $t)<option value="{{ $t->value }}" @selected(old('topic') === $t->value)>{{ $t->label() }}</option>@endforeach
                        </select>
                    </div>
                    <x-field name="name" label="Your name" autocomplete="name" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="email" type="email" label="Email" :optional="true" autocomplete="email" />
                        <x-field name="phone" type="tel" label="Phone" :optional="true" autocomplete="tel" inputmode="tel" />
                    </div>
                    <p class="-mt-2 text-sm text-ink-3">Give at least one so we can reply.</p>
                    <x-field name="body" type="textarea" label="Message" maxlength="3000" />
                    <div class="hidden" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                    <button class="btn btn-primary">Send message<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
                </form>
            @endif
        </section>
    </div>
</x-layouts.site>
