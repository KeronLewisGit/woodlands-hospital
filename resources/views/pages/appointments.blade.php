@php
    $services = \App\Enums\AppointmentService::cases();
    $selected = old('service', $service?->value);
    $clinics = department_options(['clinic']);
@endphp
<x-layouts.site title="Request an appointment" description="Request a clinic, specialist, radiology or laboratory appointment at Woodlands Hospital. We confirm a time with you by email or phone.">
    <x-page-header title="Request an appointment" sign="Front desk" code="FD" photo="ct-suite" photo-alt="The CT suite at Woodlands Hospital"
        lead="Tell us what you need and when suits you. The front desk confirms a time, or offers the nearest one, by email or phone. Nothing is booked until we confirm." />

    <div class="wrap mt-12 grid gap-12 lg:grid-cols-[1fr_20rem]">
        <form method="POST" action="{{ route('appointments.store') }}" class="space-y-10" novalidate
              x-data="appointmentForm({ service: @js($selected), department: @js(old('department', $department)) })" @submit="submit()">
            @csrf
            <x-form-errors />

            <fieldset>
                <legend class="display-3">1. What do you need?</legend>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    @foreach ($services as $s)
                        <label class="choice">
                            <input type="radio" name="service" value="{{ $s->value }}" x-model="service" @checked($selected === $s->value) required>
                            <span><span class="flex items-center gap-2 font-bold"><x-icon :name="$s->icon()" class="h-5 w-5 text-primary" />{{ $s->label() }}</span><span class="mt-0.5 block text-[0.9375rem] text-ink-2">{{ $s->hint() }}</span></span>
                        </label>
                    @endforeach
                </div>
                <x-error for="service" />

                <div class="mt-5" x-show="needsDepartment" x-cloak>
                    <label for="department" class="label">Which specialist clinic?</label>
                    <select id="department" name="department" class="input sm:max-w-md" x-model="department" @error('department') aria-invalid="true" @enderror>
                        <option value="">Choose a clinic</option>
                        @foreach ($clinics as $key => $name)<option value="{{ $key }}" @selected(old('department', $department) === $key)>{{ $name }}</option>@endforeach
                    </select>
                    <x-error for="department" />
                    @if ($doctors->isNotEmpty())
                        <label for="doctor_id" class="label mt-5">Doctor <span class="opt">(optional)</span></label>
                        <select id="doctor_id" name="doctor_id" class="input sm:max-w-md">
                            <option value="">Any available doctor</option>
                            @foreach ($doctors as $d)<option value="{{ $d->id }}" x-show="!department || department === @js($d->department)" @selected((string) old('doctor_id') === (string) $d->id)>{{ $d->name }}, {{ $d->departmentName() }}</option>@endforeach
                        </select>
                    @endif
                </div>
                <noscript><p class="hint">If you chose “Specialist clinic”, pick the clinic:</p>
                    <select name="department" class="input mt-2 sm:max-w-md"><option value="">Choose a clinic</option>@foreach ($clinics as $key => $name)<option value="{{ $key }}">{{ $name }}</option>@endforeach</select>
                </noscript>
            </fieldset>

            <fieldset>
                <legend class="display-3">2. When suits you?</legend>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <x-field name="preferred_date" type="date" label="Preferred date" :min="today()->toDateString()" :max="today()->addMonths(6)->subDay()->toDateString()" />
                    <x-field name="alternate_date" type="date" label="Another date that works" :optional="true" :min="today()->toDateString()" />
                </div>
                <p class="label mt-6" id="window-label">Time of day</p>
                <div class="grid gap-3 sm:grid-cols-3" role="radiogroup" aria-labelledby="window-label">
                    @foreach (\App\Models\AppointmentRequest::WINDOWS as $key => $label)
                        <label class="choice"><input type="radio" name="preferred_window" value="{{ $key }}" @checked(old('preferred_window', 'morning') === $key) required><span class="font-semibold">{{ $label }}</span></label>
                    @endforeach
                </div>
                <x-error for="preferred_window" />
            </fieldset>

            <fieldset>
                <legend class="display-3">3. About you</legend>
                <div class="mt-5 grid gap-5 sm:grid-cols-2">
                    <x-field name="first_name" label="First name" autocomplete="given-name" />
                    <x-field name="last_name" label="Last name" autocomplete="family-name" />
                    <x-field name="phone" type="tel" label="Phone" autocomplete="tel" inputmode="tel" hint="We call this number if we can’t confirm by email." />
                    <x-field name="email" type="email" label="Email" :optional="true" autocomplete="email" hint="Add one to get your confirmation by email." />
                    <x-field name="date_of_birth" type="date" label="Date of birth" :optional="true" autocomplete="bday" :max="today()->subDay()->toDateString()" hint="Helps us find your records." />
                </div>
                <div class="mt-5 space-y-3">
                    <label class="choice"><input type="checkbox" name="returning_patient" value="1" @checked(old('returning_patient'))><span>I have been a patient at Woodlands before</span></label>
                    <label class="choice"><input type="checkbox" name="has_referral" value="1" @checked(old('has_referral'))><span>I have a referral or request form from a doctor</span></label>
                </div>
                <x-field name="reason" type="textarea" label="Anything we should know?" :optional="true" class="mt-5" maxlength="1000" hint="For example, the test your doctor asked for. Please keep medical details brief." />
            </fieldset>

            <div class="hidden" aria-hidden="true"><label for="website">Leave this empty</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
            <div>
                <label class="choice"><input type="checkbox" name="consent" value="1" required @checked(old('consent'))><span>Woodlands Hospital may contact me by phone or email about this request. <a href="{{ route('privacy') }}" class="text-link">Privacy</a></span></label>
                <x-error for="consent" />
            </div>
            <button class="btn btn-clay w-full sm:w-auto" :disabled="submitting"><span x-text="submitting ? 'Sending…' : 'Send request'">Send request</span><x-icon name="arrow-right" class="nudge h-4 w-4" /></button>
        </form>

        <aside class="space-y-5 lg:sticky lg:top-28 lg:self-start">
            <div class="callout callout-er">
                <p class="font-bold">Not for emergencies</p>
                <p class="mt-1 text-ink-2">Call the ambulance on <a class="font-bold text-er-text underline" href="{{ tel_href(setting('ambulance_phone')) }}">{{ format_phone(setting('ambulance_phone')) }}</a> or come to the Emergency Room.</p>
            </div>
            <div class="panel p-5">
                <p class="font-bold">Rather phone?</p>
                <ul class="mt-3 space-y-2 text-[0.9375rem]">
                    <li><a class="text-link" href="{{ tel_href(setting('phone_main'), setting('ext_clinics')) }}">Clinics, ext {{ setting('ext_clinics') }}</a></li>
                    <li><a class="text-link" href="{{ tel_href(setting('phone_main'), setting('ext_radiology')) }}">Radiology &amp; ultrasound, ext {{ setting('ext_radiology') }}</a></li>
                    <li><a class="text-link" href="{{ tel_href(setting('phone_main'), setting('ext_lab')) }}">Laboratory, ext {{ setting('ext_lab') }}</a></li>
                </ul>
                <p class="mt-3 text-sm text-ink-3">Main line {{ format_phone(setting('phone_main'), true) }}</p>
            </div>
        </aside>
    </div>
</x-layouts.site>
