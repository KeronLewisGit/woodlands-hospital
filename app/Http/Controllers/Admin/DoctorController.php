<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(): View
    {
        return view('admin.doctors.index', ['doctors' => Doctor::withCount('sessions')->orderBy('department')->orderBy('sort')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('admin.doctors.form', ['doctor' => new Doctor(['is_published' => true, 'sort' => 100])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Doctor::create($this->validated($request));

        return to_route('admin.doctors.index')->with('status', 'Doctor added to the directory.');
    }

    public function edit(Doctor $doctor): View
    {
        return view('admin.doctors.form', ['doctor' => $doctor]);
    }

    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $doctor->update($this->validated($request));

        return to_route('admin.doctors.index')->with('status', 'Doctor saved.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        $doctor->delete();

        return to_route('admin.doctors.index')->with('status', 'Doctor removed. Their clinic sessions stay, without a name.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $request->merge(['is_published' => $request->boolean('is_published')]);

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'department' => ['required', Rule::in(array_keys(department_options()))],
            'qualifications' => ['nullable', 'string', 'max:200'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_published' => ['boolean'],
            'sort' => ['required', 'integer', 'min:0', 'max:999'],
        ]);
    }
}
