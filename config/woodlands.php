<?php

/*
|--------------------------------------------------------------------------
| Woodlands Hospital facts
|--------------------------------------------------------------------------
| Copy that is not edited from the dashboard. Sources, captured October 2026:
|  - woodlandshospital.info "About Us" and "Contact Us" (the hospital's 2024 WordPress site)
|  - woodlandshospital.com holding page (24/7 services, extension table, ambulance numbers)
|  - OpenStreetMap (hospital footprint on Carmichael Street, North Cummingsburg)
| Phone numbers, extensions and hours are editable in the dashboard (settings table,
| defaults in SettingsSeeder), so they are not here. Every figure below is the hospital's
| own published copy and must still be re-confirmed with the client before launch.
*/

return [
    // Concept preview on a temporary domain: shows a "not the official site" banner
    // and tells search engines not to index anything. Turn off at launch.
    'preview' => (bool) env('WOODLANDS_PREVIEW', false),

    'name' => 'Woodlands Hospital Limited',
    'short_name' => 'Woodlands Hospital',
    'founded' => 1969,

    'address' => [
        'street' => '110–111 Carmichael Street',
        'area' => 'North Cummingsburg',
        'city' => 'Georgetown',
        'country' => 'Guyana',
        'country_code' => 'GY',
    ],
    // OpenStreetMap way 337447170 ("Woodlands Hospital", amenity=hospital).
    'geo' => ['lat' => 6.81818, 'lng' => -58.16155],
    'map_url' => 'https://www.openstreetmap.org/way/337447170',

    // Who receives "new appointment request / refill / application / message" emails.
    // The dashboard setting notify_email overrides this when filled in.
    'notify' => env('WOODLANDS_NOTIFY_EMAIL'),

    // From woodlandshospital.info/about-us (2024). Re-confirm with the client before launch.
    'facts' => [
        ['value' => '1969', 'label' => 'Serving Georgetown since'],
        ['value' => '55', 'label' => 'Inpatient beds'],
        ['value' => '2', 'label' => 'Operating theatres'],
        ['value' => '70+', 'label' => 'Consultants'],
    ],

    // Dated milestones, each with its public source. Shown on /about.
    'milestones' => [
        ['year' => '1969', 'text' => 'Woodlands Hospital opens its doors on 2 June 1969.', 'source' => 'Stabroek News, 12 Jan 2020', 'url' => 'https://www.stabroeknews.com/2020/01/12/news/guyana/guyanese-surgeon-dr-bud-lee-dies/'],
        ['year' => '2018', 'text' => 'Guyana’s first cardiac electrophysiology procedures (catheter ablation for heart rhythm disorders) are carried out at Woodlands.', 'source' => 'Kaieteur News, 20 May 2018', 'url' => 'https://www.kaieteurnewsonline.com/2018/05/20/four-undergo-ground-breaking-cardiac-procedure-at-woodlands-hospital/'],
        ['year' => 'Today', 'text' => '55 beds, an Intensive Care Unit, two operating theatres and more than 70 consultants, with the Emergency Room, laboratory, radiology and pharmacy open 24 hours.', 'source' => 'Woodlands Hospital, About Us (2024)', 'url' => 'https://woodlandshospital.info/about-us/'],
    ],

    // Opening of the hospital in 1969, also from the About Us page.
    'history_1969' => 'an Outpatient Department, a 6-bed Maternity Ward, a 7-bed Male Ward, a 12-bed Female Ward, 4 private rooms, a Delivery Room and one main operating theatre',

    /*
     * Departments. Only services the hospital itself lists (About Us, holding page).
     * Blurbs describe what the speciality is, never claims about equipment or outcomes.
     * group: always = open 24/7 · inpatient = wards and theatres · clinic = specialist outpatient
     * ext: settings key of the phone extension that answers for it.
     */
    'departments' => [
        'emergency' => ['name' => 'Emergency Room', 'group' => 'always', 'icon' => 'siren', 'ext' => null,
            'blurb' => 'Walk-in emergency care, day and night. In a life-threatening emergency, call the ambulance line or come straight in.'],
        'ambulance' => ['name' => 'Ambulance service', 'group' => 'always', 'icon' => 'ambulance', 'ext' => null,
            'blurb' => 'A 24-hour ambulance that brings patients to Woodlands. Call the ambulance number or the main line, extension 911.'],
        'laboratory' => ['name' => 'Medical Laboratory', 'group' => 'always', 'icon' => 'flask', 'ext' => 'ext_lab', 'photo' => 'lab-analyser',
            'blurb' => 'Blood tests and other laboratory investigations, open around the clock. Results can be published to your secure results portal.'],
        'radiology' => ['name' => 'Radiology & Ultrasound', 'group' => 'always', 'icon' => 'scan', 'ext' => 'ext_radiology', 'photo' => 'ct-suite',
            'blurb' => 'X-ray, CT and ultrasound imaging, open 24 hours. Book scans through the radiology extension or request one online.'],
        'pharmacy' => ['name' => 'Pharmacy', 'group' => 'always', 'icon' => 'pill', 'ext' => null, 'photo' => 'pharmacy-shelves',
            'blurb' => 'Prescriptions and medicine enquiries. Send a refill request online or message the pharmacy on WhatsApp.'],

        'icu' => ['name' => 'Intensive Care Unit', 'group' => 'inpatient', 'icon' => 'heart-pulse', 'ext' => null,
            'blurb' => 'Close monitoring and support for critically ill patients.'],
        'surgery' => ['name' => 'General Surgery & theatres', 'group' => 'inpatient', 'icon' => 'scalpel', 'ext' => 'ext_clinics',
            'blurb' => 'Planned and emergency operations in the hospital’s two operating theatres, with recovery on the wards.'],
        'wards' => ['name' => 'Inpatient wards', 'group' => 'inpatient', 'icon' => 'bed', 'ext' => null, 'photo' => 'nurses-station',
            'blurb' => 'Nursing care for patients admitted under a Woodlands consultant.'],

        'cardiology' => ['name' => 'Cardiology', 'group' => 'clinic', 'icon' => 'heart-pulse', 'ext' => 'ext_clinics',
            'blurb' => 'Assessment and treatment of heart and circulation problems.'],
        'gynaecology' => ['name' => 'Gynaecology', 'group' => 'clinic', 'icon' => 'flower', 'ext' => 'ext_clinics', 'photo' => 'ultrasound',
            'blurb' => 'Women’s health, from routine check-ups to specialist gynaecological care.'],
        'orthopaedics' => ['name' => 'Orthopaedics', 'group' => 'clinic', 'icon' => 'bone', 'ext' => 'ext_clinics',
            'blurb' => 'Bones, joints, muscles and injuries.'],
        'paediatrics' => ['name' => 'Paediatrics', 'group' => 'clinic', 'icon' => 'baby', 'ext' => 'ext_clinics',
            'blurb' => 'Medical care for babies, children and teenagers.'],
        'neurology' => ['name' => 'Neurology', 'group' => 'clinic', 'icon' => 'brain', 'ext' => 'ext_clinics',
            'blurb' => 'Conditions of the brain, spine and nerves.'],
        'urology' => ['name' => 'Urology', 'group' => 'clinic', 'icon' => 'droplet', 'ext' => 'ext_clinics',
            'blurb' => 'The kidneys, bladder and urinary tract, and men’s health.'],
        'internal-medicine' => ['name' => 'Internal Medicine', 'group' => 'clinic', 'icon' => 'stethoscope', 'ext' => 'ext_clinics',
            'blurb' => 'Diagnosis and long-term care of adult illnesses such as diabetes and high blood pressure.'],
        'psychiatry' => ['name' => 'Psychiatry', 'group' => 'clinic', 'icon' => 'message', 'ext' => 'ext_clinics',
            'blurb' => 'Assessment and treatment for mental health conditions.'],
        'ophthalmology' => ['name' => 'Ophthalmology', 'group' => 'clinic', 'icon' => 'eye', 'ext' => 'ext_clinics',
            'blurb' => 'Eye examinations and treatment of eye disease.'],
        'ent' => ['name' => 'Ear, Nose & Throat', 'group' => 'clinic', 'icon' => 'ear', 'ext' => 'ext_clinics',
            'blurb' => 'Hearing, sinus, throat and voice problems.'],
        'maxillofacial' => ['name' => 'Maxillofacial', 'group' => 'clinic', 'icon' => 'smile', 'ext' => 'ext_clinics',
            'blurb' => 'Surgery of the face, jaw and mouth.'],
        'dental' => ['name' => 'Dental', 'group' => 'clinic', 'icon' => 'tooth', 'ext' => 'ext_clinics',
            'blurb' => 'Dental care and treatment.'],
        'physiotherapy' => ['name' => 'Physiotherapy', 'group' => 'clinic', 'icon' => 'activity', 'ext' => 'ext_clinics',
            'blurb' => 'Rehabilitation after injury, surgery or illness.'],
        'pathology' => ['name' => 'Pathology', 'group' => 'clinic', 'icon' => 'microscope', 'ext' => 'ext_lab',
            'blurb' => 'Laboratory examination of tissue and samples to support diagnosis.'],
    ],

    'groups' => [
        'always' => ['name' => 'Open 24/7', 'code' => '24'],
        'inpatient' => ['name' => 'Inpatient & surgical care', 'code' => 'IP'],
        'clinic' => ['name' => 'Specialist clinics', 'code' => 'OP'],
    ],

    // Photos in public/img. "ours" = the hospital's own photographs from woodlandshospital.info;
    // the rest are Wikimedia Commons illustrations to replace with hospital photos at launch.
    'photos' => [
        'ct-suite' => ['w' => 1600, 'h' => 1066, 'credit' => 'woodlands-ct', 'ours' => true],
        'nurses-station' => ['w' => 1600, 'h' => 1066, 'credit' => 'woodlands-ward', 'ours' => true],
        'ultrasound' => ['w' => 1600, 'h' => 1066, 'credit' => 'woodlands-ultrasound', 'ours' => true],
        'georgetown-gardens-palms' => ['w' => 1920, 'h' => 1280, 'credit' => 'botanical-gardens', 'ours' => false],
        'georgetown-cathedral' => ['w' => 1920, 'h' => 1332, 'credit' => 'st-georges', 'ours' => false],
        'georgetown-seawall' => ['w' => 1920, 'h' => 1280, 'credit' => 'seawall', 'ours' => false],
        'lab-analyser' => ['w' => 1920, 'h' => 1440, 'credit' => 'analyser', 'ours' => false],
        'pharmacy-shelves' => ['w' => 1920, 'h' => 1440, 'credit' => 'pharmacy', 'ours' => false],
    ],

    // First staff account, created by `php artisan db:seed` when both are set.
    'admin' => [
        'name' => env('ADMIN_NAME', 'Administrator'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Results files: maximum upload size in kilobytes.
    'result_max_kb' => 15360,
];
