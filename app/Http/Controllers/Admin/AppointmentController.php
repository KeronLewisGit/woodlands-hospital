<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentService;
use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\AppointmentRequest;
use App\Notifications\AppointmentUpdated;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'status' => AppointmentStatus::tryFrom(input_string($request->query('status')))?->value,
            'service' => AppointmentService::tryFrom(input_string($request->query('service')))?->value,
            'q' => input_string($request->query('q')) ?: null,
        ];
        $counts = AppointmentRequest::filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        // Waiting requests: soonest preferred day first (that's the one about to be missed). Everything else: newest first.
        $query = AppointmentRequest::filter($filters);
        $filters['status'] === AppointmentStatus::Requested->value
            ? $query->orderBy('preferred_date')->orderBy('created_at')
            : $query->latest()->orderByDesc('id');

        return view('admin.appointments.index', [
            'appointments' => $query->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
        ]);
    }

    public function show(AppointmentRequest $appointment): View
    {
        $appointment->load('doctor', 'handler');

        return view('admin.appointments.show', [
            'a' => $appointment,
            'history' => AppointmentRequest::where('id', '!=', $appointment->id)
                ->where(fn ($q) => $q->where('phone_digits', $appointment->phone_digits)->when($appointment->email, fn ($w) => $w->orWhere('email', $appointment->email)))
                ->latest()->limit(5)->get(),
        ]);
    }

    public function update(Request $request, AppointmentRequest $appointment): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['confirm', 'reschedule', 'cancel', 'notes'])],
            // Cancelling or saving notes ignores the (hidden) date and time, so a request whose
            // preferred day has already passed can still be cancelled.
            'date' => ['exclude_if:action,cancel,notes', 'required_if:action,confirm,reschedule', 'date', 'after_or_equal:today'],
            'time' => ['exclude_if:action,cancel,notes', 'required_if:action,confirm,reschedule', 'date_format:H:i'],
            'message_to_patient' => ['nullable', 'string', 'max:1000'],
            'staff_notes' => ['nullable', 'string', 'max:5000'],
            'notify' => ['boolean'],
        ], [
            'date.required_if' => 'Pick the appointment date.',
            'time.required_if' => 'Pick the appointment time.',
        ]);

        $before = $appointment->status;
        if (array_key_exists('staff_notes', $data)) {
            $appointment->staff_notes = $data['staff_notes'];
        }

        if ($data['action'] !== 'notes') {
            $status = match ($data['action']) {
                'confirm' => AppointmentStatus::Confirmed,
                'reschedule' => AppointmentStatus::Rescheduled,
                default => AppointmentStatus::Cancelled,
            };
            $appointment->forceFill([
                'status' => $status,
                'scheduled_for' => $status === AppointmentStatus::Cancelled ? $appointment->scheduled_for : Carbon::parse($data['date'].' '.$data['time']),
                'message_to_patient' => $data['message_to_patient'] ?? null,
                'handled_by' => $request->user()?->id,
                'status_changed_at' => now(),
            ]);
        }
        $appointment->save();

        $emailed = false;
        if ($data['action'] !== 'notes' && $request->boolean('notify', true) && $appointment->email) {
            Notify::address($appointment->email, new AppointmentUpdated($appointment));
            $emailed = true;
        }

        $verb = match ($data['action']) {
            'confirm' => 'confirmed for '.$appointment->scheduled_for?->format('D j M, g:i a'),
            'reschedule' => 'offered '.$appointment->scheduled_for?->format('D j M, g:i a'),
            'cancel' => 'cancelled',
            default => 'notes saved',
        };
        $tail = $data['action'] === 'notes' ? '' : ($emailed ? ' The patient has been emailed.' : ' No email on file: please phone '.format_phone($appointment->phone).'.');

        // After handling one request, go straight to the next waiting one.
        $next = $before === AppointmentStatus::Requested
            ? AppointmentRequest::awaiting()->orderBy('preferred_date')->orderBy('created_at')->value('id')
            : null;

        return ($next ? to_route('admin.appointments.show', $next) : back())->with('status', "{$appointment->reference} {$verb}.{$tail}");
    }
}
