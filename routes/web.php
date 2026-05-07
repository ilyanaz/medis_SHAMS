<?php

use App\Http\Controllers\DeveloperAuthController;
use App\Http\Controllers\DoctorAuthController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel/login')->name('home');
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
Route::get('/panel/login', [PanelController::class, 'showLogin'])->name('panel.login');
Route::post('/panel/login', [PanelController::class, 'login'])->name('login.store');
Route::get('/panel/logout', [PanelController::class, 'showLogout'])->name('panel.logout');
Route::post('/logout', [PanelController::class, 'logout'])->name('logout');
Route::get('/panel/dashboard', [PanelController::class, 'dashboard'])->name('panel.dashboard');
Route::get('/panel/admin-dashboard', [PanelController::class, 'adminDashboard'])->name('panel.admin_dashboard');
Route::get('/panel/doctors', [PanelController::class, 'doctorList'])->name('panel.doctor_list');
Route::get('/panel/doctor-setup', [PanelController::class, 'doctorSetup'])->name('panel.doctor_setup');
Route::post('/panel/doctor-setup', [PanelController::class, 'storeDoctor'])->name('panel.doctor_setup.store');
Route::get('/panel/doctors/{doctor}', [PanelController::class, 'doctorShow'])->name('panel.doctor.show');
Route::get('/panel/doctors/{doctor}/edit', [PanelController::class, 'doctorEdit'])->name('panel.doctor.edit');
Route::put('/panel/doctors/{doctor}', [PanelController::class, 'updateDoctor'])->name('panel.doctor.update');
Route::patch('/panel/doctors/{doctor}/status', [PanelController::class, 'updateDoctorStatus'])->name('panel.doctor.status');
Route::delete('/panel/doctors/{doctor}', [PanelController::class, 'destroyDoctor'])->name('panel.doctor.destroy');
Route::get('/panel/clinics', [PanelController::class, 'clinicList'])->name('panel.clinic_list');
Route::get('/panel/clinic-setup', [PanelController::class, 'clinicSetup'])->name('panel.clinic_setup');
Route::post('/panel/clinic-setup', [PanelController::class, 'storeClinic'])->name('panel.clinic_setup.store');
Route::get('/panel/clinics/{clinic}', [PanelController::class, 'clinicShow'])->name('panel.clinic.show');
Route::get('/panel/clinics/{clinic}/edit', [PanelController::class, 'clinicEdit'])->name('panel.clinic.edit');
Route::put('/panel/clinics/{clinic}', [PanelController::class, 'updateClinic'])->name('panel.clinic.update');
Route::patch('/panel/clinics/{clinic}/status', [PanelController::class, 'updateClinicStatus'])->name('panel.clinic.status');
Route::delete('/panel/clinics/{clinic}', [PanelController::class, 'destroyClinic'])->name('panel.clinic.destroy');
Route::post('/panel/clinics/{clinic}/switch', [PanelController::class, 'switchClinic'])->name('panel.clinic.switch');
Route::post('/panel/admin-mode', [PanelController::class, 'switchAdmin'])->name('panel.admin.switch');
Route::get('/panel/settings', [PanelController::class, 'adminSettings'])->name('panel.settings');
Route::view('/panel/account-settings', 'panel.account_settings')->name('panel.account_settings');
Route::view('/panel/forgot-password', 'panel.forgot_password')->name('panel.forgot_password');

Route::get('/admin/dashboard', [PanelController::class, 'adminDashboard'])->name('admin.dashboard');
Route::get('/admin/doctors', [PanelController::class, 'doctorList'])->name('admin.doctor_list');
Route::get('/admin/doctor-setup', [PanelController::class, 'doctorSetup'])->name('admin.doctor_setup');
Route::post('/admin/doctor-setup', [PanelController::class, 'storeDoctor'])->name('admin.doctor_setup.store');
Route::get('/admin/doctors/{doctor}', [PanelController::class, 'doctorShow'])->name('admin.doctor.show');
Route::get('/admin/doctors/{doctor}/edit', [PanelController::class, 'doctorEdit'])->name('admin.doctor.edit');
Route::put('/admin/doctors/{doctor}', [PanelController::class, 'updateDoctor'])->name('admin.doctor.update');
Route::patch('/admin/doctors/{doctor}/status', [PanelController::class, 'updateDoctorStatus'])->name('admin.doctor.status');
Route::delete('/admin/doctors/{doctor}', [PanelController::class, 'destroyDoctor'])->name('admin.doctor.destroy');
Route::get('/admin/clinics', [PanelController::class, 'clinicList'])->name('admin.clinic_list');
Route::get('/admin/clinic-setup', [PanelController::class, 'clinicSetup'])->name('admin.clinic_setup');
Route::post('/admin/clinic-setup', [PanelController::class, 'storeClinic'])->name('admin.clinic_setup.store');
Route::get('/admin/clinics/{clinic}', [PanelController::class, 'clinicShow'])->name('admin.clinic.show');
Route::get('/admin/clinics/{clinic}/edit', [PanelController::class, 'clinicEdit'])->name('admin.clinic.edit');
Route::put('/admin/clinics/{clinic}', [PanelController::class, 'updateClinic'])->name('admin.clinic.update');
Route::patch('/admin/clinics/{clinic}/status', [PanelController::class, 'updateClinicStatus'])->name('admin.clinic.status');
Route::delete('/admin/clinics/{clinic}', [PanelController::class, 'destroyClinic'])->name('admin.clinic.destroy');
Route::get('/admin/settings', [PanelController::class, 'adminSettings'])->name('admin.settings');
Route::post('/admin/settings/username', [PanelController::class, 'updateAdminUsername'])->name('admin.username.update');
Route::post('/admin/settings/password', [PanelController::class, 'updateAdminPassword'])->name('admin.password.update');

// Standard Auth Routes
Route::redirect('/login', '/panel/login')->name('login');
Route::post('/login', [PanelController::class, 'login'])->name('login.attempt');
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
    'admin.company' => 'company.surveillance_company',
    'admin.employee' => 'employee.surveillance_employee',
    'admin.clinic' => 'panel.dashboard',
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
];

foreach ($legacyPostRoutes as $name) {
    if (!Route::has($name)) {
        Route::post('/legacy/' . str_replace('.', '/', $name), fn () => redirect()->route('panel.dashboard'))->name($name);
    }
}
