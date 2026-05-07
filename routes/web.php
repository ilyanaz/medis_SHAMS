<?php

use App\Http\Controllers\DeveloperAuthController;
use App\Http\Controllers\DoctorAuthController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SystemController::class, 'dashboard'])->name('home');
Route::get('/dashboard', [SystemController::class, 'dashboard'])->name('dashboard');
Route::get('/company', [SystemController::class, 'companies'])->name('companies.index');
Route::get('/employee', [SystemController::class, 'employees'])->name('employees.index');
Route::get('/doctor', [SystemController::class, 'doctors'])->name('doctors.index');
Route::get('/email', [SystemController::class, 'emailSetup'])->name('email.setup');
Route::post('/email/test', [SystemController::class, 'sendTestEmail'])->name('email.test');

Route::get('/developer/login', [DeveloperAuthController::class, 'showLogin'])->name('developer.login');
Route::post('/developer/login', [DeveloperAuthController::class, 'login'])->name('developer.login.attempt');
Route::get('/developer/dashboard', [DeveloperAuthController::class, 'dashboard'])->name('developer.dashboard');
Route::post('/developer/logout', [DeveloperAuthController::class, 'logout'])->name('developer.logout');
Route::view('/developer/users', 'developer.devUsers')->name('developer.users');
Route::view('/developer/panel', 'developer.devPan')->name('developer.panel');

// Panel routes
Route::view('/panel/login', 'panel.login')->name('panel.login');
Route::post('/panel/login', fn () => redirect()->route('panel.dashboard'))->name('login.store');
Route::view('/panel/logout', 'panel.logout')->name('panel.logout');
Route::view('/panel/dashboard', 'panel.dashboard')->name('panel.dashboard');
Route::view('/panel/settings', 'panel.settings')->name('panel.settings');
Route::view('/panel/account-settings', 'panel.account_settings')->name('panel.account_settings');
Route::view('/panel/forgot-password', 'panel.forgot_password')->name('panel.forgot_password');

// Standard Auth Routes
Route::view('/login', 'auth.login')->name('login');
Route::post('/login', fn () => redirect()->route('panel.dashboard'))->name('login.attempt');
Route::view('/register', 'auth.register')->name('register');
Route::post('/register', fn () => redirect()->route('login'))->name('register.store');
Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
Route::post('/forgot-password', fn () => redirect()->back())->name('password.email');
Route::view('/reset-password', 'auth.reset-password')->name('password.reset');
Route::post('/reset-password', fn () => redirect()->route('login'))->name('password.store');
Route::view('/verify-email', 'auth.verify-email')->name('verification.notice');
Route::post('/verify-email/send', fn () => redirect()->back())->name('verification.send');

Route::get('/doctor/login', [DoctorAuthController::class, 'showLogin'])->name('doctor.login');
Route::post('/doctor/login', [DoctorAuthController::class, 'login'])->name('doctor.login.attempt');
Route::get('/doctor/dashboard', [DoctorAuthController::class, 'dashboard'])->name('doctor.dashboard');
Route::post('/doctor/logout', [DoctorAuthController::class, 'logout'])->name('doctor.logout');

