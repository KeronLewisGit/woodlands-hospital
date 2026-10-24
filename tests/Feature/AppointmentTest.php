<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\Role;
use App\Models\AppointmentRequest;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AppointmentReceived;
use App\Notifications\AppointmentUpdated;
use App\Notifications\NewAppointmentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AppointmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Setting::setMany(['notify_email' => 'frontdesk@example.test']);
    }

    /** @return array<string, mixed> */
    private function valid(array $overrides = []): array
    {
        return array_merge([
            'service' => 'radiology', 'first_name' => 'Anita', 'last_name' => 'Persaud', 'phone' => '600-1234',
            'email' => 'Anita@Example.test', 'preferred_date' => today()->addDays(3)->toDateString(), 'preferred_window' => 'morning',
            'reason' => 'Abdominal ultrasound', 'has_referral' => '1', 'consent' => '1',
        ], $overrides);
    }

    public function test_patient_can_request_an_appointment_and_gets_a_reference(): void
    {
        $this->get(route('appointments.create'))->assertOk()->assertSee('Request an appointment');
        $this->post(route('appointments.store'), $this->valid())->assertRedirect(route('appointments.received'));

        $a = AppointmentRequest::sole();
        $this->assertMatchesRegularExpression('/^WA-\d{2}-\d{5}$/', $a->reference);
        $this->assertSame(AppointmentStatus::Requested, $a->status);
        $this->assertSame('anita@example.test', $a->email);
        $this->assertSame('6001234', $a->phone_digits);
        $this->assertTrue($a->has_referral);

        $this->get(route('appointments.received'))->assertOk()->assertSee($a->reference)->assertSee('ext');

        Notification::assertSentTo(new AnonymousNotifiable, NewAppointmentRequest::class, fn ($n, $c, $notifiable) => $notifiable->routes['mail'] === ['frontdesk@example.test']);
        Notification::assertSentTo(new AnonymousNotifiable, AppointmentReceived::class, function (AppointmentReceived $n, $c, $notifiable) use ($a) {
            $mail = $n->toMail($notifiable);

            return $notifiable->routes['mail'] === 'anita@example.test' && str_contains($mail->subject, $a->reference)
                && str_contains(implode(' ', $mail->introLines), 'not yet a booking');
        });
    }

    public function test_no_email_means_no_patient_email_but_the_office_still_hears(): void
    {
        $this->post(route('appointments.store'), $this->valid(['email' => '']))->assertRedirect();
        Notification::assertSentTimes(NewAppointmentRequest::class, 1);
        Notification::assertNotSentTo(new AnonymousNotifiable, AppointmentReceived::class);
    }

    public function test_validation_rules(): void
    {
        // Many posts from one phone in a minute would hit the per-phone form limit; that's tested separately.
        $this->withoutMiddleware(ThrottleRequests::class);
        $this->post(route('appointments.store'), $this->valid(['preferred_date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('preferred_date');
        $this->post(route('appointments.store'), $this->valid(['preferred_date' => today()->addYear()->toDateString()]))->assertSessionHasErrors('preferred_date');
        $this->post(route('appointments.store'), $this->valid(['service' => 'specialist']))->assertSessionHasErrors('department');
        $this->post(route('appointments.store'), $this->valid(['service' => 'surgery']))->assertSessionHasErrors('service');
        $this->post(route('appointments.store'), $this->valid(['phone' => '12']))->assertSessionHasErrors('phone');
        $this->post(route('appointments.store'), $this->valid(['consent' => null]))->assertSessionHasErrors('consent');
        $this->post(route('appointments.store'), $this->valid(['preferred_window' => 'midnight']))->assertSessionHasErrors('preferred_window');
        $this->assertSame(0, AppointmentRequest::count());

        $this->post(route('appointments.store'), $this->valid(['service' => 'specialist', 'department' => 'cardiology']))->assertSessionHasNoErrors();
        $this->assertSame('cardiology', AppointmentRequest::sole()->department);
    }

    public function test_one_phone_is_limited_to_five_requests_a_minute(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('appointments.store'), $this->valid())->assertRedirect();
        }
        $this->post(route('appointments.store'), $this->valid())->assertTooManyRequests();
        $this->post(route('appointments.store'), $this->valid(['phone' => '600-5555']))->assertRedirect();
    }

    public function test_honeypot_saves_nothing(): void
    {
        $this->post(route('appointments.store'), $this->valid(['website' => 'spam.example']))->assertRedirect(route('appointments.received'));
        $this->assertSame(0, AppointmentRequest::count());
        Notification::assertNothingSent();
    }

    public function test_received_page_needs_a_submission(): void
    {
        $this->get(route('appointments.received'))->assertRedirect(route('appointments.create'));
    }

    public function test_front_desk_confirms_and_patient_is_emailed_then_goes_to_next_request(): void
    {
        $desk = User::factory()->create(['role' => Role::FrontDesk]);
        $this->post(route('appointments.store'), $this->valid());
        $this->post(route('appointments.store'), $this->valid(['first_name' => 'Ravi', 'phone' => '600-9999', 'preferred_date' => today()->addDays(5)->toDateString()]));
        [$first, $second] = AppointmentRequest::orderBy('id')->get()->all();
        Notification::fake();

        $this->actingAs($desk)->get(route('admin.appointments.show', $first))->assertOk()->assertSee('Confirm this request');
        $date = today()->addDays(3)->toDateString();
        $this->actingAs($desk)->patch(route('admin.appointments.update', $first), ['action' => 'confirm', 'date' => $date, 'time' => '09:30', 'message_to_patient' => 'Drink water before the scan.', 'notify' => '1'])
            ->assertRedirect(route('admin.appointments.show', $second));

        $first->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $first->status);
        $this->assertSame($date.' 09:30', $first->scheduled_for?->format('Y-m-d H:i'));
        $this->assertTrue($first->load('handler')->handler?->is($desk));
        Notification::assertSentTo(new AnonymousNotifiable, AppointmentUpdated::class, function (AppointmentUpdated $n, $c, $notifiable) {
            $mail = $n->toMail($notifiable);

            return str_contains($mail->subject, 'confirmed') && str_contains(implode(' ', $mail->introLines), 'Drink water');
        });
    }

    public function test_reschedule_and_cancel(): void
    {
        $desk = User::factory()->create(['role' => Role::FrontDesk]);
        $this->post(route('appointments.store'), $this->valid());
        $a = AppointmentRequest::sole();

        $this->actingAs($desk)->patch(route('admin.appointments.update', $a), ['action' => 'reschedule', 'date' => today()->addDays(6)->toDateString(), 'time' => '14:00']);
        $this->assertSame(AppointmentStatus::Rescheduled, $a->fresh()?->status);

        $this->actingAs($desk)->patch(route('admin.appointments.update', $a), ['action' => 'confirm'])->assertSessionHasErrors(['date', 'time']);

        $this->actingAs($desk)->patch(route('admin.appointments.update', $a), ['action' => 'cancel']);
        $this->assertSame(AppointmentStatus::Cancelled, $a->fresh()?->status);
        Notification::assertSentTimes(AppointmentUpdated::class, 2);
    }

    public function test_staff_can_skip_the_email_and_save_notes_only(): void
    {
        $desk = User::factory()->create(['role' => Role::FrontDesk]);
        $this->post(route('appointments.store'), $this->valid());
        $a = AppointmentRequest::sole();
        Notification::fake();

        $this->actingAs($desk)->patch(route('admin.appointments.update', $a), ['action' => 'notes', 'staff_notes' => 'Called, no answer']);
        $this->assertSame('Called, no answer', $a->fresh()?->staff_notes);
        $this->assertSame(AppointmentStatus::Requested, $a->fresh()?->status);

        $this->actingAs($desk)->patch(route('admin.appointments.update', $a), ['action' => 'confirm', 'date' => today()->addDay()->toDateString(), 'time' => '08:00', 'notify' => '0']);
        Notification::assertNothingSent();
    }

    public function test_a_request_whose_day_has_passed_can_still_be_cancelled(): void
    {
        $desk = User::factory()->create(['role' => Role::FrontDesk]);
        $this->post(route('appointments.store'), $this->valid());
        $a = AppointmentRequest::sole();
        $a->forceFill(['preferred_date' => today()->subDays(2)])->save();

        // The form still posts its hidden (suggested, now past) date and time when "Can't book" is chosen.
        $this->actingAs($desk)->patch(route('admin.appointments.update', $a), [
            'action' => 'cancel', 'date' => today()->subDays(2)->toDateString(), 'time' => '09:00', 'notify' => '0',
        ])->assertSessionHasNoErrors();
        $this->assertSame(AppointmentStatus::Cancelled, $a->fresh()?->status);
    }

    public function test_staff_notes_can_be_cleared(): void
    {
        $desk = User::factory()->create(['role' => Role::FrontDesk]);
        $this->post(route('appointments.store'), $this->valid());
        $a = AppointmentRequest::sole();
        $a->forceFill(['staff_notes' => 'Old note'])->save();

        $this->actingAs($desk)->patch(route('admin.appointments.update', $a), ['action' => 'notes', 'staff_notes' => '']);
        $this->assertNull($a->fresh()?->staff_notes);

        // The decision form has no notes field, so deciding keeps them.
        $a->forceFill(['staff_notes' => 'Keep me'])->save();
        $this->actingAs($desk)->patch(route('admin.appointments.update', $a), ['action' => 'cancel', 'notify' => '0']);
        $this->assertSame('Keep me', $a->fresh()?->staff_notes);
    }

    public function test_markdown_typed_into_a_name_never_becomes_a_link_in_email(): void
    {
        $this->post(route('appointments.store'), $this->valid(['first_name' => '[Reset your password](https://evil.example/login)']));
        $a = AppointmentRequest::sole();

        $html = (string) (new AppointmentReceived($a))->toMail(new AnonymousNotifiable)->render();
        $this->assertStringNotContainsString('href="https://evil.example', $html);
        $this->assertStringContainsString('[Reset your password](https://evil.example/login)', html_entity_decode($html));
    }

    public function test_lab_staff_cannot_handle_appointments(): void
    {
        $lab = User::factory()->create(['role' => Role::Lab]);
        $this->actingAs($lab)->get(route('admin.appointments.index'))->assertForbidden();
    }

    public function test_dashboard_leads_with_requests_waiting(): void
    {
        $desk = User::factory()->create(['role' => Role::FrontDesk]);
        $this->post(route('appointments.store'), $this->valid(['preferred_date' => today()->toDateString()]));
        $this->post(route('appointments.store'), $this->valid(['phone' => '600-7777']));

        $this->actingAs($desk)->get(route('admin.dashboard'))->assertOk()
            ->assertSeeInOrder(['Appointment requests waiting to be confirmed', '2', 'requests', '1 want today or tomorrow']);
    }
}
