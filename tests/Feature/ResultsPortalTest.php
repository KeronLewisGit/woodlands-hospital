<?php

namespace Tests\Feature;

use App\Enums\ResultKind;
use App\Enums\Role;
use App\Models\Patient;
use App\Models\PatientResult;
use App\Models\User;
use App\Notifications\PatientResetPassword;
use App\Notifications\PortalInvitation;
use App\Notifications\ResultsReady;
use App\Support\SamplePdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** The secure results portal: the part of the site that must never leak one patient's report to another. */
class ResultsPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    private function patient(string $email, bool $active = true, ?string $dob = '1980-02-03'): Patient
    {
        $p = Patient::create(['first_name' => ucfirst(strtok($email, '@')), 'last_name' => 'Test', 'email' => $email, 'date_of_birth' => $dob]);
        if ($active) {
            $p->forceFill(['password' => 'correct horse battery', 'invited_at' => now()->subDay(), 'activated_at' => now()->subDay()])->save();
        }

        return $p->fresh() ?? $p;
    }

    private function report(Patient $p, string $title = 'Full blood count'): PatientResult
    {
        $path = PatientResult::DIRECTORY.'/'.$p->id.'/'.uniqid().'.pdf';
        Storage::disk('local')->put($path, SamplePdf::make($title, ['for '.$p->email]));

        return $p->results()->create(['kind' => ResultKind::Lab, 'title' => $title, 'file_path' => $path, 'original_name' => 'r.pdf', 'size' => 1000]);
    }

    /** A real file on disk, so the MIME type is sniffed from its content (fake uploads guess from the extension). */
    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'wh');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    private function staff(Role $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_patient_sees_and_downloads_only_their_own_results(): void
    {
        $alice = $this->patient('alice@example.test');
        $bob = $this->patient('bob@example.test');
        $mine = $this->report($alice, 'Alice lipid profile');
        $theirs = $this->report($bob, 'Bob thyroid panel');

        $this->actingAs($alice, 'patient')->get(route('portal.dashboard'))
            ->assertOk()->assertSee('Alice lipid profile')->assertDontSee('Bob thyroid panel');

        $response = $this->actingAs($alice, 'patient')->get(route('portal.results.download', $mine));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertNotNull($mine->fresh()?->first_viewed_at);

        // Another patient's report: 404 (not 403), so IDs can't be probed.
        $this->actingAs($alice, 'patient')->get(route('portal.results.download', $theirs))->assertNotFound();
        $this->assertNull($theirs->fresh()?->first_viewed_at);
    }

    public function test_guests_and_staff_cannot_reach_portal_files(): void
    {
        $alice = $this->patient('alice@example.test');
        $r = $this->report($alice);

        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
        $this->get(route('portal.results.download', $r))->assertRedirect(route('portal.login'));

        // A staff session is a different guard: it does not open the patient portal.
        $this->actingAs($this->staff(Role::Admin))->get(route('portal.results.download', $r))->assertRedirect(route('portal.login'));
    }

    public function test_patient_session_cannot_reach_the_staff_dashboard(): void
    {
        $this->actingAs($this->patient('alice@example.test'), 'patient')->get('/admin')->assertRedirect(route('login'));
    }

    public function test_reports_are_stored_on_the_private_disk_not_public(): void
    {
        $lab = $this->staff(Role::Lab);
        $alice = $this->patient('alice@example.test');

        $this->actingAs($lab)->post(route('admin.results.store', $alice), [
            'kind' => 'lab', 'title' => 'HbA1c', 'file' => UploadedFile::fake()->createWithContent('hba1c.pdf', SamplePdf::make('HbA1c', ['x'])),
        ])->assertRedirect();

        $result = PatientResult::sole();
        Storage::disk('local')->assertExists($result->file_path);
        $this->assertStringStartsWith('results/'.$alice->id.'/', $result->file_path);
        $this->assertStringNotContainsString('hba1c', $result->file_path, 'stored under a random name');
        $this->assertFileDoesNotExist(public_path($result->file_path));
    }

    public function test_publishing_a_report_emails_an_active_patient_without_any_result_details(): void
    {
        $alice = $this->patient('alice@example.test');
        $this->actingAs($this->staff(Role::Lab))->post(route('admin.results.store', $alice), [
            'kind' => 'radiology', 'title' => 'Chest X-ray', 'file' => UploadedFile::fake()->createWithContent('x.pdf', SamplePdf::make('CXR', ['x'])),
        ])->assertSessionHas('status');

        Notification::assertSentTo($alice, ResultsReady::class, function (ResultsReady $n) use ($alice) {
            $mail = $n->toMail($alice);
            $text = $mail->subject.' '.implode(' ', $mail->introLines).' '.implode(' ', $mail->outroLines);

            return ! str_contains($text, 'Chest X-ray') && $mail->actionUrl === route('portal.login');
        });
        $this->assertNotNull(PatientResult::sole()->notified_at);
    }

    public function test_publishing_for_a_patient_who_never_activated_sends_a_fresh_invitation(): void
    {
        $carl = $this->patient('carl@example.test', active: false);
        $this->actingAs($this->staff(Role::Lab))->post(route('admin.results.store', $carl), [
            'kind' => 'lab', 'title' => 'Urinalysis', 'file' => UploadedFile::fake()->createWithContent('u.pdf', SamplePdf::make('U', ['x'])),
        ]);

        Notification::assertSentTo($carl, PortalInvitation::class, fn (PortalInvitation $n) => $n->resultsWaiting);
        Notification::assertNotSentTo($carl, ResultsReady::class);
        $this->assertNotNull($carl->fresh()?->invited_at);
    }

    public function test_only_pdfs_are_accepted(): void
    {
        $alice = $this->patient('alice@example.test');
        $this->actingAs($this->staff(Role::Lab))->post(route('admin.results.store', $alice), [
            'kind' => 'lab', 'title' => 'Fake', 'file' => $this->realUpload('evil.pdf', '<?php echo "hi";'),
        ])->assertSessionHasErrors('file');

        // A genuine PDF passes the same content check.
        $this->actingAs($this->staff(Role::Lab))->post(route('admin.results.store', $alice), [
            'kind' => 'lab', 'title' => 'Real', 'file' => $this->realUpload('real.pdf', SamplePdf::make('Real', ['ok'])),
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, PatientResult::count());
        PatientResult::query()->delete();
        $this->assertSame(0, PatientResult::count());
    }

    public function test_front_desk_cannot_upload_or_view_report_contents(): void
    {
        $desk = $this->staff(Role::FrontDesk);
        $alice = $this->patient('alice@example.test');
        $r = $this->report($alice, 'Secret biopsy report');

        $this->actingAs($desk)->post(route('admin.results.store', $alice), ['kind' => 'lab', 'title' => 'x', 'file' => UploadedFile::fake()->createWithContent('x.pdf', SamplePdf::make('x', ['x']))])->assertForbidden();
        $this->actingAs($desk)->get(route('admin.results.show', $r))->assertForbidden();
        $this->actingAs($desk)->get(route('admin.patients.show', $alice))->assertOk()->assertDontSee('Secret biopsy report')->assertSee('1 report');

        $this->actingAs($this->staff(Role::Lab))->get(route('admin.patients.show', $alice))->assertSee('Secret biopsy report');
        $this->actingAs($this->staff(Role::Lab))->get(route('admin.results.show', $r))->assertOk();
    }

    public function test_sign_in_failure_message_is_the_same_for_unknown_wrong_and_unactivated_accounts(): void
    {
        $this->patient('alice@example.test');
        $this->patient('carl@example.test', active: false);
        $messages = [];
        foreach ([['alice@example.test', 'wrong password!'], ['nobody@example.test', 'whatever123'], ['carl@example.test', '']] as [$email, $password]) {
            $messages[] = $this->post(route('portal.login'), ['email' => $email, 'password' => $password ?: 'anything123'])
                ->assertSessionHasErrors('email')->getSession()?->get('errors')?->first('email');
            $this->assertGuest('patient');
        }
        $this->assertCount(1, array_unique($messages));
    }

    public function test_patient_can_sign_in_and_out(): void
    {
        $alice = $this->patient('alice@example.test');
        $this->post(route('portal.login'), ['email' => 'ALICE@example.test', 'password' => 'correct horse battery'])->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($alice, 'patient');
        $this->assertGuest('web');
        $this->post(route('portal.logout'))->assertRedirect(route('portal.login'));
        $this->assertGuest('patient');
    }

    public function test_sign_in_is_rate_limited(): void
    {
        $this->patient('alice@example.test');
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('portal.login'), ['email' => 'alice@example.test', 'password' => 'nope-'.$i]);
        }
        $this->post(route('portal.login'), ['email' => 'alice@example.test', 'password' => 'correct horse battery'])->assertTooManyRequests();
    }

    public function test_there_is_no_public_sign_up(): void
    {
        $this->get('/results/register')->assertNotFound();
        $this->post('/results/register', ['email' => 'x@example.test'])->assertStatus(404);
        $this->get(route('portal.login'))->assertDontSee('Create an account');
    }

    public function test_forgot_password_never_reveals_whether_an_email_is_registered(): void
    {
        $alice = $this->patient('alice@example.test');
        $carl = $this->patient('carl@example.test', active: false);

        $a = $this->post(route('portal.password.email'), ['email' => 'alice@example.test'])->getSession()?->get('status');
        $b = $this->post(route('portal.password.email'), ['email' => 'nobody@example.test'])->getSession()?->get('status');
        $c = $this->post(route('portal.password.email'), ['email' => 'carl@example.test'])->getSession()?->get('status');

        $this->assertSame($a, $b);
        $this->assertSame($a, $c);
        Notification::assertSentTo($alice, PatientResetPassword::class);
        Notification::assertSentTo($carl, PortalInvitation::class);
        Notification::assertSentTimes(PatientResetPassword::class, 1);
    }

    public function test_reset_link_changes_the_password(): void
    {
        $alice = $this->patient('alice@example.test');
        $this->post(route('portal.password.email'), ['email' => 'alice@example.test']);
        $token = null;
        Notification::assertSentTo($alice, PatientResetPassword::class, function (PatientResetPassword $n) use (&$token) {
            $token = $n->token;

            return true;
        });

        $this->post(route('portal.password.update'), ['token' => $token, 'email' => 'alice@example.test', 'password' => 'a brand new secret', 'password_confirmation' => 'a brand new secret'])
            ->assertRedirect(route('portal.login'));
        $this->post(route('portal.login'), ['email' => 'alice@example.test', 'password' => 'a brand new secret'])->assertRedirect(route('portal.dashboard'));
    }

    public function test_invitation_activates_the_account_once_and_checks_date_of_birth(): void
    {
        $desk = $this->staff(Role::FrontDesk);
        $this->actingAs($desk)->post(route('admin.patients.store'), [
            'first_name' => 'Dana', 'last_name' => 'Ng', 'email' => 'Dana@Example.test', 'date_of_birth' => '1991-07-15', 'send_invite' => 1,
        ])->assertRedirect();
        $dana = Patient::where('email', 'dana@example.test')->sole();

        $url = null;
        Notification::assertSentTo($dana, PortalInvitation::class, function (PortalInvitation $n) use ($dana, &$url) {
            $url = $n->toMail($dana)->actionUrl;

            return true;
        });
        auth('web')->logout();

        $this->get($url)->assertOk()->assertSee('Activate your results portal');
        // Wrong date of birth: refused, account stays inactive.
        $this->post($url, ['date_of_birth' => '1990-01-01', 'password' => 'long enough pass', 'password_confirmation' => 'long enough pass'])->assertSessionHasErrors('date_of_birth');
        $this->assertFalse($dana->fresh()?->isActivated());

        $this->post($url, ['date_of_birth' => '1991-07-15', 'password' => 'long enough pass', 'password_confirmation' => 'long enough pass'])->assertRedirect(route('portal.dashboard'));
        $this->assertTrue($dana->fresh()?->isActivated());
        $this->assertAuthenticatedAs($dana->fresh(), 'patient');

        // The link is single-use.
        auth('patient')->logout();
        $this->post($url, ['date_of_birth' => '1991-07-15', 'password' => 'another password', 'password_confirmation' => 'another password'])->assertForbidden();
    }

    public function test_tampered_expired_and_replaced_invitation_links_are_refused(): void
    {
        $p = $this->patient('erin@example.test', active: false, dob: null);
        $p->forceFill(['invited_at' => now()])->save();
        $url = PortalInvitation::url($p);

        $this->get(str_replace('patient='.$p->id, 'patient='.$p->id, $url).'x')->assertForbidden();
        $this->get(route('portal.invite', ['patient' => $p->id, 'v' => $p->invited_at?->timestamp]))->assertForbidden();

        // Someone else's id with a valid-looking signature from another patient's link.
        $other = $this->patient('fay@example.test', active: false, dob: null);
        $other->forceFill(['invited_at' => now()])->save();
        $this->get(preg_replace('#/welcome/\d+#', '/welcome/'.$other->id, $url))->assertForbidden();

        // A newer invitation retires the older link.
        $this->travel(5)->seconds();
        $p->forceFill(['invited_at' => now()])->save();
        $this->get($url)->assertForbidden();
        $this->get(PortalInvitation::url($p->fresh()))->assertOk();

        // Expired after 72 hours.
        $fresh = PortalInvitation::url($p->fresh());
        $this->travel(PortalInvitation::HOURS + 1)->hours();
        $this->get($fresh)->assertForbidden();
    }

    public function test_activated_patients_cannot_be_re_invited_and_old_links_redirect(): void
    {
        $alice = $this->patient('alice@example.test');
        $url = URL::temporarySignedRoute('portal.invite', now()->addHour(), ['patient' => $alice->id, 'v' => $alice->invited_at?->timestamp]);
        $this->get($url)->assertRedirect(route('portal.login'));

        $this->actingAs($this->staff(Role::FrontDesk))->post(route('admin.patients.invite', $alice))->assertSessionHas('status');
        Notification::assertNotSentTo($alice, PortalInvitation::class);
    }

    public function test_deleting_a_report_removes_the_file(): void
    {
        $alice = $this->patient('alice@example.test');
        $r = $this->report($alice);
        $this->actingAs($this->staff(Role::Lab))->delete(route('admin.results.destroy', $r))->assertRedirect();
        Storage::disk('local')->assertMissing($r->file_path);
        $this->assertModelMissing($r);
    }

    public function test_portal_pages_are_never_cached(): void
    {
        $this->actingAs($this->patient('alice@example.test'), 'patient')->get(route('portal.dashboard'))
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Cache-Control');
        $this->assertStringContainsString('no-store', (string) $this->get(route('portal.login'))->headers->get('Cache-Control'));
    }
}
