<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\RefillController;
use Illuminate\Support\Facades\Route;

// ---------- Public site ----------
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/clinics', [PageController::class, 'clinics'])->name('clinics');
Route::get('/doctors', [PageController::class, 'doctors'])->name('doctors');
Route::get('/emergency', [PageController::class, 'emergency'])->name('emergency');
Route::get('/patients-and-visitors', [PageController::class, 'patients'])->name('patients');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:forms')->name('contact.store');
Route::get('/privacy', [PageController::class, 'privacy'])->name('privacy');
Route::get('/photo-credits', [PageController::class, 'credits'])->name('credits');
Route::get('/sitemap.xml', [PageController::class, 'sitemap'])->name('sitemap');

Route::get('/appointments', [AppointmentController::class, 'create'])->name('appointments.create');
Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('throttle:forms')->name('appointments.store');
Route::get('/appointments/received', [AppointmentController::class, 'received'])->name('appointments.received');

Route::get('/pharmacy', [RefillController::class, 'create'])->name('pharmacy');
Route::post('/pharmacy', [RefillController::class, 'store'])->middleware('throttle:forms')->name('pharmacy.store');
Route::get('/pharmacy/received', [RefillController::class, 'received'])->name('pharmacy.received');

Route::get('/careers', [JobController::class, 'index'])->name('jobs.index');
Route::get('/careers/apply', [ApplicationController::class, 'create'])->name('apply.create');
Route::post('/careers/apply', [ApplicationController::class, 'store'])->middleware('throttle:forms')->name('apply.store');
Route::get('/careers/apply/received', [ApplicationController::class, 'received'])->name('apply.received');
Route::get('/careers/{job}', [JobController::class, 'show'])->name('jobs.show');

// ---------- Secure results portal (guard "patient") ----------
Route::prefix('results')->name('portal.')->group(function () {
    Route::middleware('guest:patient')->group(function () {
        Route::get('/sign-in', [Portal\AuthController::class, 'show'])->name('login');
        Route::post('/sign-in', [Portal\AuthController::class, 'login'])->middleware('throttle:portal-login');
        Route::get('/forgot-password', [Portal\PasswordController::class, 'request'])->name('password.request');
        Route::post('/forgot-password', [Portal\PasswordController::class, 'email'])->middleware('throttle:portal-reset')->name('password.email');
        Route::get('/reset-password/{token}', [Portal\PasswordController::class, 'edit'])->name('password.reset');
        Route::post('/reset-password', [Portal\PasswordController::class, 'update'])->middleware('throttle:portal-reset')->name('password.update');
        Route::get('/welcome/{patient}', [Portal\InvitationController::class, 'show'])->name('invite');
        Route::post('/welcome/{patient}', [Portal\InvitationController::class, 'accept'])->middleware('throttle:portal-reset')->name('invite.accept');
    });
    Route::middleware('auth:patient')->group(function () {
        Route::get('/', [Portal\ResultController::class, 'index'])->name('dashboard');
        Route::get('/{result}/download', [Portal\ResultController::class, 'download'])->whereNumber('result')->name('results.download');
        Route::post('/sign-out', [Portal\AuthController::class, 'logout'])->name('logout');
    });
});

// ---------- Staff ----------
Route::middleware('guest:web')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:web')->name('logout');

Route::middleware('auth:web')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::middleware('role:front_desk')->group(function () {
        Route::get('/appointments', [Admin\AppointmentController::class, 'index'])->name('appointments.index');
        Route::get('/appointments/{appointment}', [Admin\AppointmentController::class, 'show'])->name('appointments.show');
        Route::patch('/appointments/{appointment}', [Admin\AppointmentController::class, 'update'])->name('appointments.update');
        Route::get('/refills', [Admin\RefillController::class, 'index'])->name('refills.index');
        Route::patch('/refills/{refill}', [Admin\RefillController::class, 'update'])->name('refills.update');
        Route::get('/messages', [Admin\MessageController::class, 'index'])->name('messages.index');
        Route::patch('/messages/{message}', [Admin\MessageController::class, 'update'])->name('messages.update');
        Route::resource('clinics', Admin\ClinicSessionController::class)->except('show')->parameters(['clinics' => 'clinic']);
    });

    Route::middleware('role:front_desk,lab')->group(function () {
        Route::get('/patients', [Admin\PatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/create', [Admin\PatientController::class, 'create'])->name('patients.create');
        Route::post('/patients', [Admin\PatientController::class, 'store'])->name('patients.store');
        Route::get('/patients/{patient}', [Admin\PatientController::class, 'show'])->name('patients.show');
        Route::put('/patients/{patient}', [Admin\PatientController::class, 'update'])->name('patients.update');
        Route::post('/patients/{patient}/invite', [Admin\PatientController::class, 'invite'])->name('patients.invite');
    });

    Route::middleware('role:lab')->group(function () {
        Route::post('/patients/{patient}/results', [Admin\ResultController::class, 'store'])->name('results.store');
        Route::get('/results/{result}', [Admin\ResultController::class, 'show'])->name('results.show');
        Route::delete('/results/{result}', [Admin\ResultController::class, 'destroy'])->name('results.destroy');
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('doctors', Admin\DoctorController::class)->except('show');
        Route::resource('jobs', Admin\JobPostingController::class)->except('show')->parameters(['jobs' => 'job']);
        Route::get('/applications', [Admin\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [Admin\ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/cv', [Admin\ApplicationController::class, 'cv'])->name('applications.cv');
        Route::patch('/applications/{application}', [Admin\ApplicationController::class, 'update'])->name('applications.update');
        Route::get('/settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
        Route::get('/staff', [Admin\StaffController::class, 'index'])->name('staff.index');
        Route::post('/staff', [Admin\StaffController::class, 'store'])->name('staff.store');
        Route::patch('/staff/{user}', [Admin\StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{user}', [Admin\StaffController::class, 'destroy'])->name('staff.destroy');
    });
});
