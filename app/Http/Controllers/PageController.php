<?php

namespace App\Http\Controllers;

use App\Models\ClinicSession;
use App\Models\Doctor;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'openJobs' => JobPosting::open()->count(),
            'doctorCount' => Doctor::published()->count(),
        ]);
    }

    public function services(): View
    {
        return view('pages.services', [
            'doctorCounts' => Doctor::published()->selectRaw('department, count(*) as total')->groupBy('department')->pluck('total', 'department'),
        ]);
    }

    public function clinics(Request $request): View
    {
        $sessions = ClinicSession::active()->with('doctor')->orderBy('weekday')->orderBy('starts_at')->get();
        $dept = input_string($request->query('department'));
        $filtered = $dept !== '' && department($dept) ? $sessions->where('department', $dept)->values() : $sessions;

        return view('pages.clinics', [
            'sessions' => $filtered,
            'byDay' => $filtered->groupBy('weekday'),
            'departments' => $sessions->pluck('department')->unique()->mapWithKeys(fn (string $d) => [$d => department($d)['name'] ?? $d])->sort(),
            'active' => department($dept) ? $dept : null,
        ]);
    }

    public function doctors(Request $request): View
    {
        $all = Doctor::published()->orderBy('sort')->orderBy('name')->get();
        $dept = input_string($request->query('department'));
        $q = mb_strtolower(input_string($request->query('q')));
        $doctors = $all
            ->when(department($dept) !== null, fn ($c) => $c->where('department', $dept))
            ->when($q !== '', fn ($c) => $c->filter(fn (Doctor $d) => str_contains(mb_strtolower($d->name.' '.$d->title.' '.$d->departmentName()), $q)))
            ->values();

        return view('pages.doctors', [
            'doctors' => $doctors,
            'total' => $all->count(),
            'departments' => $all->countBy('department')->mapWithKeys(fn (int $n, string $d) => [$d => ['name' => department($d)['name'] ?? $d, 'count' => $n]])->sortBy('name'),
            'active' => department($dept) ? $dept : null,
            'q' => input_string($request->query('q')),
        ]);
    }

    public function emergency(): View
    {
        return view('pages.emergency');
    }

    public function patients(): View
    {
        return view('pages.patients');
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function credits(): View
    {
        return view('pages.credits');
    }

    public function sitemap(): Response
    {
        $urls = collect(['home', 'services', 'clinics', 'doctors', 'emergency', 'appointments.create', 'pharmacy', 'patients', 'portal.login', 'about', 'jobs.index', 'contact', 'privacy', 'credits'])
            ->map(fn (string $name) => ['loc' => route($name), 'lastmod' => null])
            ->concat(JobPosting::open()->get()->map(fn (JobPosting $job) => ['loc' => route('jobs.show', $job), 'lastmod' => $job->updated_at->toAtomString()]));

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
