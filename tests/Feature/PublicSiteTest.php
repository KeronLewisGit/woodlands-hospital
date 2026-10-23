<?php

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Models\ClinicSession;
use App\Models\Doctor;
use App\Models\JobPosting;
use App\Models\Message;
use App\Models\RefillRequest;
use App\Models\Setting;
use App\Notifications\NewSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_renders(): void
    {
        foreach (['home', 'services', 'clinics', 'doctors', 'emergency', 'patients', 'about', 'contact', 'privacy', 'credits', 'appointments.create', 'pharmacy', 'jobs.index', 'apply.create', 'portal.login', 'portal.password.request', 'login'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml');
    }

    public function test_every_inner_page_uses_the_shared_page_header(): void
    {
        foreach (['services', 'clinics', 'doctors', 'emergency', 'patients', 'about', 'contact', 'privacy', 'credits', 'appointments.create', 'pharmacy', 'jobs.index', 'apply.create', 'portal.login'] as $route) {
            $this->get(route($route))->assertSee('class="canopy"', false)->assertSee('sign-code', false);
        }
    }

    public function test_ambulance_call_is_on_every_page_with_a_tel_link(): void
    {
        foreach (['home', 'services', 'contact', 'appointments.create'] as $route) {
            $this->get(route($route))->assertSee('href="tel:+5926454765"', false)->assertSee('Quick actions');
        }
    }

    public function test_phone_numbers_and_extensions_come_from_settings(): void
    {
        $this->get(route('home'))->assertSee('225-4050')->assertSee('Dr N. Gobin clinic')->assertSee('tel:+5922254050,251', false);

        Setting::setMany(['phone_main' => '226-0000', 'ext_clinics' => '300', 'extra_extensions' => "Maternity | 410\nBilling | 222"]);
        $this->get(route('home'))->assertSee('226-0000')->assertSee('tel:+5922260000,300', false)->assertSee('Maternity')->assertDontSee('Dr N. Gobin clinic');
    }

    public function test_doctor_directory_and_timetable_handle_no_data_without_placeholders(): void
    {
        $this->get(route('doctors'))->assertOk()->assertSee('Our doctor directory is coming soon')->assertSee('extension 251');
        $this->get(route('clinics'))->assertOk()->assertSee('The clinic timetable is being updated');
    }

    public function test_published_doctors_and_sessions_appear(): void
    {
        $doc = Doctor::create(['slug' => 'dr-a', 'name' => 'Dr Test Person', 'department' => 'cardiology', 'is_published' => true]);
        Doctor::create(['slug' => 'dr-b', 'name' => 'Dr Hidden Person', 'department' => 'cardiology', 'is_published' => false]);
        ClinicSession::create(['department' => 'cardiology', 'doctor_id' => $doc->id, 'weekday' => 2, 'starts_at' => '09:00', 'ends_at' => '12:30', 'is_active' => true]);
        ClinicSession::create(['department' => 'urology', 'label' => 'Hidden clinic', 'weekday' => 3, 'starts_at' => '09:00', 'ends_at' => '12:00', 'is_active' => false]);

        $this->get(route('doctors'))->assertSee('Dr Test Person')->assertDontSee('Dr Hidden Person')->assertSee('TP');
        $this->get(route('doctors', ['q' => 'nobody']))->assertSee('No doctors match');
        $this->get(route('clinics'))->assertSee('Tuesday')->assertSee('9:00 am – 12:30 pm')->assertSee('Dr Test Person')->assertDontSee('Hidden clinic');
    }

    public function test_refill_request_is_saved_and_office_notified(): void
    {
        Notification::fake();
        Setting::setMany(['notify_email' => 'pharmacy@example.test']);
        $this->get(route('pharmacy'))->assertSee('wa.me/5926223829', false);

        $this->post(route('pharmacy.store'), ['name' => 'Joan Mc Donald', 'phone' => '622 0000', 'medications' => "Metformin 500mg\nAmlodipine", 'consent' => '1'])
            ->assertRedirect(route('pharmacy.received'));
        $r = RefillRequest::sole();
        $this->assertMatchesRegularExpression('/^RX-\d{2}-\d{5}$/', $r->reference);
        $this->get(route('pharmacy.received'))->assertSee($r->reference);
        Notification::assertSentTimes(NewSubmission::class, 1);

        $this->post(route('pharmacy.store'), ['name' => 'X', 'phone' => '622 0000', 'consent' => '1'])->assertSessionHasErrors('medications');
    }

    public function test_contact_message_needs_a_way_to_reply(): void
    {
        Notification::fake();
        $this->post(route('contact.store'), ['topic' => 'general', 'name' => 'Sam', 'body' => 'Hello'])->assertSessionHasErrors(['email', 'phone']);
        $this->post(route('contact.store'), ['topic' => 'billing', 'name' => 'Sam', 'phone' => '600-0000', 'body' => 'Hello'])->assertRedirect();
        $this->assertSame(1, Message::count());
        $this->get(route('contact'))->assertSee('Your message has been sent');
    }

    public function test_careers_page_lists_open_jobs_only(): void
    {
        $this->get(route('jobs.index'))->assertSee('No roles are advertised right now');
        $base = ['category' => JobCategory::Nursing, 'employment_type' => EmploymentType::FullTime, 'summary' => 'x', 'location' => 'Georgetown'];
        $open = JobPosting::create($base + ['slug' => 'rn', 'title' => 'Registered Nurse', 'status' => JobStatus::Open]);
        JobPosting::create($base + ['slug' => 'draft', 'title' => 'Draft Role', 'status' => JobStatus::Draft]);
        JobPosting::create($base + ['slug' => 'expired', 'title' => 'Expired Role', 'status' => JobStatus::Open, 'ends_on' => today()->subDay()]);

        $this->get(route('jobs.index'))->assertSee('Registered Nurse')->assertDontSee('Draft Role')->assertDontSee('Expired Role');
        $this->get(route('jobs.show', $open))->assertOk()->assertSee('"@type":"JobPosting"', false);
        $this->get(route('jobs.show', 'draft'))->assertNotFound();
        $this->get(route('jobs.show', 'expired'))->assertOk()->assertSee('no longer open');
        $this->get(route('sitemap'))->assertSee(route('jobs.show', $open));
    }

    public function test_security_headers_and_preview_mode(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        config(['woodlands.preview' => true]);
        $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertSee('Concept preview');
    }

    public function test_announcement_setting_shows_site_wide(): void
    {
        Setting::setMany(['announcement' => 'Clinics closed on Phagwah.']);
        $this->get(route('about'))->assertSee('Clinics closed on Phagwah.');
    }

    public function test_unknown_pages_show_the_hospital_404(): void
    {
        $this->get('/nope')->assertNotFound()->assertSee('Page not found')->assertSee('Ambulance');
    }

    public function test_phone_helpers(): void
    {
        $this->assertSame('tel:+5922254050', tel_href('225-4050'));
        $this->assertSame('tel:+5922254050,251', tel_href('+592 225 4050', '251'));
        $this->assertSame('225-4050', format_phone('5922254050'));
        $this->assertSame('+592 645-4765', format_phone('645 4765', true));
        $this->assertSame('https://wa.me/5926223829?text=Hi%20there', whatsapp_href('622-3829', 'Hi there'));
        $this->assertSame('not a number', format_phone('not a number'));
    }
}
