<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentService;
use App\Enums\AppointmentStatus;
use App\Models\AppointmentRequest;
use App\Models\Doctor;
use App\Notifications\AppointmentReceived;
use App\Notifications\NewAppointmentRequest;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function create(Request $request): View
    {
        return view('pages.appointments', [
            'service' => AppointmentService::tryFrom(input_string($request->query('service'))),
            'department' => department(input_string($request->query('department'))) ? input_string($request->query('department')) : null,
            'doctors' => Doctor::published()->orderBy('name')->get(['id', 'name', 'department']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: bots fill every field. Pretend it worked and keep nothing.
        if ($request->filled('website')) {
            $request->session()->put('appointment_reference', AppointmentRequest::REFERENCE_PREFIX.'-'.now()->format('y').'-00000');

            return to_route('appointments.received');
        }

        $request->merge([
            'email' => $request->filled('email') ? strtolower(input_string($request->input('email'))) : null,
            'returning_patient' => $request->boolean('returning_patient'),
            'has_referral' => $request->boolean('has_referral'),
        ]);

        $data = $request->validate([
            'service' => ['required', Rule::enum(AppointmentService::class)],
            'department' => ['nullable', 'required_if:service,specialist', Rule::in(array_keys(department_options()))],
            'doctor_id' => ['nullable', 'integer', Rule::exists('doctors', 'id')->where('is_published', true)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'returning_patient' => ['boolean'],
            'preferred_date' => ['required', 'date', 'after_or_equal:today', 'before:'.today()->addMonths(6)->toDateString()],
            'preferred_window' => ['required', Rule::in(array_keys(AppointmentRequest::WINDOWS))],
            'alternate_date' => ['nullable', 'date', 'after_or_equal:today', 'different:preferred_date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'has_referral' => ['boolean'],
            'consent' => ['accepted'],
        ], [
            'phone.regex' => 'Enter a phone number we can call, with at least 7 digits.',
            'department.required_if' => 'Choose which specialist clinic you need.',
            'preferred_date.after_or_equal' => 'Choose today or a later date.',
            'preferred_date.before' => 'We can only take requests up to six months ahead.',
            'consent.accepted' => 'Please confirm that we may contact you about this request.',
        ], [
            'preferred_date' => 'preferred date', 'preferred_window' => 'time of day', 'first_name' => 'first name', 'last_name' => 'last name',
        ]);

        unset($data['consent']);
        $appointment = AppointmentRequest::create($data + ['status' => AppointmentStatus::Requested]);

        Notify::office(new NewAppointmentRequest($appointment));
        Notify::address($appointment->email, new AppointmentReceived($appointment));

        // Kept (not flashed) so a refresh of the confirmation page still shows the number.
        $request->session()->put('appointment_reference', $appointment->reference);
        $request->session()->put('appointment_service', $appointment->service->value);

        return to_route('appointments.received');
    }

    public function received(Request $request): View|RedirectResponse
    {
        $reference = $request->session()->get('appointment_reference');

        return $reference ? view('pages.received', [
            'title' => 'Request received',
            'reference' => $reference,
            'lead' => 'Your appointment request is in. It is not booked yet: our front desk will confirm a time, or offer the nearest available one.',
            'steps' => [
                'We check the clinic or department diary for your preferred day.',
                'We confirm by email (if you gave one) or call you on the number you gave.',
                'Bring a photo ID, and any referral or request form, on the day.',
            ],
            'extKey' => AppointmentService::tryFrom((string) $request->session()->get('appointment_service'))?->extensionKey() ?? 'ext_clinics',
        ]) : to_route('appointments.create');
    }
}
