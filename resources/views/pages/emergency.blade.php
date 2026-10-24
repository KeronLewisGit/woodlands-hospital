@php($ambulance = setting('ambulance_phone'))
@php($main = setting('phone_main'))
<x-layouts.site title="Emergency & ambulance" :description="'Woodlands Hospital Emergency Room is open 24 hours. Ambulance: '.format_phone($ambulance).' or '.format_phone($main).' ext '.setting('ambulance_ext').'.'">
    <x-page-header title="Emergency Room & ambulance" sign="Open 24 hours" code="ER" tone="er"
        lead="If someone’s life is in danger, call the ambulance now or come straight to the Emergency Room. You do not need an appointment.">
        <div class="mt-7 flex flex-wrap gap-3">
            <a href="{{ tel_href($ambulance) }}" class="btn btn-er"><x-icon name="ambulance" class="h-5 w-5" />Call ambulance {{ format_phone($ambulance) }}</a>
            <a href="{{ tel_href($main, setting('ambulance_ext')) }}" class="btn btn-on-dark"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($main) }} ext {{ setting('ambulance_ext') }}</a>
        </div>
    </x-page-header>

    <div class="wrap mt-14 grid gap-12 lg:grid-cols-[1.2fr_1fr]">
        <section class="prose-copy measure" aria-labelledby="when">
            <h2 id="when" class="!mt-0">Call the ambulance for</h2>
            <ul>
                <li>Chest pain, or pain spreading to the arm, jaw or back</li>
                <li>Trouble breathing, choking, or lips turning blue</li>
                <li>Sudden weakness of the face, arm or leg, slurred speech or confusion</li>
                <li>Heavy bleeding that won’t stop, or a serious injury or burn</li>
                <li>Someone who has collapsed, is unconscious or is having a seizure</li>
            </ul>
            <h2>While you wait</h2>
            <ul>
                <li>Stay on the line and give the exact address, a landmark and a phone number.</li>
                <li>Unlock the door and, at night, switch on an outside light.</li>
                <li>Don’t give food or drink. Collect the person’s medicines and any ID.</li>
            </ul>
            <h2>Coming to the Emergency Room yourself</h2>
            <p>The hospital is at {{ woodlands('address.street') }}, {{ woodlands('address.area') }}, Georgetown. Bring photo ID, your medicines or a list of them, and any insurance details.</p>
        </section>
        <aside class="space-y-5">
            <div class="callout callout-er">
                <p class="font-bold">This page is general guidance.</p>
                <p class="mt-1 text-ink-2">It does not replace advice from a doctor. If in doubt, call.</p>
            </div>
            <div class="board board-light panel">
                <a href="{{ tel_href($ambulance) }}" class="board-row"><span class="sign-code is-er"><x-icon name="ambulance" class="h-4 w-4" /></span><span><span class="block font-bold">Ambulance (24 hours)</span><span class="block text-ink-2">{{ format_phone($ambulance, true) }}</span></span><x-icon name="phone" class="h-4 w-4 text-ink-3" /></a>
                <a href="{{ tel_href($main, setting('ambulance_ext')) }}" class="board-row"><span class="sign-code is-er num">{{ setting('ambulance_ext') }}</span><span><span class="block font-bold">Main line, ambulance extension</span><span class="block text-ink-2">{{ format_phone($main, true) }}</span></span><x-icon name="phone" class="h-4 w-4 text-ink-3" /></a>
                <a href="{{ woodlands('map_url') }}" target="_blank" rel="noopener" class="board-row"><span class="sign-code"><x-icon name="map-pin" class="h-4 w-4" /></span><span><span class="block font-bold">Directions</span><span class="block text-ink-2">{{ woodlands('address.street') }}</span></span><x-icon name="external" class="h-4 w-4 text-ink-3" /></a>
            </div>
        </aside>
    </div>
</x-layouts.site>
