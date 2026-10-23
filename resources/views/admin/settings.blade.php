@php
    $field = function (string $key, string $label, ?string $hint = null, string $type = 'text') use ($values) {
        return compact('key', 'label', 'hint', 'type') + ['value' => old($key, $values[$key] ?? '')];
    };
    $groups = [
        'Phone numbers' => [
            $field('phone_main', 'Main line', 'Shown site-wide and used for every extension link.', 'tel'),
            $field('ambulance_phone', 'Ambulance number', 'The red button on every page.', 'tel'),
            $field('ambulance_ext', 'Ambulance extension on the main line'),
            $field('pharmacy_whatsapp', 'Pharmacy WhatsApp number', null, 'tel'),
        ],
        'Extensions' => [
            $field('ext_clinics', 'Doctors’ clinic times & appointments'),
            $field('ext_radiology', 'Radiology & ultrasound'),
            $field('ext_lab', 'Medical laboratory'),
            $field('extra_extensions', 'Other extensions', 'One per line, as “Label | extension”, e.g. “Dr N. Gobin clinic | 265”.', 'textarea'),
        ],
        'Email' => [
            $field('email_care', 'Public email', null, 'email'),
            $field('email_customer', 'Customer care email', null, 'email'),
            $field('notify_email', 'Notification email', 'Who is emailed about new appointment requests, refills, messages and applications. Separate several with commas.'),
        ],
        'Hours & notices' => [
            $field('pharmacy_hours', 'Pharmacy hours'),
            $field('visiting_hours', 'Visiting hours', 'Leave empty to show “ask the ward”.', 'textarea'),
            $field('clinic_hours_note', 'Clinic note', 'Shown on the clinic timetable and home page, e.g. holiday closures.', 'textarea'),
            $field('announcement', 'Site-wide announcement', 'A short notice at the top of every page. Leave empty to hide.', 'textarea'),
        ],
    ];
@endphp
<x-layouts.admin title="Settings">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl space-y-5">
        @csrf @method('PUT')
        @foreach ($groups as $title => $fields)
            <section class="a-card p-5">
                <h2 class="font-bold">{{ $title }}</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ($fields as $f)
                        <div id="{{ $f['key'] }}" @class(['sm:col-span-2' => $f['type'] === 'textarea'])>
                            <label class="a-label" for="f-{{ $f['key'] }}">{{ $f['label'] }}</label>
                            @if ($f['type'] === 'textarea')
                                <textarea id="f-{{ $f['key'] }}" name="{{ $f['key'] }}" rows="3" class="a-input w-full">{{ $f['value'] }}</textarea>
                            @else
                                <input id="f-{{ $f['key'] }}" name="{{ $f['key'] }}" type="{{ $f['type'] }}" class="a-input w-full" value="{{ $f['value'] }}">
                            @endif
                            @if ($f['hint'])<p class="mt-1 text-xs text-ink-3">{{ $f['hint'] }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
        <button class="a-btn a-btn-primary">Save settings</button>
    </form>
</x-layouts.admin>
