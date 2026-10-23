<?php

namespace App\Http\Controllers;

use App\Enums\RefillStatus;
use App\Models\RefillRequest;
use App\Notifications\NewSubmission;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefillController extends Controller
{
    public function create(): View
    {
        return view('pages.pharmacy');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            $request->session()->put('refill_reference', RefillRequest::REFERENCE_PREFIX.'-'.now()->format('y').'-00000');

            return to_route('pharmacy.received');
        }
        $request->merge(['email' => $request->filled('email') ? strtolower(input_string($request->input('email'))) : null]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'date_of_birth' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?:\D*\d){7,15}\D*$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'medications' => ['required', 'string', 'max:2000'],
            'prescriber' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'consent' => ['accepted'],
        ], [
            'phone.regex' => 'Enter a phone number we can call, with at least 7 digits.',
            'medications.required' => 'List the medicines you need, one per line.',
            'consent.accepted' => 'Please confirm that we may contact you about this request.',
        ]);
        unset($data['consent']);

        $refill = RefillRequest::create($data + ['status' => RefillStatus::New]);

        Notify::office(new NewSubmission(
            "Refill request {$refill->reference}: {$refill->name}",
            ["{$refill->name} asked for a prescription refill.", 'Phone: '.format_phone($refill->phone, true)],
            route('admin.refills.index'),
        ));
        $request->session()->put('refill_reference', $refill->reference);

        return to_route('pharmacy.received');
    }

    public function received(Request $request): View|RedirectResponse
    {
        $reference = $request->session()->get('refill_reference');

        return $reference ? view('pages.received', [
            'title' => 'Refill request received',
            'reference' => $reference,
            'lead' => 'The pharmacy has your request. We will call or message you when it is ready, or if we need a new prescription from your doctor.',
            'steps' => [
                'A pharmacist checks your request against your prescription.',
                'We contact you when it is ready to collect.',
                'Bring a photo ID and your prescription when you collect.',
            ],
            'extKey' => null,
            'whatsapp' => true,
        ]) : to_route('pharmacy');
    }
}
