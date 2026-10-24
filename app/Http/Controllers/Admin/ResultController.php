<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ResultKind;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\ResultController as PortalResults;
use App\Models\Patient;
use App\Models\PatientResult;
use App\Notifications\ResultsReady;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends Controller
{
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::enum(ResultKind::class)],
            'title' => ['required', 'string', 'max:160'],
            'collected_on' => ['nullable', 'date', 'before_or_equal:today'],
            // PDF only, checked by content (finfo), not just the extension.
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.(int) config('woodlands.result_max_kb')],
            'notify' => ['boolean'],
        ], [
            'file.mimetypes' => 'Upload the report as a PDF.',
            'file.max' => 'The PDF must be '.round(config('woodlands.result_max_kb') / 1024).' MB or smaller.',
        ]);

        $file = $request->file('file');
        $result = $patient->results()->create([
            'kind' => $data['kind'],
            'title' => $data['title'],
            'collected_on' => $data['collected_on'] ?? null,
            // Random file name inside a per-patient folder on the private disk.
            'file_path' => $file->store(PatientResult::DIRECTORY.'/'.$patient->id, PatientResult::DISK),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
            'size' => (int) $file->getSize(),
            'uploaded_by' => $request->user()?->id,
        ]);

        $message = "“{$result->title}” published to {$patient->full_name}’s portal.";
        if ($request->boolean('notify', true)) {
            if ($patient->isActivated()) {
                Notify::person($patient, new ResultsReady);
                $message .= ' They have been emailed that a new report is ready.';
            } else {
                // Not activated yet: the invitation itself says a report is waiting.
                $patient->invite(resultsWaiting: true);
                $message .= ' Their portal is not active yet, so an invitation was emailed.';
            }
            $result->forceFill(['notified_at' => now()])->save();
        }

        return back()->with('status', $message);
    }

    /** Staff copy, for checking an upload. Lab and admin only (route middleware). */
    public function show(PatientResult $result): StreamedResponse
    {
        abort_unless(Storage::disk(PatientResult::DISK)->exists($result->file_path), 404);

        return PortalResults::stream($result);
    }

    public function destroy(PatientResult $result): RedirectResponse
    {
        Storage::disk(PatientResult::DISK)->delete($result->file_path);
        $result->delete();

        return back()->with('status', 'Report removed from the portal and deleted.');
    }
}
