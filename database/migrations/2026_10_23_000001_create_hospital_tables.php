<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Editable phone numbers, extensions and hours (admin → Settings).
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Doctor directory: managed by staff, seeded empty. Departments are keys from config/woodlands.php.
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name', 120);
            $table->string('title', 120)->nullable(); // e.g. "Consultant Obstetrician & Gynaecologist"
            $table->string('department', 40)->index();
            $table->string('qualifications', 200)->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedSmallInteger('sort')->default(100);
            $table->timestamps();
        });

        // Weekly specialist clinic timetable, edited by staff.
        Schema::create('clinic_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('department', 40)->index();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 120)->nullable(); // shown instead of a doctor name, e.g. "Antenatal clinic"
            $table->unsignedTinyInteger('weekday'); // 1 = Monday … 7 = Sunday (ISO)
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('note', 200)->nullable(); // "By appointment only", "First Tuesday of the month"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('appointment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('service', 20)->index(); // clinic | specialist | radiology | lab
            $table->string('department', 40)->nullable();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 12)->default('requested')->index();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('email')->nullable();
            $table->boolean('returning_patient')->default(false);
            $table->date('preferred_date')->index();
            $table->string('preferred_window', 12); // morning | afternoon | evening
            $table->date('alternate_date')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('has_referral')->default(false);
            $table->dateTime('scheduled_for')->nullable()->index();
            $table->text('message_to_patient')->nullable();
            $table->text('staff_notes')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });

        // Results-portal accounts. No public sign-up: staff create the record and the patient sets a password from an emailed invitation.
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('hospital_number', 30)->nullable()->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->date('date_of_birth')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('patient_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('patient_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 12); // lab | radiology
            $table->string('title', 160);
            $table->date('collected_on')->nullable();
            // Private disk path with a random name; never under public/.
            $table->string('file_path');
            $table->string('original_name', 200);
            $table->unsignedInteger('size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('first_viewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refill_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('status', 12)->default('new')->index();
            $table->string('name', 120);
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->text('medications');
            $table->string('prescriber', 120)->nullable();
            $table->text('notes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 20)->index();
            $table->string('name', 120);
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('body');
            $table->timestamp('handled_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category', 20)->index();
            $table->string('employment_type', 20);
            $table->string('status', 10)->default('draft')->index();
            $table->string('location')->default('Georgetown');
            $table->unsignedSmallInteger('openings')->default(1);
            $table->date('ends_on')->nullable();
            $table->text('summary');
            $table->text('duties')->nullable();
            $table->text('requirements')->nullable();
            $table->timestamps();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('job_posting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category', 20)->index();
            $table->string('status', 12)->default('new')->index();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('phone', 30);
            $table->string('phone_digits', 20)->nullable()->index();
            $table->string('email');
            $table->string('registration', 120)->nullable(); // professional registration / licence number
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->text('message')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('cv_name')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        foreach (['job_applications', 'job_postings', 'messages', 'refill_requests', 'patient_results', 'patient_password_reset_tokens', 'patients', 'appointment_requests', 'clinic_sessions', 'doctors', 'settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
