<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\AppointmentService;
use App\Enums\AppointmentStatus;
use App\Enums\EmploymentType;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use App\Enums\MessageTopic;
use App\Enums\RefillStatus;
use App\Enums\ResultKind;
use App\Enums\Role;
use App\Models\AppointmentRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\Message;
use App\Models\Patient;
use App\Models\PatientResult;
use App\Models\RefillRequest;
use App\Models\Setting;
use App\Models\User;
use App\Support\SamplePdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pitch demo and local work: php artisan migrate:fresh --seeder=DemoSeeder
 *
 * Everything here is clearly fictitious demo data (".test" addresses, "SAMPLE" reports).
 * Doctors and clinic sessions are deliberately NOT seeded: the hospital supplies the real
 * list, and the public pages show neutral "call to book" copy until then.
 * Job postings are illustrative roles, not real vacancies.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SettingsSeeder::class);
        Setting::setMany(['notify_email' => 'frontdesk@woodlands.test']);
        $password = Hash::make('password');

        User::updateOrCreate(['email' => 'admin@woodlands.test'], ['name' => 'Demo Administrator', 'password' => $password, 'role' => Role::Admin]);
        $desk = User::updateOrCreate(['email' => 'frontdesk@woodlands.test'], ['name' => 'Demo Front Desk', 'password' => $password, 'role' => Role::FrontDesk]);
        $lab = User::updateOrCreate(['email' => 'lab@woodlands.test'], ['name' => 'Demo Lab Technologist', 'password' => $password, 'role' => Role::Lab]);

        // Portal patients: one active (with a sample report), one invited but not yet activated.
        $patient = Patient::updateOrCreate(['email' => 'patient@woodlands.test'], [
            'first_name' => 'Demo', 'last_name' => 'Patient', 'date_of_birth' => '1985-04-12', 'phone' => '600-0000', 'hospital_number' => 'DEMO-0001', 'created_by' => $desk->id,
        ]);
        $patient->forceFill(['password' => $password, 'invited_at' => now()->subDays(10), 'activated_at' => now()->subDays(9), 'last_login_at' => now()->subDay()])->save();

        $pending = Patient::updateOrCreate(['email' => 'invited@woodlands.test'], [
            'first_name' => 'Invited', 'last_name' => 'Patient', 'date_of_birth' => '1990-01-01', 'hospital_number' => 'DEMO-0002', 'created_by' => $desk->id,
        ]);
        $pending->forceFill(['invited_at' => now()->subDays(2)])->save();

        if (! $patient->results()->exists()) {
            foreach ([
                [ResultKind::Lab, 'Full blood count (SAMPLE)', 2],
                [ResultKind::Radiology, 'Chest X-ray report (SAMPLE)', 12],
            ] as [$kind, $title, $daysAgo]) {
                $path = PatientResult::DIRECTORY.'/'.$patient->id.'/sample-'.$kind->value.'.pdf';
                Storage::disk(PatientResult::DISK)->put($path, SamplePdf::make('Woodlands Hospital - SAMPLE REPORT', [
                    'DEMONSTRATION ONLY. This is not a real patient report.',
                    'Report: '.$title,
                    'Department: '.$kind->label(),
                    'Patient: Demo Patient (DEMO-0001)',
                    '',
                    'In the live portal, the laboratory or radiology team uploads',
                    'the signed PDF report here. Only this patient can open it.',
                ]));
                $result = $patient->results()->create([
                    'kind' => $kind, 'title' => $title, 'collected_on' => today()->subDays($daysAgo + 1),
                    'file_path' => $path, 'original_name' => basename($path), 'size' => (int) Storage::disk(PatientResult::DISK)->size($path), 'uploaded_by' => $lab->id,
                ]);
                $result->forceFill(['created_at' => now()->subDays($daysAgo), 'notified_at' => now()->subDays($daysAgo), 'first_viewed_at' => $daysAgo > 5 ? now()->subDays($daysAgo - 1) : null])->save();
            }
        }

        if (! AppointmentRequest::exists()) {
            $people = [
                ['Anita', 'Persaud', AppointmentService::Radiology, null, 0, AppointmentStatus::Requested],
                ['Marcus', 'Fraser', AppointmentService::Specialist, 'cardiology', 1, AppointmentStatus::Requested],
                ['Shondel', 'Williams', AppointmentService::Lab, null, 2, AppointmentStatus::Requested],
                ['Ravi', 'Singh', AppointmentService::Clinic, null, 4, AppointmentStatus::Requested],
                ['Kezia', 'Adams', AppointmentService::Specialist, 'gynaecology', 6, AppointmentStatus::Requested],
                ['Devon', 'Charles', AppointmentService::Radiology, null, 0, AppointmentStatus::Confirmed],
                ['Priya', 'Ramdass', AppointmentService::Specialist, 'paediatrics', 0, AppointmentStatus::Confirmed],
                ['Leon', 'Grant', AppointmentService::Lab, null, 3, AppointmentStatus::Rescheduled],
                ['Tamika', 'Bourne', AppointmentService::Clinic, null, 5, AppointmentStatus::Cancelled],
            ];
            foreach ($people as $i => [$first, $last, $service, $dept, $inDays, $status]) {
                $handled = $status !== AppointmentStatus::Requested;
                $booked = $handled && $status !== AppointmentStatus::Cancelled;
                $a = AppointmentRequest::create([
                    'service' => $service, 'department' => $dept, 'status' => $status,
                    'first_name' => $first, 'last_name' => $last, 'phone' => '6'.str_pad((string) (100000 + $i * 7919), 6, '0', STR_PAD_LEFT),
                    'email' => strtolower($first).'@example.test', 'returning_patient' => $i % 2 === 0,
                    'preferred_date' => today()->addDays($inDays), 'preferred_window' => ['morning', 'afternoon', 'evening'][$i % 3],
                    'reason' => $service === AppointmentService::Radiology ? 'Ultrasound requested by my GP.' : null,
                    'has_referral' => $service === AppointmentService::Radiology,
                    'scheduled_for' => $booked ? today()->addDays($inDays)->setTime(9 + $i % 6, $i % 2 ? 30 : 0) : null,
                    'handled_by' => $handled ? $desk->id : null,
                    'status_changed_at' => $handled ? now()->subHours($i) : null,
                ]);
                $a->forceFill(['created_at' => now()->subHours(3 + $i * 9)])->save();
            }
            // Thirty days of history for the dashboard chart (seeded random, weekdays busier).
            mt_srand(1969);
            for ($d = 29; $d >= 1; $d--) {
                $weekend = today()->subDays($d)->isWeekend();
                for ($n = 0, $count = mt_rand($weekend ? 0 : 2, $weekend ? 2 : 6); $n < $count; $n++) {
                    $a = AppointmentRequest::create([
                        'service' => AppointmentService::cases()[($d + $n) % 4], 'status' => AppointmentStatus::Confirmed,
                        'first_name' => 'Earlier', 'last_name' => 'Request '.$d.'-'.$n, 'phone' => '600'.str_pad((string) ($d * 10 + $n), 4, '0', STR_PAD_LEFT),
                        'preferred_date' => today()->subDays($d), 'preferred_window' => 'morning',
                        'scheduled_for' => today()->subDays($d)->setTime(10, 0), 'handled_by' => $desk->id, 'status_changed_at' => now()->subDays($d),
                    ]);
                    $a->forceFill(['created_at' => now()->subDays($d)->setTime(8 + $n, 15)])->save();
                }
            }
        }

        if (! RefillRequest::exists()) {
            RefillRequest::create(['name' => 'Joan Mc Donald', 'phone' => '622-0000', 'medications' => "Metformin 500 mg, 60 tablets\nAmlodipine 5 mg, 30 tablets", 'prescriber' => 'Woodlands clinic', 'status' => RefillStatus::New]);
            RefillRequest::create(['name' => 'Errol Hinds', 'phone' => '622-0001', 'email' => 'errol@example.test', 'medications' => 'Salbutamol inhaler', 'status' => RefillStatus::Preparing]);
        }

        if (! Message::exists()) {
            Message::create(['topic' => MessageTopic::Billing, 'name' => 'Sasha Thomas', 'email' => 'sasha@example.test', 'body' => 'Do you accept my employer’s health insurance for outpatient scans? Which documents should I bring?']);
            Message::create(['topic' => MessageTopic::Records, 'name' => 'Gary Daniels', 'phone' => '600-1111', 'body' => 'I need a copy of my discharge summary for my overseas doctor.']);
        }

        if (! JobPosting::exists()) {
            $jobs = [
                ['title' => 'Registered Nurse, Medical-Surgical Ward', 'category' => JobCategory::Nursing, 'openings' => 3,
                    'summary' => 'Illustrative posting for the demo. Provide safe, compassionate nursing care to adult inpatients on day and night rotation.',
                    'duties' => "Assess, plan and deliver patient care\nGive medicines and record observations\nWork with doctors, physiotherapists and families",
                    'requirements' => "Registered with the Guyana Nursing Council\nCurrent BLS certificate\nAble to work rotating shifts"],
                ['title' => 'Medical Laboratory Technologist', 'category' => JobCategory::Laboratory, 'openings' => 1,
                    'summary' => 'Illustrative posting for the demo. Run haematology and chemistry testing in a 24-hour hospital laboratory.',
                    'duties' => "Process and analyse patient samples\nRun quality control and maintain analysers\nPublish verified reports to the patient portal",
                    'requirements' => "Degree or diploma in Medical Technology\nExperience with automated analysers"],
                ['title' => 'Radiographer', 'category' => JobCategory::Radiology, 'openings' => 1,
                    'summary' => 'Illustrative posting for the demo. Perform X-ray and CT examinations for emergency, inpatient and outpatient referrals.',
                    'requirements' => "Diploma or degree in Radiography\nCT experience an advantage"],
            ];
            foreach ($jobs as $job) {
                JobPosting::create($job + ['slug' => Str::slug($job['title']), 'employment_type' => EmploymentType::FullTime, 'status' => JobStatus::Open, 'location' => 'Georgetown']);
            }
            $nurse = JobPosting::where('category', JobCategory::Nursing)->first();
            foreach ([['Alicia', 'Bacchus'], ['Nadia', 'Khan'], ['Trevor', 'Lewis']] as $i => [$first, $last]) {
                $path = 'cvs/demo-'.$i.'.pdf';
                Storage::disk('local')->put($path, SamplePdf::make('Sample CV (demo)', [$first.' '.$last, 'Demonstration file only.']));
                JobApplication::create([
                    'job_posting_id' => $nurse?->id, 'category' => JobCategory::Nursing, 'status' => $i === 2 ? ApplicationStatus::Reviewing : ApplicationStatus::New,
                    'first_name' => $first, 'last_name' => $last, 'phone' => '60'.(10000 + $i), 'email' => strtolower($first).'@example.test',
                    'registration' => 'GNC-DEMO-'.(100 + $i), 'years_experience' => 2 + $i * 3, 'cv_path' => $path, 'cv_name' => $first.'-'.$last.'-CV.pdf',
                ]);
            }
        }
    }
}
