<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Enums\RefillStatus;
use App\Enums\Role;
use App\Models\AppointmentRequest;
use App\Models\Doctor;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Message;
use App\Models\Patient;
use App\Models\RefillRequest;
use App\Models\User;
use App\Notifications\RefillReady;
use App\Support\Notify;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(Role $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_staff_login_and_logout(): void
    {
        $u = User::factory()->create(['email' => 'desk@example.test', 'role' => Role::FrontDesk]);
        $this->post(route('login'), ['email' => 'DESK@example.test', 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($u, 'web');
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->post(route('login'), ['email' => 'desk@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    public function test_guests_are_sent_to_staff_login(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get(route('admin.settings.edit'))->assertRedirect(route('login'));
    }

    public function test_role_access_matrix(): void
    {
        $matrix = [
            'admin.appointments.index' => [Role::Admin, Role::FrontDesk],
            'admin.refills.index' => [Role::Admin, Role::FrontDesk],
            'admin.messages.index' => [Role::Admin, Role::FrontDesk],
            'admin.clinics.index' => [Role::Admin, Role::FrontDesk],
            'admin.patients.index' => [Role::Admin, Role::FrontDesk, Role::Lab],
            'admin.doctors.index' => [Role::Admin],
            'admin.jobs.index' => [Role::Admin],
            'admin.applications.index' => [Role::Admin],
            'admin.settings.edit' => [Role::Admin],
            'admin.staff.index' => [Role::Admin],
            'admin.dashboard' => [Role::Admin, Role::FrontDesk, Role::Lab],
        ];
        $users = collect(Role::cases())->mapWithKeys(fn (Role $r) => [$r->value => $this->user($r)]);
        foreach ($matrix as $route => $allowed) {
            foreach (Role::cases() as $role) {
                $status = $this->actingAs($users[$role->value])->get(route($route))->getStatusCode();
                $this->assertSame(in_array($role, $allowed, true) ? 200 : 403, $status, "{$role->value} on {$route}");
            }
        }
    }

    public function test_lab_dashboard_leads_with_unopened_reports(): void
    {
        $this->actingAs($this->user(Role::Lab))->get(route('admin.dashboard'))->assertOk()->assertSee('Every report has been opened')->assertDontSee('Appointment requests waiting');
    }

    public function test_settings_update_and_validation(): void
    {
        $admin = $this->user(Role::Admin);
        $data = ['phone_main' => '226-1111', 'ambulance_phone' => '645-4765', 'ambulance_ext' => '911', 'ext_clinics' => '251', 'ext_radiology' => '282', 'ext_lab' => '319',
            'pharmacy_whatsapp' => '622-3829', 'email_care' => 'wecare@example.test', 'visiting_hours' => 'Daily 4–6 pm', 'notify_email' => 'a@example.test, b@example.test'];
        $this->actingAs($admin)->put(route('admin.settings.update'), $data)->assertSessionHasNoErrors();
        $this->assertSame('226-1111', setting('phone_main'));
        $this->get(route('patients'))->assertSee('Daily 4–6 pm');
        $this->assertSame(['a@example.test', 'b@example.test'], Notify::recipients());

        $this->actingAs($admin)->put(route('admin.settings.update'), ['ext_lab' => 'abc'] + $data)->assertSessionHasErrors('ext_lab');
    }

    public function test_admin_manages_staff_but_cannot_demote_or_delete_self(): void
    {
        $admin = $this->user(Role::Admin);
        $this->actingAs($admin)->post(route('admin.staff.store'), ['name' => 'Lab Tech', 'email' => 'Tech@Example.test', 'role' => 'lab', 'password' => 'long password 1'])->assertSessionHasNoErrors();
        $tech = User::where('email', 'tech@example.test')->sole();
        $this->assertSame(Role::Lab, $tech->role);

        $this->actingAs($admin)->patch(route('admin.staff.update', $admin), ['role' => 'lab'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->delete(route('admin.staff.destroy', $admin))->assertSessionHasErrors('user');
        $this->actingAs($admin)->delete(route('admin.staff.destroy', $tech));
        $this->assertModelMissing($tech);
    }

    public function test_doctor_crud_feeds_the_public_directory(): void
    {
        $admin = $this->user(Role::Admin);
        $this->actingAs($admin)->post(route('admin.doctors.store'), ['name' => 'Dr Example Name', 'department' => 'paediatrics', 'title' => 'Consultant Paediatrician', 'is_published' => '1', 'sort' => 10])->assertRedirect();
        $doc = Doctor::sole();
        $this->get(route('doctors'))->assertSee('Dr Example Name')->assertSee('Consultant Paediatrician');

        $this->actingAs($admin)->put(route('admin.doctors.update', $doc), ['name' => 'Dr Example Name', 'department' => 'paediatrics', 'sort' => 10]);
        $this->get(route('doctors'))->assertDontSee('Dr Example Name');
        $this->actingAs($admin)->post(route('admin.doctors.store'), ['name' => 'X', 'department' => 'astrology', 'sort' => 1])->assertSessionHasErrors('department');
    }

    public function test_front_desk_edits_the_clinic_timetable(): void
    {
        $desk = $this->user(Role::FrontDesk);
        $this->actingAs($desk)->post(route('admin.clinics.store'), ['department' => 'ophthalmology', 'label' => 'Eye clinic', 'weekday' => 4, 'starts_at' => '13:00', 'ends_at' => '16:00', 'is_active' => '1'])->assertRedirect();
        $this->get(route('clinics'))->assertSee('Thursday')->assertSee('Eye clinic')->assertSee('1:00 pm – 4:00 pm');
        $this->actingAs($desk)->post(route('admin.clinics.store'), ['department' => 'ophthalmology', 'weekday' => 4, 'starts_at' => '13:00', 'ends_at' => '12:00'])->assertSessionHasErrors(['ends_at', 'label']);
    }

    public function test_refill_ready_emails_the_patient_once(): void
    {
        Notification::fake();
        $desk = $this->user(Role::FrontDesk);
        $r = RefillRequest::create(['name' => 'Joan', 'phone' => '6220000', 'email' => 'joan@example.test', 'medications' => 'Metformin', 'status' => RefillStatus::New]);
        $this->actingAs($desk)->patch(route('admin.refills.update', $r), ['status' => 'ready']);
        $this->actingAs($desk)->patch(route('admin.refills.update', $r), ['status' => 'ready', 'staff_notes' => 'Shelf B']);
        Notification::assertSentTimes(RefillReady::class, 1);
        Notification::assertSentTo(new AnonymousNotifiable, RefillReady::class, fn ($n, $c, $to) => $to->routes['mail'] === 'joan@example.test');
    }

    public function test_messages_can_be_marked_handled(): void
    {
        $m = Message::create(['topic' => 'general', 'name' => 'A', 'phone' => '600', 'body' => 'Hi']);
        $this->actingAs($this->user(Role::FrontDesk))->patch(route('admin.messages.update', $m), ['handled' => '1']);
        $this->assertNotNull($m->fresh()?->handled_at);
    }

    public function test_job_application_with_cv_and_private_download(): void
    {
        Storage::fake('local');
        Notification::fake();
        $job = JobPosting::create(['slug' => 'rn', 'title' => 'Registered Nurse', 'category' => JobCategory::Nursing, 'employment_type' => EmploymentType::FullTime, 'status' => JobStatus::Open, 'summary' => 'x', 'location' => 'Georgetown']);
        $this->get(route('apply.create', ['job' => 'rn']))->assertOk()->assertSee('Apply: Registered Nurse');

        $this->post(route('apply.store'), ['job_posting_id' => $job->id, 'first_name' => 'Alicia', 'last_name' => 'Bacchus', 'phone' => '600-1111', 'email' => 'alicia@example.test', 'consent' => '1'])
            ->assertSessionHasErrors('cv');
        $this->post(route('apply.store'), ['job_posting_id' => $job->id, 'first_name' => 'Alicia', 'last_name' => 'Bacchus', 'phone' => '600-1111', 'email' => 'alicia@example.test', 'category' => 'support',
            'cv' => UploadedFile::fake()->create('cv.pdf', 200, 'application/pdf'), 'consent' => '1'])->assertRedirect(route('apply.received'));

        $app = JobApplication::sole();
        $this->assertSame(JobCategory::Nursing, $app->category, 'the job decides the category');
        Storage::disk('local')->assertExists((string) $app->cv_path);

        $this->get(route('admin.applications.cv', $app))->assertRedirect(route('login'));
        $this->actingAs($this->user(Role::FrontDesk))->get(route('admin.applications.cv', $app))->assertForbidden();
        $admin = $this->user(Role::Admin);
        $this->actingAs($admin)->get(route('admin.applications.cv', $app))->assertOk();
        $this->actingAs($admin)->patch(route('admin.applications.update', $app), ['status' => 'reviewing', 'staff_notes' => 'Call Monday']);
        $this->assertSame(ApplicationStatus::Reviewing, $app->fresh()?->status);
    }

    public function test_job_crud_and_delete_with_applicants_closes(): void
    {
        $admin = $this->user(Role::Admin);
        $this->actingAs($admin)->post(route('admin.jobs.store'), ['title' => 'Pharmacist', 'category' => 'pharmacy', 'employment_type' => 'full_time', 'status' => 'open', 'location' => 'Georgetown', 'openings' => 1, 'summary' => 'Dispense.'])->assertRedirect();
        $job = JobPosting::sole();
        JobApplication::create(['job_posting_id' => $job->id, 'category' => 'pharmacy', 'status' => 'new', 'first_name' => 'A', 'last_name' => 'B', 'phone' => '6000000', 'email' => 'a@example.test']);
        $this->actingAs($admin)->delete(route('admin.jobs.destroy', $job));
        $this->assertSame(JobStatus::Closed, $job->fresh()?->status);
    }

    public function test_demo_seeder_runs_and_every_admin_page_renders_with_data(): void
    {
        Storage::fake('local');
        $this->seed(DemoSeeder::class);
        $admin = User::where('email', 'admin@woodlands.test')->sole();
        $this->assertSame(0, Doctor::count(), 'doctors are never invented');

        foreach (['admin.dashboard', 'admin.appointments.index', 'admin.refills.index', 'admin.messages.index', 'admin.clinics.index', 'admin.patients.index', 'admin.doctors.index', 'admin.jobs.index', 'admin.applications.index', 'admin.settings.edit', 'admin.staff.index', 'admin.doctors.create', 'admin.clinics.create', 'admin.patients.create', 'admin.jobs.create'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.appointments.show', AppointmentRequest::first()))->assertOk();
        $this->actingAs($admin)->get(route('admin.patients.show', Patient::first()))->assertOk()->assertSee('SAMPLE');
        $this->actingAs($admin)->get(route('admin.applications.show', JobApplication::first()))->assertOk();
        $this->actingAs(User::where('email', 'lab@woodlands.test')->sole())->get(route('admin.dashboard'))->assertOk();

        // Demo patient can sign in and open their sample report.
        $this->post(route('logout'));
        $this->post(route('portal.login'), ['email' => 'patient@woodlands.test', 'password' => 'password'])->assertRedirect(route('portal.dashboard'));
        $this->get(route('portal.dashboard'))->assertSee('Full blood count (SAMPLE)');
    }
}
