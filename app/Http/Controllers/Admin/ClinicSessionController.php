<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClinicSession;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClinicSessionController extends Controller
{
    public function index(): View
    {
        return view('admin.clinics.index', [
            'sessions' => ClinicSession::with('doctor')->orderBy('weekday')->orderBy('starts_at')->get()->groupBy('weekday'),
        ]);
    }

    public function create(): View
    {
        return view('admin.clinics.form', ['clinic' => new ClinicSession(['weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '12:00', 'is_active' => true]), 'doctors' => Doctor::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        ClinicSession::create($this->validated($request));

        return to_route('admin.clinics.index')->with('status', 'Clinic session added. It now shows on the public clinic timetable.');
    }

    public function edit(ClinicSession $clinic): View
    {
        return view('admin.clinics.form', ['clinic' => $clinic, 'doctors' => Doctor::orderBy('name')->get()]);
    }

    public function update(Request $request, ClinicSession $clinic): RedirectResponse
    {
        $clinic->update($this->validated($request));

        return to_route('admin.clinics.index')->with('status', 'Clinic session saved.');
    }

    public function destroy(ClinicSession $clinic): RedirectResponse
    {
        $clinic->delete();

        return to_route('admin.clinics.index')->with('status', 'Clinic session removed.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'department' => ['required', Rule::in(array_keys(department_options()))],
            'doctor_id' => ['nullable', 'integer', 'exists:doctors,id', 'required_without:label'],
            'label' => ['nullable', 'string', 'max:120', 'required_without:doctor_id'],
            'weekday' => ['required', 'integer', Rule::in(array_keys(ClinicSession::WEEKDAYS))],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'note' => ['nullable', 'string', 'max:200'],
            'is_active' => ['boolean'],
        ], [
            'doctor_id.required_without' => 'Choose a doctor or type a clinic name.',
            'label.required_without' => 'Choose a doctor or type a clinic name.',
            'ends_at.after' => 'The end time must be after the start time.',
        ]);
    }
}
