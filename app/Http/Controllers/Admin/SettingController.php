<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['values' => array_merge(Setting::DEFAULTS, array_filter(Setting::allCached(), fn ($v) => $v !== null))]);
    }

    public function update(Request $request): RedirectResponse
    {
        $phone = ['required', 'string', 'max:30', phone_rule()];
        $ext = ['nullable', 'string', 'max:10', 'regex:/^\d{1,6}$/'];
        $data = $request->validate([
            'phone_main' => $phone,
            'ambulance_phone' => $phone,
            'ambulance_ext' => $ext,
            'ext_clinics' => $ext,
            'ext_radiology' => $ext,
            'ext_lab' => $ext,
            'extra_extensions' => ['nullable', 'string', 'max:2000'],
            'pharmacy_whatsapp' => $phone,
            'email_care' => ['required', 'email', 'max:255'],
            'email_customer' => ['nullable', 'email', 'max:255'],
            'visiting_hours' => ['nullable', 'string', 'max:500'],
            'pharmacy_hours' => ['nullable', 'string', 'max:200'],
            'clinic_hours_note' => ['nullable', 'string', 'max:500'],
            'notify_email' => ['nullable', 'string', 'max:500'],
            'announcement' => ['nullable', 'string', 'max:300'],
        ], [
            'regex' => 'Use digits only for :attribute.',
        ]);

        Setting::setMany(array_map(fn ($v) => $v === null ? '' : (string) $v, $data));

        return back()->with('status', 'Settings saved. The website shows the new details straight away.');
    }
}
