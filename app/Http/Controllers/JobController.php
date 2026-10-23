<?php

namespace App\Http\Controllers;

use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Models\JobPosting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobController extends Controller
{
    public function index(Request $request): View
    {
        $all = JobPosting::open()->orderBy('category')->latest()->get();
        $type = JobCategory::tryFrom(input_string($request->query('type')));

        return view('pages.jobs.index', [
            'jobs' => $type ? $all->filter(fn (JobPosting $j) => $j->category === $type)->values() : $all,
            'active' => $type,
            'types' => $all->countBy(fn (JobPosting $j) => $j->category->value)
                ->map(fn (int $count, string $value) => ['type' => JobCategory::from($value), 'count' => $count])->values(),
            'total' => $all->count(),
        ]);
    }

    public function show(JobPosting $job): View
    {
        abort_if($job->status === JobStatus::Draft, 404);

        return view('pages.jobs.show', ['job' => $job, 'isOpen' => JobPosting::open()->whereKey($job->id)->exists()]);
    }
}
