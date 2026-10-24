<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Enums\JobCategory;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Notifications\NewSubmission;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function create(Request $request): View
    {
        $jobs = JobPosting::open()->orderBy('title')->get();

        return view('pages.apply', ['jobs' => $jobs, 'selected' => $jobs->firstWhere('slug', input_string($request->query('job')))]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            $request->session()->put('application_reference', JobApplication::REFERENCE_PREFIX.'-'.now()->format('y').'-00000');

            return to_route('apply.received');
        }

        // A chosen job decides the category, with or without JavaScript.
        $jobId = input_string($request->input('job_posting_id'));
        $job = ctype_digit($jobId) ? JobPosting::open()->find((int) $jobId) : null;
        $request->merge(array_filter([
            'category' => $job?->category->value,
            'email' => strtolower(input_string($request->input('email'))),
        ]));

        $data = $request->validate([
            'job_posting_id' => ['nullable', 'integer', Rule::exists('job_postings', 'id')->where('status', 'open')],
            'category' => ['required', Rule::enum(JobCategory::class)],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'string', 'max:30', phone_rule()],
            'email' => ['required', 'email:rfc', 'max:255'],
            'registration' => ['nullable', 'string', 'max:120'],
            'years_experience' => ['nullable', 'integer', 'min:0', 'max:60'],
            'message' => ['nullable', 'string', 'max:3000'],
            'cv' => ['required', 'file', 'max:5120', 'mimes:'.implode(',', JobApplication::CV_TYPES)],
            'consent' => ['accepted'],
        ], [
            'category.required' => 'Choose the kind of role you are applying for.',
            'phone.regex' => 'Enter a phone number with at least 7 digits.',
            'cv.required' => 'Attach your CV (PDF or Word).',
            'cv.max' => 'Your CV must be 5 MB or smaller.',
            'cv.mimes' => 'Your CV must be a PDF or Word document.',
            'cv.uploaded' => 'Your CV must be 5 MB or smaller.',
            'consent.accepted' => 'Please agree to us keeping your application on file.',
        ], ['years_experience' => 'years of experience']);

        $file = $request->file('cv');
        $application = JobApplication::create([
            ...Arr::except($data, ['cv', 'consent']),
            // Random name on the private disk; the original name is only shown to staff.
            'cv_path' => $file->store('cvs', 'local'),
            'cv_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'status' => ApplicationStatus::New,
        ]);
        $application->load('jobPosting');

        Notify::office(new NewSubmission(
            "New application {$application->reference}: {$application->full_name}",
            ["{$application->full_name} applied for ".($application->jobPosting->title ?? $application->category->label()).'.'],
            route('admin.applications.show', $application),
        ));
        $request->session()->put('application_reference', $application->reference);

        return to_route('apply.received');
    }

    public function received(Request $request): View|RedirectResponse
    {
        $reference = $request->session()->get('application_reference');

        return $reference ? view('pages.received', [
            'title' => 'Application received',
            'reference' => $reference,
            'lead' => 'Thank you for applying to Woodlands Hospital. Our team reads every application.',
            'steps' => [
                'We review your CV against the role.',
                'If it is a match, we contact you to arrange an interview.',
                'Please have your registration and certificates ready to show.',
            ],
            'extKey' => null,
        ]) : to_route('apply.create');
    }
}
