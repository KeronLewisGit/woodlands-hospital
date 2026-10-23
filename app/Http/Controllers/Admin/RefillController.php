<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefillStatus;
use App\Http\Controllers\Controller;
use App\Models\RefillRequest;
use App\Notifications\RefillReady;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RefillController extends Controller
{
    public function index(Request $request): View
    {
        $status = RefillStatus::tryFrom(input_string($request->query('status')));

        return view('admin.refills.index', [
            'refills' => RefillRequest::query()
                ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->where('status', '!=', RefillStatus::Closed))
                ->orderByRaw("case status when 'new' then 0 when 'preparing' then 1 when 'ready' then 2 else 3 end")
                ->oldest()->paginate(25)->withQueryString(),
            'active' => $status,
            'counts' => RefillRequest::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function update(Request $request, RefillRequest $refill): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(RefillStatus::class)],
            'staff_notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $becameReady = $refill->status !== RefillStatus::Ready && $data['status'] === RefillStatus::Ready->value;
        $refill->update($data);

        $note = '';
        if ($becameReady) {
            Notify::address($refill->email, new RefillReady($refill));
            $note = $refill->email ? ' The patient has been emailed.' : ' No email on file: call or WhatsApp '.format_phone($refill->phone).'.';
        }

        return back()->with('status', "{$refill->reference} is now “{$refill->status->label()}”.{$note}");
    }
}
