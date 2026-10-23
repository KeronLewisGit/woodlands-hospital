<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmploymentType;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\JobPosting;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JobPostingController extends Controller
{
    public function index(): View
    {
        return view('admin.jobs.index', [
            'jobs' => JobPosting::withCount([
                'applications',
                'applications as new_count' => fn ($q) => $q->where('status', 'new'),
            ])->orderByRaw("case status when 'open' then 0 when 'draft' then 1 else 2 end")->latest()->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.jobs.form', ['job' => new JobPosting(['status' => JobStatus::Draft, 'location' => 'Georgetown', 'openings' => 1])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = Slug::unique(JobPosting::class, $data['title'], 'job');
        $job = JobPosting::create($data);

        return to_route('admin.jobs.edit', $job)->with('status', 'Job created.');
    }

    public function edit(JobPosting $job): View
    {
        return view('admin.jobs.form', ['job' => $job]);
    }

    public function update(Request $request, JobPosting $job): RedirectResponse
    {
        $job->update($this->validated($request));

        return back()->with('status', 'Job saved.');
    }

    public function destroy(JobPosting $job): RedirectResponse
    {
        if ($job->applications()->exists()) {
            $job->update(['status' => JobStatus::Closed]);

            return to_route('admin.jobs.index')->with('status', 'That job has applicants, so it was closed instead of deleted.');
        }
        $job->delete();

        return to_route('admin.jobs.index')->with('status', 'Job deleted.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::enum(JobCategory::class)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'status' => ['required', Rule::enum(JobStatus::class)],
            'location' => ['required', 'string', 'max:120'],
            'openings' => ['required', 'integer', 'min:1', 'max:500'],
            'ends_on' => ['nullable', 'date'],
            'summary' => ['required', 'string', 'max:600'],
            'duties' => ['nullable', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