$legacyViewRoutes = [
    'profile' => 'panel.dashboard',
    'settings' => 'panel.settings',
    'account.settings' => 'panel.account_settings',
    'logout.page' => 'panel.logout',
    'general.report' => 'report.general_report',
    'general.examination' => 'report.general_examination',
    'surveillance.company' => 'company.surveillance_company',
    'surveillance.company.new' => 'company.new_company',
    'surveillance.company.edit' => 'action.edit_surveillanceComp',
    'surveillance.company.delete' => 'action.delete_surveillanceComp',
    'surveillance.employee' => 'employee.surveillance_employee',
    'surveillance.employee.new' => 'employee.new_employee',
    'surveillance.employee.edit' => 'action.edit_surveillanceEmp',
    'surveillance.employee.delete' => 'action.delete_surveillanceEmp',
    'surveillance.list' => 'surveillance.surveillance_list',
    'surveillance.record.edit' => 'action.edit_surveillanceRecord',
    'surveillance.record.delete' => 'action.delete_surveillanceRecord',
    'surveillance.declaration' => 'surveillance.surveillance_examination',
    'surveillance.examination' => 'surveillance.surveillance_examination',
    'surveillance.confirm' => 'surveillance.surveillance_list',
    'surveillance.report' => 'report.surveillance_usechh1Report',
    'surveillance.report.abnormal' => 'report.suveillance_abnormalReport',
    'surveillance.report.summary' => 'report.surveillance_summaryReport',
    'surveillance.report.summary-employee' => 'report.surveillance_fitnessReport.summaryEmpReport',
    'surveillance.report.removal' => 'report.surveillance_removalReport',
    'surveillance.report.fitness' => 'report.surveillance_fitnessReport',
    'surveillance.report.usechh1' => 'report.surveillance_usechh1Report',
    'surveillance.record.start' => 'surveillance.new_surveillanceRecord',
    'pdf.surveillance-report' => 'report.PDF_USECHH1',
    'audiometry.company' => 'company.audiometry_company',
    'audiometry.company.new' => 'company.new_company',
    'audiometry.company.edit' => 'action.edit_audioComp',
    'audiometry.company.delete' => 'action.delete_audioComp',
    'audiometry.employee' => 'employee.audiometry_employee',
    'audiometry.employee.new' => 'employee.new_employee',
    'audiometry.employee.edit' => 'action.edit_audioEmp',
    'audiometry.employee.delete' => 'action.delete_audioEmp',
    'audiometry.questionnaire' => 'audiometry.audiometry_questionnaire',
    'audiometry.questionnaire.new' => 'audiometry.audiometry_list',
    'audiometry.questionnaire.edit' => 'action.edit_questionnaire',
    'audiometry.questionnaire.delete' => 'action.delete_questionnaire',
    'audiometry.list' => 'audiometry.audiometry_list',
    'audiometry.examination' => 'audiometry.audiometry_examination',
    'audiometry.confirm' => 'audiometry.audiometry_list',
    'audiometry.report' => 'audiometry.audiometry_report',
    'audiometry.record.edit' => 'action.edit_audioRecord',
    'audiometry.record.delete' => 'action.delete_audioRecord',
    'admin.dashboard' => 'panel.dashboard',
    'admin.company' => 'company.surveillance_company',
    'admin.employee' => 'employee.surveillance_employee',
    'admin.clinic' => 'panel.dashboard',
    'admin.settings' => 'panel.settings',
    'pdf.questionnaire' => 'report.PDF_questionnaire',
    'pdf.audio-report' => 'report.PDF_audioReport',
    'pdf.employee' => 'report.PDF_employee',
    'pdf.usechh1' => 'report.PDF_USECHH1',
    'pdf.usechh2' => 'report.PDF_USECHH2',
    'pdf.usechh3' => 'report.PDF_USECHH3',
    'pdf.usechh4' => 'report.PDF_USECHH4',
    'pdf.usechh5i' => 'report.PDF_USECHH5i',
    'pdf.usechh5ii' => 'report.PDF_USECHH5ii',
];

foreach ($legacyViewRoutes as $name => $view) {
    if (!Route::has($name)) {
        Route::get('/legacy/' . str_replace('.', '/', $name), fn () => view($view))->name($name);
    }
}

$legacyPostRoutes = [
    'surveillance.company.store',
    'surveillance.company.update',
    'surveillance.company.destroy',
    'surveillance.employee.store',
    'surveillance.employee.update',
    'surveillance.employee.destroy',
    'surveillance.record.update',
    'surveillance.record.destroy',
    'surveillance.declaration.save',
    'surveillance.examination.save',
    'surveillance.report.fitness.save',
    'surveillance.report.removal.save',
    'surveillance.chemical-option.store',
    'audiometry.examination.save',
    'settings.header.upload',
    'settings.header.delete',
    'settings.signature.upload',
    'settings.signature.delete',
    'account.profile-photo.upload',
    'account.profile-photo.delete',
    'account.password.update',
    'logout',
];

foreach ($legacyPostRoutes as $name) {
    if (!Route::has($name)) {
        Route::post('/legacy/' . str_replace('.', '/', $name), fn () => redirect()->route('panel.dashboard'))->name($name);
    }
}
