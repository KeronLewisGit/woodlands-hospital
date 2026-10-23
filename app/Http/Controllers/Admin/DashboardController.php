<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentService;
use App\Enums\AppointmentStatus;
use App\Enums\RefillStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AppointmentRequest;
use App\Models\JobApplication;
use App\Models\Message;
use App\Models\Patient;
use App\Models\PatientResult;
use App\Models\RefillRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const int CHART_DAYS = 30;

    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return $user->role === Role::Lab ? $this->lab() : $this->frontDesk($user);
    }

    private function frontDesk(User $user): View
    {
        $now = now();
        $weekAgo = $now->copy()->subDays(7);
        $chartStart = today()->subDays(self::CHART_DAYS - 1);

        $awaiting = AppointmentRequest::awaiting();
        $oldest = (clone $awaiting)->min('created_at');

        $recent = AppointmentRequest::where('created_at', '>=', $chartStart)->get(['created_at', 'service']);
        $byDate = $recent->countBy(fn (AppointmentRequest $a) => $a->created_at->toDateString());
        $daily = collect(range(0, self::CHART_DAYS - 1))->map(function (int $i) use ($chartStart, $byDate) {
            $day = $chartStart->copy()->addDays($i);

            return ['date' => $day, 'count' => (int) ($byDate[$day->toDateString()] ?? 0)];
        });
        $byService = $recent->countBy(fn (AppointmentRequest $a) => $a->service->value);

        $tiles = [
            ['label' => 'Requests, last 7 days', 'value' => AppointmentRequest::where('created_at', '>=', $weekAgo)->count(),
                'previous' => AppointmentRequest::whereBetween('created_at', [$now->copy()->subDays(14), $weekAgo])->count(), 'period' => 'vs previous 7 days',
                'href' => route('admin.appointments.index')],
            ['label' => 'Refills to prepare', 'value' => RefillRequest::whereIn('status', [RefillStatus::New, RefillStatus::Preparing])->count(),
                'note' => RefillRequest::where('status', RefillStatus::Ready)->count().' ready, waiting for collection', 'href' => route('admin.refills.index')],
            ['label' => 'Messages to answer', 'value' => Message::open()->count(),
                'note' => 'From the contact form', 'href' => route('admin.messages.index')],
        ];
        if ($user->role === Role::Admin) {
            $tiles[] = ['label' => 'New job applications', 'value' => JobApplication::awaitingReview()->count(),
                'note' => 'Waiting for review', 'href' => route('admin.applications.index', ['status' => 'new'])];
        } else {
            $tiles[] = ['label' => 'Portal invitations pending', 'value' => Patient::whereNull('activated_at')->whereNotNull('invited_at')->count(),
                'note' => 'Patients who have not set a password', 'href' => route('admin.patients.index')];
        }

        return view('admin.dashboard', [
            'awaitingCount' => $awaiting->count(),
            'urgentCount' => (clone $awaiting)->whereDate('preferred_date', '<=', today()->addDay())->count(),
            'oldestAwaiting' => $oldest ? Carbon::parse($oldest) : null,
            'today' => AppointmentRequest::whereIn('status', [AppointmentStatus::Confirmed, AppointmentStatus::Rescheduled])
                ->whereDate('scheduled_for', today())->orderBy('scheduled_for')->get(),
            'tiles' => $tiles,
            'daily' => $daily,
            'dailyMax' => max(1, (int) $daily->max('count')),
            'byService' => collect(AppointmentService::cases())
                ->map(fn (AppointmentService $s) => ['label' => $s->label(), 'value' => $s->value, 'count' => (int) ($byService[$s->value] ?? 0)])
                ->sortByDesc('count')->values(),
            'chartDays' => self::CHART_DAYS,
        ]);
    }

    private function lab(): View
    {
        $unopened = PatientResult::whereNull('first_viewed_at')->where('created_at', '<=', now()->subDays(3));

        return view('admin.dashboard-lab', [
            'unopenedCount' => $unopened->count(),
            'unopened' => (clone $unopened)->with('patient')->oldest()->limit(8)->get(),
            'tiles' => [
                ['label' => 'Reports published, last 7 days', 'value' => PatientResult::where('created_at', '>=', now()->subDays(7))->count(),
                    'previous' => PatientResult::whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count(), 'period' => 'vs previous 7 days', 'href' => route('admin.patients.index')],
                ['label' => 'Portal accounts active', 'value' => Patient::whereNotNull('activated_at')->count(),
                    'note' => Patient::whereNull('activated_at')->count().' not activated yet', 'href' => route('admin.patients.index')],
                ['label' => 'Reports opened by patients', 'value' => PatientResult::whereNotNull('first_viewed_at')->count(),
                    'note' => 'of '.PatientResult::count().' published', 'href' => route('admin.patients.index')],
            ],
            'latest' => PatientResult::with('patient', 'uploader')->latest()->limit(8)->get(),
        ]);
    }
}
