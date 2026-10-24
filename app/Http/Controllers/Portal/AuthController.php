<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View
    {
        return view('portal.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = strtolower($credentials['email']);

        // One message for every failure, so the form never reveals whether someone is a patient here.
        if (! Auth::guard('patient')->attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'That email and password do not match a results-portal account.']);
        }

        $request->session()->regenerate();
        /** @var Patient $patient */
        $patient = Auth::guard('patient')->user();
        $patient->forceFill(['last_login_at' => now()])->save();

        // The session's "intended" URL is shared with the staff guard: only follow it inside the portal.
        $intended = (string) $request->session()->pull('url.intended', '');

        return redirect()->to(str_starts_with((string) parse_url($intended, PHP_URL_PATH), '/results') ? $intended : route('portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('patient')->logout();
        // Patients often use a shared or clinic computer: drop the whole session.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('portal.login')->with('status', 'You are signed out. Close this window if you are on a shared computer.');
    }
}
