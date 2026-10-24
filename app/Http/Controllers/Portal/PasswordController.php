<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public const string SENT = 'If that email belongs to a results-portal account, a link is on its way. Check your inbox and spam folder.';

    public function request(): View
    {
        return view('portal.forgot');
    }

    public function email(Request $request): RedirectResponse
    {
        $email = strtolower((string) $request->validate(['email' => ['required', 'email']])['email']);
        $patient = Patient::where('email', $email)->first();

        if ($patient?->isActivated()) {
            Password::broker('patients')->sendResetLink(['email' => $email]);
        } elseif ($patient && Cache::add('portal-invite:'.$patient->id, true, now()->addMinutes(10))) {
            // Never activated: a fresh invitation (at most every 10 minutes) instead of a reset link.
            $patient->invite();
        }

        // Same answer whether or not the address exists.
        return back()->with('status', self::SENT);
    }

    public function edit(Request $request, string $token): View
    {
        return view('portal.reset', ['token' => $token, 'email' => input_string($request->query('email'))]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)],
        ]);

        $status = Password::broker('patients')->reset(
            ['email' => strtolower(input_string($request->input('email')))] + $request->only('password', 'password_confirmation', 'token'),
            function (Patient $patient, string $password): void {
                $patient->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                event(new PasswordReset($patient));
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'This reset link is invalid or has expired. Ask for a new one.']);
        }

        return to_route('portal.login')->with('status', 'Your password has been changed. You can sign in now.');
    }
}
