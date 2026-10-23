<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * First password for a portal account, from the signed link in PortalInvitation.
 * The link carries the invitation time, so sending a new invitation retires older links,
 * and it stops working once a password is set. When the record has a date of birth,
 * the patient must also confirm it: someone who only intercepted the email cannot activate.
 */
class InvitationController extends Controller
{
    public function show(Request $request, Patient $patient): View|RedirectResponse
    {
        if ($patient->isActivated()) {
            return to_route('portal.login')->with('status', 'Your portal is already active. Sign in, or use “Forgot password” if you need a new one.');
        }
        $this->guard($request, $patient);

        return view('portal.welcome', ['patient' => $patient, 'action' => $request->fullUrl()]);
    }

    public function accept(Request $request, Patient $patient): RedirectResponse
    {
        abort_if($patient->isActivated(), 403, 'This link has already been used.');
        $this->guard($request, $patient);

        $data = $request->validate([
            'date_of_birth' => [$patient->date_of_birth ? 'required' : 'nullable', 'date'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ], ['date_of_birth.required' => 'Enter your date of birth to confirm it is you.']);

        if ($patient->date_of_birth && ! $patient->date_of_birth->isSameDay((string) $data['date_of_birth'])) {
            throw ValidationException::withMessages(['date_of_birth' => 'That date of birth does not match our records. Call us if you need help.']);
        }

        $patient->forceFill(['password' => $data['password'], 'activated_at' => now(), 'last_login_at' => now()])->save();
        Auth::guard('patient')->login($patient);
        $request->session()->regenerate();

        return to_route('portal.dashboard')->with('status', 'Your password is set. Welcome to your results portal.');
    }

    private function guard(Request $request, Patient $patient): void
    {
        $current = (string) ($patient->invited_at->timestamp ?? 0);
        abort_unless($request->hasValidSignature() && hash_equals($current, input_string($request->query('v'))), 403, 'This link has expired or was replaced by a newer one. Ask the hospital to send a new invitation, or use “Forgot password”.');
    }
}
