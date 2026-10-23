<x-layouts.site title="About Woodlands" description="Woodlands Hospital, a private multi-specialty hospital on Carmichael Street, Georgetown, since 1969.">
    <x-page-header title="A Georgetown hospital since 1969" sign="About Woodlands" code="69" photo="georgetown-gardens-palms" photo-alt="Palms and trees in the Botanical Gardens, Georgetown"
        lead="Woodlands opened on Carmichael Street in 1969. It has grown into a private multi-specialty hospital serving patients from all walks of life." />

    <div class="wrap mt-14 grid gap-14 lg:grid-cols-[1.2fr_1fr]">
        <div class="prose-copy measure">
            <h2 class="!mt-0">Care with a human touch</h2>
            <p>In the hospital’s own words: “We believe in a patient-centred approach to health care, where we provide care that is respectful of and responsive to individual patient preference, needs and values.”</p>
            <h2>From humble beginnings</h2>
            <p>The hospital opened with {{ woodlands('history_1969') }}. Today it has 55 beds, an Intensive Care Unit, two operating theatres, more than 70 consultants and close to 300 staff, and reports seeing nearly 50 inpatients and 500 outpatients a day.</p>
            <h2>Services</h2>
            <p>The main hospital provides care on a 24-hour basis, including cardiology, gynaecology, orthopaedics, paediatrics, neurology, urology, internal medicine, general surgery, psychiatry, the medical laboratory, radiology, pathology, ophthalmology, ear, nose and throat, maxillofacial surgery, dental care, physiotherapy and emergency medicine. <a href="{{ route('services') }}">See all services</a>.</p>
            <p class="text-sm text-ink-3">Figures from Woodlands Hospital’s published About Us page (2024).</p>
        </div>
        <aside>
            <h2 class="display-3">Milestones</h2>
            <ol class="mt-6 border-l-2 border-line">
                @foreach (woodlands('milestones') as $m)
                    <li class="relative pb-8 pl-7 last:pb-0">
                        <span class="absolute -left-[0.55rem] top-1 h-4 w-4 rounded-full border-2 border-surface bg-primary" aria-hidden="true"></span>
                        <p class="font-display text-2xl">{{ $m['year'] }}</p>
                        <p class="mt-1 text-ink-2">{{ $m['text'] }}</p>
                        <p class="mt-1 text-sm text-ink-3">Source: <a class="text-link" href="{{ $m['url'] }}" rel="noopener" target="_blank">{{ $m['source'] }}</a></p>
                    </li>
                @endforeach
            </ol>
            <dl class="mt-10 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-line ring-1 ring-line">
                @foreach (woodlands('facts') as $fact)
                    <div class="bg-surface p-4"><dt class="text-sm text-ink-3">{{ $fact['label'] }}</dt><dd class="font-display num mt-1 text-4xl">{{ $fact['value'] }}</dd></div>
                @endforeach
            </dl>
        </aside>
    </div>
</x-layouts.site>
