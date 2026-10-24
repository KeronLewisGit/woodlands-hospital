<?php

namespace App\Http\Controllers;

use App\Enums\MessageTopic;
use App\Models\Message;
use App\Notifications\NewSubmission;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('pages.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('website')) {
            return to_route('contact')->with('sent', true);
        }
        $request->merge(['email' => $request->filled('email') ? strtolower(input_string($request->input('email'))) : null]);

        $data = $request->validate([
            'topic' => ['required', Rule::enum(MessageTopic::class)],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30', phone_rule()],
            'body' => ['required', 'string', 'max:3000'],
        ], [
            'email.required_without' => 'Give us an email address or a phone number so we can reply.',
            'phone.required_without' => 'Give us an email address or a phone number so we can reply.',
            'phone.regex' => 'Enter a phone number with at least 7 digits.',
            'body.required' => 'Write your message.',
        ]);

        $message = Message::create($data);
        Notify::office(new NewSubmission(
            "Website message ({$message->topic->label()}) from {$message->name}",
            [str($message->body)->limit(300)->toString()],
            route('admin.messages.index'),
        ));

        return to_route('contact')->with('sent', true)->withFragment('message');
    }
}
