@php($wa = setting('pharmacy_whatsapp'))
<x-layouts.site title="Pharmacy & prescription refills" description="Request a prescription refill from the Woodlands Hospital pharmacy online or on WhatsApp.">
    <x-page-header title="Pharmacy & refills" sign="{{ setting('pharmacy_hours') ?: 'Pharmacy' }}" code="Rx" photo="pharmacy-shelves" photo-alt="Medicines on pharmacy shelves (illustrative photo)"
        lead="Send your refill list and we will tell you when it is ready to collect. For questions about a medicine, message the pharmacist on WhatsApp.">
        <div class="mt-7 flex flex-wrap gap-3">
            <a href="#refill" class="btn btn-clay">Request a refill</a>
            <a href="{{ whatsapp_href($wa, 'Hello Woodlands Pharmacy, I would like to ask about a prescription.') }}" class="btn btn-on-dark" rel="noopener"><x-icon name="whatsapp" class="h-5 w-5" />WhatsApp {{ format_phone($wa) }}</a>
        </div>
    </x-page-header>

    <div class="wrap mt-12 grid gap-12 lg:grid-cols-[1fr_20rem]">
        <section id="refill" class="scroll-mt-32" aria-labelledby="refill-title">
            <h2 id="refill-title" class="display-3">Refill request</h2>
            <form method="POST" action="{{ route('pharmacy.store') }}" class="mt-6 space-y-5" novalidate>
                @csrf
                <x-form-errors />
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="name" label="Patient’s full name" autocomplete="name" class="sm:col-span-2" />
                    <x-field name="phone" type="tel" label="Phone" autocomplete="tel" inputmode="tel" />
                    <x-field name="email" type="email" label="Email" :optional="true" autocomplete="email" hint="We email you when it’s ready." />
                    <x-field name="date_of_birth" type="date" label="Patient’s date of birth" :optional="true" :max="today()->subDay()->toDateString()" />
                    <x-field name="prescriber" label="Prescribing doctor" :optional="true" />
                </div>
                <x-field name="medications" type="textarea" label="Medicines" hint="One per line, with strength and quantity if you know them, e.g. “Metformin 500 mg, 60 tablets”." rows="5" maxlength="2000" />
                <x-field name="notes" type="textarea" label="Notes for the pharmacist" :optional="true" rows="3" maxlength="1000" />
                <div class="hidden" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                <label class="choice"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>The pharmacy may contact me about this request. <a href="{{ route('privacy') }}" class="text-link">Privacy</a></span></label>
                <x-error for="consent" />
                <button class="btn btn-clay">Send refill request<x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
            </form>
        </section>
        <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
            <div class="panel p-5">
                <p class="font-bold">Collecting your medicines</p>
                <ul class="mt-3 list-disc space-y-1.5 pl-5 text-[0.9375rem] text-ink-2">
                    <li>Wait for our message that it is ready.</li>
                    <li>Bring photo ID and your prescription.</li>
                    <li>Some medicines need a new prescription; we will tell you.</li>
                </ul>
            </div>
            <a href="{{ whatsapp_href($wa) }}" class="panel flex items-center gap-4 p-5 hover:border-line-strong" rel="noopener">
                <span class="flex h-11 w-11 flex-none items-center justify-center rounded-lg bg-mint-soft text-primary"><x-icon name="whatsapp" /></span>
                <span><span class="block font-bold">WhatsApp the pharmacy</span><span class="block text-sm text-ink-2">{{ format_phone($wa, true) }}, messages only</span></span>
            </a>
        </aside>
    </div>
</x-layouts.site>
