<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        $q = input_string($request->query('q')) ?: null;

        return view('admin.patients.index', [
            'patients' => Patient::search($q)->withCount('results')->orderBy('last_name')->orderBy('first_name')->paginate(30)->withQueryString(),
            'q' => $q,
        ]);
    }

    public function create(): View
    {
        return view('admin.patients.form', ['patient' => new Patient]);
    }

    public function store(Request $request): RedirectResponse
    {
        $patient = new Patient($this->validated($request));
        $patient->created_by = $request->user()?->id;
        $patient->save();

        if ($request->boolean('send_invite', true)) {
            $patient->invite();

            return to_route('admin.patients.show', $patient)->with('status', "Portal record created and an invitation emailed to {$patient->email}.");
        }

        return to_route('admin.patients.show', $patient)->with('status', 'Portal record created. No invitation sent yet.');
    }

    public function show(Request $request, Patient $patient): View
    {
        $canSeeResults = $request->user()?->hasRole(Role::Lab) ?? false;

        return view('admin.patients.show', [
            'patient' => $patient,
            'results' => $canSeeResults ? $patient->results()->with('uploader')->latest()->get() : collect(),
            'resultCount' => $patient->results()->count(),
            'canSeeResults' => $canSeeResults,
        ]);
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $data = $this->validated($request, $patient);
        $emailChanged = strtolower($data['email']) !== $patient->email;
        $patient->update($data);

        // A new address must be proven again: old invitation links stop working.
        if ($emailChanged && ! $patient->isActivated()) {
            $patient->forceFill(['invited_at' => null])->save();
        }

        return back()->with('status', 'Patient details saved.');
    }

    public function invite(Patient $patient): RedirectResponse
    {
        if ($patient->isActivated()) {
            return back()->with('status', 'This patient has already activated their portal. If they forgot the password, they can use “Forgot password” on the sign-in page.');
        }
        $patient->invite();

        return back()->with('status', "A new invitation was emailed to {$patient->email}. Earlier links no longer work.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?Patient $patient = null): array
    {
        $request->merge(['email' => strtolower(input_string($request->input('email'))), 'hospital_number' => input_string($request->input('hospital_number')) ?: null]);

        return $request->validate([
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('patients', 'email')->ignore($patient?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'hospital_number' => ['nullable', 'string', 'max:30', Rule::unique('patients', 'hospital_number')->ignore($patient?->id)],
        ], ['email.unique' => 'Another portal record already uses this email address.']);
    }
}
