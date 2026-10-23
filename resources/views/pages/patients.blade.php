@php($main = setting('phone_main'))
<x-layouts.site title="Patients & visitors" description="What to bring, how to get your results, pharmacy refills, visiting and getting to Woodlands Hospital, Georgetown.">
    <x-page-header title="Patients & visitors" sign="Before you come in" code="i" photo="nurses-station" photo-alt="Nurses’ station on a ward at Woodlands Hospital"
        lead="Practical information for appointments, results, prescriptions and visiting someone on the wards." />

    <div class="wrap mt-12 grid gap-12 lg:grid-cols-[16rem_1fr]">
        <nav class="hidden lg:block" aria-label="On this page">
            <ul class="sticky top-28 space-y-1 border-l-2 border-line">
                @foreach (['appointments' => 'Appointments', 'bring' => 'What to bring', 'results' => 'Your results', 'pharmacy' => 'Prescriptions', 'visiting' => 'Visiting', 'feedback' => 'Feedback & records'] as $id => $label)
                    <li><a href="#{{ $id }}" class="-ml-0.5 flex min-h-10 items-center border-l-2 border-transparent pl-4 text-ink-2 hover:border-primary hover:text-ink">{{ $label }}</a></li>
                @endforeach
            </ul>
        </nav>
        <div class="prose-copy measure">
            <section id="appointments">
                <h2 class="!mt-0">Appointments</h2>
                <p>Send an <a href="{{ route('appointments.create') }}">appointment request</a> for a doctor’s clinic, specialist clinic, scan or lab test. Choose a preferred day and time of day; the front desk confirms a time by email or phone. Nothing is booked until we confirm.</p>
                <p>Prefer to phone? Call {{ format_phone($main, true) }}: extension {{ setting('ext_clinics') }} for clinics, {{ setting('ext_radiology') }} for radiology and ultrasound, {{ setting('ext_lab') }} for the laboratory.</p>
            </section>
            <section id="bring">
                <h2>What to bring</h2>
                <ul>
                    <li>Photo ID, and your hospital number if you have one</li>
                    <li>Your doctor’s referral or request form for scans, tests and specialist clinics</li>
                    <li>Your medicines, or a written list of them</li>
                    <li>Insurance card or details, if you are claiming</li>
                </ul>
                <p>For blood tests, ask when you book whether you need to fast.</p>
            </section>
            <section id="results">
                <h2>Your results</h2>
                <p>Woodlands publishes laboratory and radiology reports to a <a href="{{ route('portal.login') }}">secure results portal</a>. To get access, give the front desk or laboratory your email address when you register or have a test. We create your account and email you a one-time link to choose a password. If we have your date of birth on file, you will be asked to confirm it.</p>
                <p>There is no public sign-up form, so nobody can find out whether you are a patient here by trying your email address. When a new report arrives, we email you that it is ready; the results themselves are only ever shown inside the portal.</p>
                <p>Please talk through your results with the doctor who requested them.</p>
            </section>
            <section id="pharmacy">
                <h2>Prescriptions and refills</h2>
                <p>The pharmacy is {{ mb_strtolower((string) setting('pharmacy_hours')) ?: 'open daily' }}. Send a <a href="{{ route('pharmacy') }}">refill request</a> online or message the pharmacy on WhatsApp at {{ format_phone(setting('pharmacy_whatsapp')) }} (messages only, no calls).</p>
            </section>
            <section id="visiting">
                <h2>Visiting someone on the wards</h2>
                @if ($hours = setting('visiting_hours'))
                    <p>{{ $hours }}</p>
                @else
                    <p>Visiting arrangements depend on the ward. Please call {{ format_phone($main, true) }} or ask at the nurses’ station before you visit.</p>
                @endif
                <p>Please don’t visit if you have a cough, cold, fever or stomach upset, and wash or sanitise your hands when you arrive and leave.</p>
            </section>
            <section id="feedback">
                <h2>Feedback and medical records</h2>
                <p>To compliment the team, raise a concern or ask for a copy of your records, <a href="{{ route('contact') }}">send us a message</a> or email <a href="mailto:{{ setting('email_customer') ?: setting('email_care') }}">{{ setting('email_customer') ?: setting('email_care') }}</a>.</p>
            </section>
        </div>
    </div>
</x-layouts.site>
