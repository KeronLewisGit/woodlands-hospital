<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\JobCategory;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'status' => ApplicationStatus::tryFrom(input_string($request->query('status')))?->value,
            'category' => JobCategory::tryFrom(input_string($request->query('category')))?->value,
            'job' => ctype_digit(input_string($request->query('job'))) ? input_string($request->query('job')) : null,
            'q' => input_string($request->query('q')) ?: null,
        ];

        return view('admin.applications.index', [
            'applications' => JobApplication::with('jobPosting')->filter($filters)->latest()->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => JobApplication::filter(array_merge($filters, ['status' => null]))->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'jobs' => JobPosting::orderBy('title')->get(['id', 'title']),
        ]);
    }

    public function show(JobApplication $application): View
    {
        return view('admin.applications.show', ['application' => $application->load('jobPosting')]);
    }

    public function update(Request $request, JobApplication $application): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', Rule::enum(ApplicationStatus::class)],
            'staff_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);
        if (isset($data['status'])) {
            $application->moveTo(ApplicationStatus::from($data['status']));
        }
        if (array_key_exists('staff_notes', $data)) {
            $application->staff_notes = $data['staff_notes'];
        }
        $application->save();

        return back()->with('status', "{$application->reference} saved.");
    }

    /** CVs are private: streamed to signed-in admins only, never linked publicly. */
    public function cv(JobApplication $application): StreamedResponse
    {
        abort_unless($application->cv_path && Storage::disk('local')->exists($application->cv_path), 404);

        return Storage::disk('local')->download(
            $application->cv_path,
            Str::slug($application->full_name).'-'.$application->reference.'.'.pathinfo($application->cv_path, PATHINFO_EXTENSION),
        );
    }
}
