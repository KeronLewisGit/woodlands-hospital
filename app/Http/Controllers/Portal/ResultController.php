<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\PatientResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends Controller
{
    public function index(Request $request): View
    {
        $patient = $this->patient($request);

        return view('portal.dashboard', [
            'patient' => $patient,
            'results' => $patient->results()->latest()->get(),
        ]);
    }

    /**
     * Streams the PDF only to the patient who owns it. Anyone else gets a 404, never a 403,
     * so result IDs can't be probed to learn that a report exists.
     */
    public function download(Request $request, int $result): StreamedResponse
    {
        $patient = $this->patient($request);
        $report = $patient->results()->whereKey($result)->first();
        abort_if($report === null || ! Storage::disk(PatientResult::DISK)->exists($report->file_path), 404);

        if ($report->first_viewed_at === null) {
            $report->forceFill(['first_viewed_at' => now()])->save();
        }

        return self::stream($report, $request->boolean('download'));
    }

    public static function stream(PatientResult $report, bool $attachment = false): StreamedResponse
    {
        return Storage::disk(PatientResult::DISK)->response($report->file_path, $report->downloadName(), [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ], $attachment ? 'attachment' : 'inline');
    }

    private function patient(Request $request): Patient
    {
        $patient = $request->user('patient');
        abort_unless($patient instanceof Patient, 403);

        return $patient;
    }
}
