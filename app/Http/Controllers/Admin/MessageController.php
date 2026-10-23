<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MessageTopic;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $showHandled = $request->query('show') === 'handled';
        $topic = MessageTopic::tryFrom(input_string($request->query('topic')));

        return view('admin.messages.index', [
            'messages' => Message::query()
                ->when($showHandled, fn ($q) => $q->whereNotNull('handled_at'), fn ($q) => $q->whereNull('handled_at'))
                ->when($topic, fn ($q) => $q->where('topic', $topic))
                ->latest()->paginate(25)->withQueryString(),
            'showHandled' => $showHandled,
            'topic' => $topic,
            'openCount' => Message::open()->count(),
        ]);
    }

    public function update(Request $request, Message $message): RedirectResponse
    {
        $message->update(['handled_at' => $request->boolean('handled') ? now() : null]);

        return back()->with('status', $request->boolean('handled') ? 'Marked as handled.' : 'Moved back to open messages.');
    }
}
