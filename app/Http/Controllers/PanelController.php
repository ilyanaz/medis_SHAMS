<?php

namespace App\Http\Controllers;

use App\Mail\SurveillanceReportMail;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use DOMDocument;
use DOMXPath;

class PanelController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has('panel_user_id')) {
            return $this->redirectToHome($request);
        }

        return view('panel.login', $this->buildViewData($request));
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->first();

        if (! $user || ! $this->passwordMatches($user, $credentials['password'])) {
            return back()
                ->withErrors(['username' => 'The provided login details are incorrect.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        $request->session()->put([
            'panel_user_id' => $user->getKey(),
            'panel_user_email' => $user->email,
            'panel_user_role' => (string) $user->role,
            'panel_user_username' => (string) $user->username,
            'panel_user_original_role' => (string) $user->role,
            'panel_mode' => $this->canUseAdminMode($user) ? 'admin' : 'clinic',
        ]);

        if (! $this->canUseAdminMode($user)) {
            $defaultClinicId = $this->firstClinicId();
            if ($defaultClinicId !== null) {
                $request->session()->put('active_clinic_id', $defaultClinicId);
            }
        }

        return $this->redirectToHome($request);
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);
        $clinicId = (int) $request->session()->get('active_clinic_id', 0);

        return view('panel.dashboard', array_merge($viewData, $this->dashboardContext($clinicId)));
    }

    public function generalReport(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['companies'] = $this->reportCompanies($request);

        return view('report.general_report', $viewData);
    }

    public function generalReportFolder(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['companies'] = $this->reportCompanies($request);
        $generalReportContext = app(\App\Support\LegacyClinicContext::class)->compose('report.general_report', [], $request);
        $viewData['surveillanceReportRows'] = $generalReportContext['surveillanceReportRows'] ?? [];
        $viewData['audiometryReportRows'] = $generalReportContext['audiometryReportRows'] ?? [];

        $module = strtolower(trim((string) $request->query('module', 'surveillance')));
        if (! in_array($module, ['surveillance', 'audiometry'], true)) {
            $module = 'surveillance';
        }

        $company = trim((string) $request->query('company', ''));
        $date = trim((string) $request->query('date', ''));

        $viewData['folderModule'] = $module;
        $viewData['folderCompany'] = $company;
        $viewData['folderDate'] = $date;
        $viewData['reportEmailStatuses'] = $this->reportEmailStatuses();

        return view('report.general_report_folder', $viewData);
    }

    public function combinedUsechhAllPdf(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $baseViewData = $this->buildViewData($request, $user);
        $legacyContext = app(\App\Support\LegacyClinicContext::class);

        $declarationId = (int) $request->query('declaration_id', 0);
        $employeeId = (int) $request->query('employee_id', 0);
        $companyId = (int) $request->query('company_id', 0);
        $surveillanceId = (int) $request->query('surveillance_id', 0);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declarationQuery = DB::table('declaration');
            if ($employeeId > 0) {
                $declarationQuery->where('employee_id', $employeeId);
            }
            if ($companyId > 0) {
                $declarationQuery->where('company_id', $companyId);
            }
            if ($surveillanceId > 0) {
                $declarationQuery->where('surveillance_id', $surveillanceId);
            }
            $declaration = $declarationQuery->orderByDesc('declaration_id')->first();
        }

        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $companyId = (int) ($declaration->company_id ?? $companyId);
        $surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
        $declarationId = (int) ($declaration->declaration_id ?? $declarationId);

        $selectedCompany = $companyId > 0 && Schema::hasTable('company')
            ? DB::table('company')->where('company_id', $companyId)->first()
            : null;
        $selectedEmployee = $employeeId > 0 && Schema::hasTable('employee')
            ? DB::table('employee')->where('employee_id', $employeeId)->first()
            : null;
        $doctor = ! empty($declaration->doctor_id) && Schema::hasTable('doctor')
            ? DB::table('doctor')->where('doctor_id', (int) $declaration->doctor_id)->first()
            : null;

        $usechh1Context = array_merge(
            $baseViewData,
            $legacyContext->compose('report.surveillance_usechh1Report', [], $request),
            ['pdfMode' => true]
        );
        $usechh2Context = array_merge(
            $baseViewData,
            $legacyContext->compose('report.surveillance_fitnessReport.summaryEmpReport', [], $request)
        );
        $usechh3Context = array_merge(
            $baseViewData,
            $this->buildUsechh3ReportContext($request, $user),
            ['pdfDownloadMode' => true]
        );
        $combinedSections = $this->buildCombinedUsechhAllSections($baseViewData, $legacyContext, $request, $declaration);

        return view('report.PDF_USECHH_ALL', array_merge($baseViewData, [
            'combinedSections' => $combinedSections,
        ]));
    }

    public function downloadUsechh1Pdf(Request $request)
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $baseViewData = $this->buildViewData($request, $user);
        $legacyContext = app(\App\Support\LegacyClinicContext::class);
        $viewData = array_merge(
            $baseViewData,
            $legacyContext->compose('report.surveillance_usechh1Report', [], $request),
            ['pdfMode' => true]
        );

        $employee = $viewData['employeeData'] ?? (object) [];
        $workerName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
        $safeWorkerName = trim(preg_replace('/[\\\\\\/:*?"<>|]+/', '', $workerName)) ?: 'Worker';
        $safeWorkerName = preg_replace('/\s+/', ' ', $safeWorkerName ?? '');
        $filename = 'USECHH1 - ' . trim((string) $safeWorkerName) . '.pdf';

        $clinicHeaderPath = trim((string) ($viewData['activeClinic']->clinic_header_path ?? ''));
        if ($clinicHeaderPath !== '') {
            $localClinicHeaderPath = public_path(ltrim($clinicHeaderPath, '/\\'));
            if (is_file($localClinicHeaderPath)) {
                $viewData['clinicHeaderUrl'] = $localClinicHeaderPath;
                $viewData['clinicLogoUrl'] = $localClinicHeaderPath;
            }
        }

        return Pdf::loadView('report.surveillance_usechh1Report', $viewData)
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function downloadUsechh5iPdf(Request $request)
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $declarationId = (int) $request->query('declaration_id', 0);
        $employeeId = (int) $request->query('employee_id', 0);
        $companyId = (int) $request->query('company_id', 0);
        $surveillanceId = (int) $request->query('surveillance_id', 0);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declarationQuery = DB::table('declaration');
            if ($employeeId > 0) {
                $declarationQuery->where('employee_id', $employeeId);
            }
            if ($companyId > 0) {
                $declarationQuery->where('company_id', $companyId);
            }
            if ($surveillanceId > 0) {
                $declarationQuery->where('surveillance_id', $surveillanceId);
            }
            $declaration = $declarationQuery->orderByDesc('declaration_id')->first();
        }

        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $companyId = (int) ($declaration->company_id ?? $companyId);
        $surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
        $declarationId = (int) ($declaration->declaration_id ?? $declarationId);

        $employee = $employeeId > 0 && Schema::hasTable('employee')
            ? DB::table('employee')->where('employee_id', $employeeId)->first()
            : null;
        $workerName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
        $safeWorkerName = trim(preg_replace('/[\\\\\\/:*?"<>|]+/', '', $workerName)) ?: 'Patient';
        $filename = 'USECHH5i - ' . $safeWorkerName . '.pdf';

        $viewData = $this->buildViewData($request, $user);
        $viewData['pdfDownloadMode'] = true;
        $clinicHeaderPath = trim((string) ($viewData['activeClinic']->clinic_header_path ?? ''));
        if ($clinicHeaderPath !== '') {
            $localClinicHeaderPath = public_path(ltrim($clinicHeaderPath, '/\\'));
            if (is_file($localClinicHeaderPath)) {
                $viewData['clinicHeaderUrl'] = $localClinicHeaderPath;
                $viewData['clinicLogoUrl'] = $localClinicHeaderPath;
            }
        }
        $queryBag = $request->query;
        $originalQuery = $queryBag->all();
        $queryBag->add(array_filter([
            'declaration_id' => $declarationId,
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'surveillance_id' => $surveillanceId,
        ], static fn ($value) => (int) $value > 0));
        $queryBag->set('view', '1');
        $queryBag->set('print', '1');

        try {
            return Pdf::loadView('report.surveillance_removalReport', $viewData)
                ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])
                ->setPaper('a4', 'portrait')
                ->download($filename);
        } finally {
            $queryBag->replace($originalQuery);
        }
    }

    public function downloadUsechh4Pdf(Request $request)
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $declarationId = (int) $request->query('declaration_id', 0);
        $employeeId = (int) $request->query('employee_id', 0);
        $companyId = (int) $request->query('company_id', 0);
        $surveillanceId = (int) $request->query('surveillance_id', 0);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declarationQuery = DB::table('declaration');
            if ($employeeId > 0) {
                $declarationQuery->where('employee_id', $employeeId);
            }
            if ($companyId > 0) {
                $declarationQuery->where('company_id', $companyId);
            }
            if ($surveillanceId > 0) {
                $declarationQuery->where('surveillance_id', $surveillanceId);
            }
            $declaration = $declarationQuery->orderByDesc('declaration_id')->first();
        }

        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $companyId = (int) ($declaration->company_id ?? $companyId);
        $surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
        $declarationId = (int) ($declaration->declaration_id ?? $declarationId);

        $company = $companyId > 0 && Schema::hasTable('company')
            ? DB::table('company')->where('company_id', $companyId)->first()
            : null;
        $summaryReport = $surveillanceId > 0 && Schema::hasTable('summary_report')
            ? DB::table('summary_report')->where('surveillance_id', $surveillanceId)->first()
            : null;

        $chemicalName = trim((string) ($summaryReport->chemical_name ?? 'USECHH4'));
        $safeChemicalName = trim(preg_replace('/[\\\\\\/:*?"<>|]+/', '', $chemicalName)) ?: 'USECHH4';
        $safeChemicalName = preg_replace('/\s+/', ' ', $safeChemicalName ?? '');
        $filename = 'USECHH4 - ' . trim((string) $safeChemicalName) . '.pdf';

        $viewData = $this->buildViewData($request, $user);
        $viewData['pdfDownloadMode'] = true;
        $clinicHeaderPath = trim((string) ($viewData['activeClinic']->clinic_header_path ?? ''));
        if ($clinicHeaderPath !== '') {
            $localClinicHeaderPath = public_path(ltrim($clinicHeaderPath, '/\\'));
            if (is_file($localClinicHeaderPath)) {
                $viewData['clinicHeaderUrl'] = $localClinicHeaderPath;
                $viewData['clinicLogoUrl'] = $localClinicHeaderPath;
            }
        }

        $queryBag = $request->query;
        $originalQuery = $queryBag->all();
        $queryBag->add(array_filter([
            'declaration_id' => $declarationId,
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'surveillance_id' => $surveillanceId,
        ], static fn ($value) => (int) $value > 0));
        $queryBag->set('view', '1');
        $queryBag->set('print', '1');

        try {
            return Pdf::loadView('report.surveillance_summaryReport', $viewData)
                ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])
                ->setPaper('a4', 'portrait')
                ->download($filename);
        } finally {
            $queryBag->replace($originalQuery);
        }
    }

    public function downloadUsechh2Pdf(Request $request)
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $declarationId = (int) $request->query('declaration_id', 0);
        $employeeId = (int) $request->query('employee_id', 0);
        $companyId = (int) $request->query('company_id', 0);
        $surveillanceId = (int) $request->query('surveillance_id', 0);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declarationQuery = DB::table('declaration');
            if ($employeeId > 0) {
                $declarationQuery->where('employee_id', $employeeId);
            }
            if ($companyId > 0) {
                $declarationQuery->where('company_id', $companyId);
            }
            if ($surveillanceId > 0) {
                $declarationQuery->where('surveillance_id', $surveillanceId);
            }
            $declaration = $declarationQuery->orderByDesc('declaration_id')->first();
        }

        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $companyId = (int) ($declaration->company_id ?? $companyId);
        $surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
        $declarationId = (int) ($declaration->declaration_id ?? $declarationId);

        $employee = $employeeId > 0 && Schema::hasTable('employee')
            ? DB::table('employee')->where('employee_id', $employeeId)->first()
            : null;
        $employeeName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
        $safeEmployeeName = trim(preg_replace('/[\\\\\\/:*?"<>|]+/', '', $employeeName)) ?: 'Worker';
        $safeEmployeeName = preg_replace('/\s+/', ' ', $safeEmployeeName ?? '');
        $filename = 'USECHH2 - ' . trim((string) $safeEmployeeName) . '.pdf';

        $viewData = $this->buildViewData($request, $user);
        $viewData = array_merge($viewData, $this->buildUsechh2ReportContext($request, $user, true), [
            'pdfDownloadMode' => true,
        ]);
        $clinicHeaderPath = trim((string) ($viewData['activeClinic']->clinic_header_path ?? ''));
        if ($clinicHeaderPath !== '') {
            $localClinicHeaderPath = public_path(ltrim($clinicHeaderPath, '/\\'));
            if (is_file($localClinicHeaderPath)) {
                $viewData['clinicHeaderUrl'] = $localClinicHeaderPath;
                $viewData['clinicLogoUrl'] = $localClinicHeaderPath;
            }
        }

        $queryBag = $request->query;
        $originalQuery = $queryBag->all();
        $queryBag->replace(array_filter([
            'declaration_id' => $declarationId,
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'surveillance_id' => $surveillanceId,
        ], static fn ($value) => (int) $value > 0));

        try {
            return Pdf::loadView('report.surveillance_fitnessReport.summaryEmpReport', $viewData)
                ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])
                ->setPaper('a4', 'landscape')
                ->download($filename);
        } finally {
            $queryBag->replace($originalQuery);
        }
    }

    public function downloadUsechh3Pdf(Request $request)
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $declarationId = (int) $request->query('declaration_id', 0);
        $employeeId = (int) $request->query('employee_id', 0);
        $companyId = (int) $request->query('company_id', 0);
        $surveillanceId = (int) $request->query('surveillance_id', 0);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declarationQuery = DB::table('declaration');
            if ($employeeId > 0) {
                $declarationQuery->where('employee_id', $employeeId);
            }
            if ($companyId > 0) {
                $declarationQuery->where('company_id', $companyId);
            }
            if ($surveillanceId > 0) {
                $declarationQuery->where('surveillance_id', $surveillanceId);
            }
            $declaration = $declarationQuery->orderByDesc('declaration_id')->first();
        }

        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $employee = $employeeId > 0 && Schema::hasTable('employee')
            ? DB::table('employee')->where('employee_id', $employeeId)->first()
            : null;
        $employeeName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
        $safeEmployeeName = trim(preg_replace('/[\\\\\\/:*?"<>|]+/', '', $employeeName)) ?: 'Worker';
        $safeEmployeeName = preg_replace('/\s+/', ' ', $safeEmployeeName ?? '');
        $filename = 'USECHH3 - ' . trim((string) $safeEmployeeName) . '.pdf';

        $viewData = array_merge(
            $this->buildViewData($request, $user),
            $this->buildUsechh3ReportContext($request, $user),
            ['pdfDownloadMode' => true]
        );
        $clinicHeaderPath = trim((string) ($viewData['activeClinic']->clinic_header_path ?? ''));
        if ($clinicHeaderPath !== '') {
            $localClinicHeaderPath = public_path(ltrim($clinicHeaderPath, '/\\'));
            if (is_file($localClinicHeaderPath)) {
                $viewData['clinicHeaderUrl'] = $localClinicHeaderPath;
                $viewData['clinicLogoUrl'] = $localClinicHeaderPath;
            }
        }

        return Pdf::loadView('report.surveillance_fitnessReport', $viewData)
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function downloadUsechh5iiPdf(Request $request)
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData = array_merge($viewData, $this->buildUsechh5iiReportContext($request, $user, true), [
            'pdfDownloadMode' => true,
        ]);

        $chemicalName = trim((string) ($viewData['usechh5iiChemical'] ?? 'Chemical'));
        $safeChemicalName = trim(preg_replace('/[\\\\\\/:*?"<>|]+/', '', $chemicalName)) ?: 'Chemical';
        $safeChemicalName = preg_replace('/\s+/', ' ', $safeChemicalName ?? '');
        $filename = 'USECHH5ii - ' . trim((string) $safeChemicalName) . '.pdf';
        $clinicHeaderPath = trim((string) ($viewData['activeClinic']->clinic_header_path ?? ''));
        if ($clinicHeaderPath !== '') {
            $localClinicHeaderPath = public_path(ltrim($clinicHeaderPath, '/\\'));
            if (is_file($localClinicHeaderPath)) {
                $viewData['clinicHeaderUrl'] = $localClinicHeaderPath;
                $viewData['clinicLogoUrl'] = $localClinicHeaderPath;
            }
        }

        return Pdf::loadView('report.suveillance_abnormalReport', $viewData)
            ->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true])
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    public function sendSurveillanceReportEmail(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'module' => ['required', 'string', 'max:50'],
            'report_key' => ['nullable', 'string', 'max:50'],
            'declaration_id' => ['nullable', 'integer'],
            'employee_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'surveillance_id' => ['nullable', 'integer'],
            'selected_reports' => ['nullable', 'array'],
            'selected_reports.*.report_key' => ['required_with:selected_reports', 'string', 'max:50'],
            'selected_reports.*.declaration_id' => ['nullable', 'integer'],
            'selected_reports.*.employee_id' => ['nullable', 'integer'],
            'selected_reports.*.company_id' => ['nullable', 'integer'],
            'selected_reports.*.surveillance_id' => ['nullable', 'integer'],
        ]);

        if (strtolower((string) $validated['module']) !== 'surveillance') {
            return back()->withErrors(['report_email' => 'Email sending is currently available for medical surveillance reports only.']);
        }

        $selectedReports = array_values(array_filter((array) ($validated['selected_reports'] ?? []), static fn ($row) => is_array($row)));
        if ($selectedReports === []) {
            $selectedReports[] = [
                'report_key' => (string) ($validated['report_key'] ?? ''),
                'declaration_id' => (int) ($validated['declaration_id'] ?? 0),
                'employee_id' => (int) ($validated['employee_id'] ?? 0),
                'company_id' => (int) ($validated['company_id'] ?? 0),
                'surveillance_id' => (int) ($validated['surveillance_id'] ?? 0),
            ];
        }

        $sentCount = 0;
        $skippedCount = 0;

        foreach ($selectedReports as $selectedReport) {
            $reportKey = strtolower(trim((string) ($selectedReport['report_key'] ?? '')));
            if ($reportKey !== 'all') {
                $skippedCount += 1;
                continue;
            }

            $mailPayload = $this->buildSurveillanceReportMailPayload(
                $reportKey,
                (int) ($selectedReport['declaration_id'] ?? 0),
                (int) ($selectedReport['employee_id'] ?? 0),
                (int) ($selectedReport['company_id'] ?? 0),
                (int) ($selectedReport['surveillance_id'] ?? 0),
                $request,
                $user
            );

            if (($mailPayload['recipient_email'] ?? '') === '') {
                $skippedCount += 1;
                continue;
            }

            Mail::to($mailPayload['recipient_email'])->send(
                new SurveillanceReportMail(
                    mailData: $mailPayload['mail_view_data'],
                    attachmentContent: $mailPayload['pdf_content'],
                    attachmentName: $mailPayload['attachment_name'],
                )
            );

            if (Schema::hasTable('report_email_logs')) {
                $lookup = [
                    'module' => 'surveillance',
                    'report_key' => $mailPayload['report_key'],
                    'declaration_id' => $mailPayload['declaration_id'] ?: null,
                    'employee_id' => $mailPayload['employee_id'] ?: null,
                    'company_id' => $mailPayload['company_id'] ?: null,
                    'surveillance_id' => $mailPayload['surveillance_id'] ?: null,
                ];

                $existing = DB::table('report_email_logs')->where($lookup)->first();
                $payload = [
                    'recipient_email' => $mailPayload['recipient_email'],
                    'email_subject' => 'Medical Surveillance Report',
                    'attachment_name' => $mailPayload['attachment_name'],
                    'sent_by_user_id' => (int) ($user->getKey() ?? 0) ?: null,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ];

                if ($existing) {
                    DB::table('report_email_logs')
                        ->where('report_email_log_id', $existing->report_email_log_id)
                        ->update($payload);
                } else {
                    DB::table('report_email_logs')->insert(array_merge($lookup, $payload, [
                        'created_at' => now(),
                    ]));
                }
            }

            $sentCount += 1;
        }

        if ($sentCount === 0) {
            return back()->withErrors(['report_email' => 'No selected combined ALL PDF records could be emailed.']);
        }

        $message = $sentCount === 1
            ? '1 medical surveillance report emailed successfully.'
            : $sentCount.' medical surveillance reports emailed successfully.';
        if ($skippedCount > 0) {
            $message .= ' '.$skippedCount.' selected record(s) were skipped.';
        }

        return back()->with('status', $message);
    }

    protected function buildCombinedUsechhAllSections(array $baseViewData, \App\Support\LegacyClinicContext $legacyContext, Request $request, ?object $declaration): array
    {
        $usechh1Context = array_merge(
            $baseViewData,
            $legacyContext->compose('report.surveillance_usechh1Report', [], $request),
            ['pdfMode' => true]
        );
        $usechh2Context = array_merge(
            $baseViewData,
            $this->buildUsechh2ReportContext($request, $this->resolvePanelUser($request), true),
            ['pdfDownloadMode' => true]
        );
        $usechh3Context = array_merge(
            $baseViewData,
            $this->buildUsechh3ReportContext($request, $this->resolvePanelUser($request)),
            ['pdfDownloadMode' => true]
        );

        return [
            [
                'title' => 'USECHH 1',
                'selector' => '.report-page',
                'html' => view('report.surveillance_usechh1Report', $usechh1Context)->render(),
            ],
            [
                'title' => 'Declaration',
                'selector' => '.report-page',
                'html' => view('report.PDF_declaration', $baseViewData)->render(),
            ],
            [
                'title' => 'Examination',
                'selector' => '.report-page',
                'html' => view('report.PDF_examination', $baseViewData)->render(),
            ],
            [
                'title' => 'USECHH 2',
                'selector' => '.pdf-page',
                'html' => view('report.surveillance_fitnessReport.summaryEmpReport', $usechh2Context)->render(),
            ],
            [
                'title' => 'USECHH 3',
                'selector' => '.pdf-page',
                'html' => view('report.surveillance_fitnessReport', $usechh3Context)->render(),
            ],
        ];
    }

    public function generalExamination(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['companies'] = $this->reportCompanies($request);

        return view('report.general_examination', $viewData);
    }

    public function companyList(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return view('company.company_list', $this->buildViewData($request, $user));
    }

    public function companyNew(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return view('company.new_company', $this->buildViewData($request, $user));
    }

    public function surveillanceCompanyList(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return view('surveillance.surveillance_listComp', $this->buildViewData($request, $user));
    }

    public function surveillancePatientList(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return view('surveillance.surveillance_patient', $this->buildViewData($request, $user));
    }

    public function surveillancePatientNew(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return view('surveillance.new_surveillancePatient', $this->buildViewData($request, $user));
    }

    public function surveillanceList(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return view('surveillance.surveillance_list', $this->buildViewData($request, $user));
    }

    public function surveillancePatientView(Request $request, int $employee): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $context = $this->surveillancePatientPageContext($request, $employee);
        if ($context === null) {
            return redirect()->route('surveillance.patient')->withErrors(['patient' => 'The selected patient could not be found.']);
        }

        return view('surveillance.survPatient_view', array_merge($this->buildViewData($request, $user), $context));
    }

    public function surveillancePatientEdit(Request $request, int $employee): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $context = $this->surveillancePatientPageContext($request, $employee);
        if ($context === null) {
            return redirect()->route('surveillance.patient')->withErrors(['patient' => 'The selected patient could not be found.']);
        }

        return view('surveillance.survPatient_edit', array_merge($this->buildViewData($request, $user), $context));
    }

    public function surveillancePatientDelete(Request $request, int $employee): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $context = $this->surveillancePatientPageContext($request, $employee);
        if ($context === null) {
            return redirect()->route('surveillance.patient')->withErrors(['patient' => 'The selected patient could not be found.']);
        }

        return view('surveillance.survPatient_delete', array_merge($this->buildViewData($request, $user), $context));
    }

    public function updateSurveillancePatient(Request $request, int $employee): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $context = $this->surveillancePatientPageContext($request, $employee);
        if ($context === null) {
            return redirect()->route('surveillance.patient')->withErrors(['patient' => 'The selected patient could not be found.']);
        }

        $validated = $this->validateSurveillancePatientRequest($request);
        $selectedCompany = $context['selectedCompany'];
        $selectedCompanyId = (int) ($validated['company_id'] ?? ($selectedCompany->company_id ?? 0));

        if ($selectedCompanyId > 0 && $selectedCompany === null) {
            return back()->withErrors(['company_id' => 'The selected company does not belong to the active clinic.'])->withInput();
        }

        $employeePayload = $this->surveillancePatientEmployeePayload($validated, $request, $selectedCompany);

        DB::table('employee')
            ->where('employee_id', $employee)
            ->update($employeePayload);

        $this->syncSurveillancePatientSupportingData($employee, $validated, $selectedCompany);

        return redirect()
            ->to($this->surveillancePatientReturnUrl($request, $selectedCompanyId))
            ->with('status', 'Patient updated successfully.');
    }

    public function destroySurveillancePatient(Request $request, int $employee): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $context = $this->surveillancePatientPageContext($request, $employee);
        if ($context === null) {
            return redirect()->route('surveillance.patient')->withErrors(['patient' => 'The selected patient could not be found.']);
        }

        $surveillanceIds = [];
        if (Schema::hasTable('declaration')) {
            $surveillanceIds = DB::table('declaration')
                ->where('employee_id', $employee)
                ->whereNotNull('surveillance_id')
                ->pluck('surveillance_id')
                ->filter(static fn ($value) => (int) $value > 0)
                ->map(static fn ($value) => (int) $value)
                ->values()
                ->all();
        }

        foreach ($this->surveillanceRelatedTables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($table === 'declaration') {
                DB::table($table)->where('employee_id', $employee)->delete();
                continue;
            }

            if ($surveillanceIds !== []) {
                DB::table($table)->whereIn('surveillance_id', $surveillanceIds)->delete();
            }
        }

        foreach (['medical_history', 'occupational_history', 'personal_social_history', 'training_history'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('employee_id', $employee)->delete();
            }
        }

        DB::table('employee')->where('employee_id', $employee)->delete();

        return redirect()
            ->to($this->surveillancePatientReturnUrl($request, (int) ($context['selectedCompany']->company_id ?? 0)))
            ->with('status', 'Patient deleted successfully.');
    }

    public function surveillanceDeclaration(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $companyId = (int) $request->query('company_id', 0);
        $employeeId = (int) $request->query('employee_id', 0);
        $declarationId = (int) $request->query('declaration_id', 0);

        $selectedCompany = $companyId > 0 ? $this->findCompany($request, $companyId) : null;
        if ($companyId > 0 && $selectedCompany === null) {
            return redirect()->route('surveillance.company')->withErrors(['company' => 'The selected company could not be found.']);
        }

        $employeeQuery = DB::table('employee');
        if ($employeeId > 0) {
            $employeeQuery->where('employee_id', $employeeId);
        }
        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId > 0 && Schema::hasColumn('employee', 'clinic_id')) {
            $employeeQuery->where('clinic_id', $clinicId);
        }
        $selectedEmployee = $employeeId > 0 ? $employeeQuery->first() : null;
        if ($employeeId > 0 && $selectedEmployee === null) {
            return redirect()
                ->route('surveillance.patient', array_filter(['company_id' => $companyId ?: null]))
                ->withErrors(['employee' => 'The selected patient could not be found.']);
        }

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')
                ->where('declaration_id', $declarationId)
                ->first();
        }

        $doctor = $this->resolvedSurveillanceDoctorRecord($request, $user, $declaration);

        $viewData = $this->buildViewData($request, $user);
        $viewData['selectedCompany'] = $selectedCompany;
        $viewData['selectedEmployee'] = $selectedEmployee;
        $viewData['declaration'] = $declaration;
        $viewData['declarationId'] = $declarationId > 0 ? $declarationId : null;
        $viewData['doctor'] = $doctor;

        return view('surveillance.surveillance_declaration', $viewData);
    }

    public function surveillanceExamination(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $companyId = (int) $request->query('company_id', 0);
        $employeeId = (int) $request->query('employee_id', 0);
        $declarationId = (int) $request->query('declaration_id', 0);

        $selectedCompany = $companyId > 0 ? $this->findCompany($request, $companyId) : null;
        if ($companyId > 0 && $selectedCompany === null) {
            return redirect()->route('surveillance.company')->withErrors(['company' => 'The selected company could not be found.']);
        }

        $employeeQuery = DB::table('employee');
        if ($employeeId > 0) {
            $employeeQuery->where('employee_id', $employeeId);
        }
        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId > 0 && Schema::hasColumn('employee', 'clinic_id')) {
            $employeeQuery->where('clinic_id', $clinicId);
        }
        $selectedEmployee = $employeeId > 0 ? $employeeQuery->first() : null;
        if ($employeeId > 0 && $selectedEmployee === null) {
            return redirect()
                ->route('surveillance.patient', array_filter(['company_id' => $companyId ?: null]))
                ->withErrors(['employee' => 'The selected patient could not be found.']);
        }

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')
                ->where('declaration_id', $declarationId)
                ->first();
        }

        $surveillanceId = (int) ($declaration->surveillance_id ?? 0);
        $doctor = $this->resolvedSurveillanceDoctorRecord($request, $user, $declaration);

        $patientSupportingContext = $this->surveillancePatientSupportingContext($employeeId, $selectedCompany, $selectedEmployee, $surveillanceId > 0 ? $surveillanceId : null);

        $context = [
            'chemicalInfo' => $surveillanceId > 0 && Schema::hasTable('chemical_information') ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first() : null,
            'historyOfHealth' => $surveillanceId > 0 && Schema::hasTable('history_of_health') ? DB::table('history_of_health')->where('surveillance_id', $surveillanceId)->first() : null,
            'clinicalFindings' => $surveillanceId > 0 && Schema::hasTable('clinical_findings') ? DB::table('clinical_findings')->where('surveillance_id', $surveillanceId)->first() : null,
            'physicalExam' => $surveillanceId > 0 && Schema::hasTable('physical_examination') ? DB::table('physical_examination')->where('surveillance_id', $surveillanceId)->first() : null,
            'targetOrgan' => $surveillanceId > 0 && Schema::hasTable('target_organ') ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first() : null,
            'otherTargetTests' => $this->surveillanceOtherTargetTests($surveillanceId),
            'biologicalMonitoring' => $surveillanceId > 0 && Schema::hasTable('biological_monitoring') ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first() : null,
            'fitnessRespirator' => $surveillanceId > 0 && Schema::hasTable('fitness_respirator') ? DB::table('fitness_respirator')->where('surveillance_id', $surveillanceId)->first() : null,
            'msFindings' => $surveillanceId > 0 && Schema::hasTable('ms_findings') ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first() : null,
            'recommendationData' => $surveillanceId > 0 && Schema::hasTable('recommendation') ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first() : null,
            'patientFormData' => $patientSupportingContext['patientFormData'],
        ];

        return view('surveillance.surveillance_examination', array_merge(
            $this->buildViewData($request, $user),
            $context,
            [
                'selectedCompany' => $selectedCompany,
                'selectedEmployee' => $selectedEmployee,
                'declaration' => $declaration,
                'declarationId' => $declarationId > 0 ? $declarationId : null,
                'surveillanceId' => $surveillanceId > 0 ? $surveillanceId : null,
                'doctor' => $doctor,
                'sectionStatuses' => $this->surveillanceSectionStatusesFromModels($context),
                'pageMode' => $surveillanceId > 0 ? 'edit' : 'create',
                'readOnly' => false,
            ]
        ));
    }

    public function saveSurveillanceDeclaration(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user) || $this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
            'employee_id' => ['required', 'integer', 'min:1'],
            'declaration_id' => ['nullable', 'integer', 'min:1'],
            'employee_date' => ['required', 'date'],
            'doctor_date' => ['required', 'date'],
            'employee_signature' => ['required', 'string'],
            'doctor_signature' => ['required', 'string'],
        ]);

        if (! Schema::hasTable('declaration')) {
            return redirect()->back()->withErrors(['declaration' => 'The declaration table is not available.'])->withInput();
        }

        $company = $this->findCompany($request, (int) $validated['company_id']);
        if ($company === null) {
            return redirect()->back()->withErrors(['company' => 'The selected company could not be found.'])->withInput();
        }

        $employeeQuery = DB::table('employee')->where('employee_id', (int) $validated['employee_id']);
        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId > 0 && Schema::hasColumn('employee', 'clinic_id')) {
            $employeeQuery->where('clinic_id', $clinicId);
        }
        $employee = $employeeQuery->first();
        if ($employee === null) {
            return redirect()->back()->withErrors(['employee' => 'The selected patient could not be found.'])->withInput();
        }

        $existingDeclaration = !empty($validated['declaration_id'])
            ? DB::table('declaration')->where('declaration_id', (int) $validated['declaration_id'])->first()
            : null;
        $doctor = $this->resolvedSurveillanceDoctorRecord($request, $user, $existingDeclaration);

        $payload = [
            'surveillance_id' => $existingDeclaration->surveillance_id ?? null,
            'doctor_id' => $doctor->doctor_id ?? ($existingDeclaration->doctor_id ?? null),
            'company_id' => (int) $validated['company_id'],
            'employee_id' => (int) $validated['employee_id'],
            'company_name' => trim((string) ($company->company_name ?? $request->input('company_name', ''))),
            'employee_firstName' => trim((string) ($employee->employee_firstName ?? $request->input('employee_firstName', ''))),
            'employee_lastName' => trim((string) ($employee->employee_lastName ?? $request->input('employee_lastName', ''))),
            'employee_signature' => trim((string) $validated['employee_signature']),
            'employee_date' => $validated['employee_date'],
            'doctor_signature' => trim((string) $validated['doctor_signature']),
            'doctor_date' => $validated['doctor_date'],
        ];

        if ($existingDeclaration) {
            DB::table('declaration')
                ->where('declaration_id', $existingDeclaration->declaration_id)
                ->update($payload);
            $declarationId = (int) $existingDeclaration->declaration_id;
            $surveillanceId = (int) ($existingDeclaration->surveillance_id ?? 0);
        } else {
            $declarationId = (int) DB::table('declaration')->insertGetId($payload);
            $surveillanceId = 0;
        }

        $company = $companyId ? DB::table('company')->where('company_id', $companyId)->first() : null;
        $chemicalInfo = $surveillanceId > 0 && Schema::hasTable('chemical_information')
            ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
            : null;

        $folderDate = trim((string) ($declaration->employee_date ?? $declaration->doctor_date ?? $request->input('folder_date', $chemicalInfo->examination_date ?? '')));

        return redirect()
            ->route('general.report.folder', array_filter([
                'module' => 'surveillance',
                'company' => trim((string) ($company->company_name ?? '')),
                'date' => $folderDate,
                'tab' => 'usechh 4',
            ], static fn ($value) => $value !== ''))
            ->with('status', 'USECHH 4 details saved successfully.');
    }

    public function companyShow(Request $request, int $company): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $record = $this->findCompany($request, $company);
        if ($record === null) {
            return redirect()->route('panel.company_list')->withErrors(['company' => 'The selected company could not be found.']);
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['companyRecord'] = $record;
        $viewData['companyFormData'] = $this->companyFormDefaults($record);

        return view('company.company_view', $viewData);
    }

    public function companyEdit(Request $request, int $company): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $record = $this->findCompany($request, $company);
        if ($record === null) {
            return redirect()->route('panel.company_list')->withErrors(['company' => 'The selected company could not be found.']);
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['companyRecord'] = $record;
        $viewData['companyFormData'] = $this->companyFormDefaults($record);

        return view('company.company_edit', $viewData);
    }

    public function companyDelete(Request $request, int $company): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $record = $this->findCompany($request, $company);
        if ($record === null) {
            return redirect()->route('panel.company_list')->withErrors(['company' => 'The selected company could not be found.']);
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['companyRecord'] = $record;

        return view('company.company_delete', $viewData);
    }

    public function adminDashboard(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canAccessAdminDashboard($request, $user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['adminEmail'] = (string) $user->email;
        $viewData['adminRole'] = (string) $user->role;
        $viewData['displayName'] = $this->displayName($user);
        $viewData['initials'] = $this->initials($viewData['displayName']);

        return view('admin.admin_dashboard', $viewData);
    }

    public function clinicSetup(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'create';
        $viewData['clinicFormData'] = $this->clinicFormDefaults();

        return view('clinic.clinic_setup', $viewData);
    }

    public function doctorSetup(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'create';
        $viewData['doctorFormData'] = $this->doctorFormDefaults();
        $viewData['canSaveDoctor'] = $this->canUseAdminMode($user);

        return view('doctor.doctor_setup', $viewData);
    }

    public function clinicShow(Request $request, int $clinic): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'view';
        $viewData['clinicRecord'] = $record;
        $viewData['clinicFormData'] = $this->clinicFormDefaults($record);

        return view('clinic.clinic_setup', $viewData);
    }

    public function clinicEdit(Request $request, int $clinic): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'edit';
        $viewData['clinicRecord'] = $record;
        $viewData['clinicFormData'] = $this->clinicFormDefaults($record);

        return view('clinic.clinic_setup', $viewData);
    }

    public function doctorShow(Request $request, int $doctor): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'view';
        $viewData['doctorRecord'] = $record;
        $viewData['doctorFormData'] = $this->doctorFormDefaults($record);
        $viewData['canSaveDoctor'] = false;

        return view('doctor.doctor_setup', $viewData);
    }

    public function doctorEdit(Request $request, int $doctor): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['pageMode'] = 'edit';
        $viewData['doctorRecord'] = $record;
        $viewData['doctorFormData'] = $this->doctorFormDefaults($record);
        $viewData['canSaveDoctor'] = $this->canUseAdminMode($user);

        return view('doctor.doctor_setup', $viewData);
    }

    public function doctorList(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $doctorQuery = DB::table('doctor')->select($this->doctorListColumns());
        if (Schema::hasColumn('doctor', 'doctor_status')) {
            $doctorQuery->orderByDesc('doctor_status');
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['doctors'] = $doctorQuery
            ->orderBy('doctor_firstName')
            ->orderBy('doctor_lastName')
            ->get();
        $viewData['canAddDoctor'] = $this->canUseAdminMode($user);

        return view('doctor.doctor_list', $viewData);
    }

    public function clinicList(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->isAdmin($user) && ! $this->isDoctor($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $clinicQuery = DB::table('clinic')->select($this->clinicListColumns());
        if (Schema::hasColumn('clinic', 'clinic_status')) {
            $clinicQuery->orderByDesc('clinic_status');
        }

        $viewData = $this->buildViewData($request, $user);
        $viewData['clinics'] = $clinicQuery
            ->orderBy('clinic_name')
            ->get();
        $viewData['canAddClinic'] = $this->isAdmin($user);

        return view('clinic.clinic_list', $viewData);
    }

    public function adminSettings(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        $viewData = $this->buildViewData($request, $user);
        $viewData['accountUser'] = $user;
        $viewData['doctorRecord'] = $this->linkedDoctorRecord($user);

        return view('admin.admin_setting', $viewData);
    }

    public function storeClinic(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $validated = $request->validate([
            'clinic_name' => ['required', 'string', 'max:150'],
            'clinic_email' => ['nullable', 'email', 'max:150'],
            'clinic_phone_code' => ['required', 'string', 'max:10'],
            'clinic_phone_number' => ['required', 'string', 'max:30'],
            'clinic_fax_code' => ['nullable', 'string', 'max:10'],
            'clinic_fax_number' => ['nullable', 'string', 'max:30'],
            'clinic_postcode' => ['required', 'string', 'max:10'],
            'clinic_district' => ['required', 'string', 'max:100'],
            'clinic_state' => ['required', 'string', 'max:100'],
            'registration' => ['required', 'string', 'max:100'],
            'clinic_status' => ['required', 'in:active,not active'],
            'clinic_address' => ['required', 'string', 'max:255'],
            'header_upload' => ['required', 'image', 'max:3072'],
        ]);

        $headerPath = null;
        if ($request->hasFile('header_upload')) {
            $headerPath = $this->storeUploadedFile(
                $request->file('header_upload'),
                'clinic-header-',
                'uploads/clinic-headers'
            );
        }

        $usernameBase = Str::slug($validated['clinic_name'], '');
        $candidateUsername = $usernameBase !== '' ? $usernameBase : 'clinic' . now()->timestamp;
        $uniqueUsername = $candidateUsername;
        $suffix = 1;

        while (DB::table('clinic')->where('clinic_username', $uniqueUsername)->exists()) {
            $suffix++;
            $uniqueUsername = $candidateUsername . $suffix;
        }

        $payload = [
            'clinic_name' => $validated['clinic_name'],
            'clinic_address' => $validated['clinic_address'] ?: null,
            'clinic_postcode' => $validated['clinic_postcode'] ?: null,
            'clinic_district' => $validated['clinic_district'] ?: null,
            'clinic_state' => $validated['clinic_state'] ?: null,
            'clinic_telephone' => $this->buildCountryCodeNumber(
                $validated['clinic_phone_code'] ?? null,
                $validated['clinic_phone_number'] ?? null
            ),
            'clinic_fax' => $this->buildCountryCodeNumber(
                $validated['clinic_fax_code'] ?? null,
                $validated['clinic_fax_number'] ?? null
            ),
            'clinic_email' => $validated['clinic_email'] ?: null,
            'clinic_username' => $uniqueUsername,
            'clinic_password' => Hash::make(Str::random(32)),
            'clinic_status' => $validated['clinic_status'],
        ];

        $optionalFields = [
            'clinic_registration' => $validated['registration'] ?: null,
            'clinic_header_path' => $headerPath,
        ];

        foreach ($optionalFields as $column => $value) {
            if (Schema::hasColumn('clinic', $column)) {
                $payload[$column] = $value;
            }
        }

        DB::table('clinic')->insertGetId($payload);
        $request->session()->put('panel_mode', 'admin');

        return redirect()
            ->route('admin.clinic_list')
            ->with('status', 'Clinic saved successfully. It is now available in the navigation switcher.');
    }

    public function updateClinic(Request $request, int $clinic): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $validated = $request->validate([
            'clinic_name' => ['required', 'string', 'max:150'],
            'clinic_email' => ['nullable', 'email', 'max:150'],
            'clinic_phone_code' => ['required', 'string', 'max:10'],
            'clinic_phone_number' => ['required', 'string', 'max:30'],
            'clinic_fax_code' => ['nullable', 'string', 'max:10'],
            'clinic_fax_number' => ['nullable', 'string', 'max:30'],
            'clinic_postcode' => ['required', 'string', 'max:10'],
            'clinic_district' => ['required', 'string', 'max:100'],
            'clinic_state' => ['required', 'string', 'max:100'],
            'registration' => ['required', 'string', 'max:100'],
            'clinic_status' => ['required', 'in:active,not active'],
            'clinic_address' => ['required', 'string', 'max:255'],
            'header_upload' => ['nullable', 'image', 'max:3072'],
        ]);

        $headerPath = (string) ($record->clinic_header_path ?? '');
        if ($request->hasFile('header_upload')) {
            $this->deletePublicFile($headerPath);
            $headerPath = (string) $this->storeUploadedFile(
                $request->file('header_upload'),
                'clinic-header-',
                'uploads/clinic-headers'
            );
        }

        $payload = [
            'clinic_name' => $validated['clinic_name'],
            'clinic_address' => $validated['clinic_address'] ?: null,
            'clinic_postcode' => $validated['clinic_postcode'] ?: null,
            'clinic_district' => $validated['clinic_district'] ?: null,
            'clinic_state' => $validated['clinic_state'] ?: null,
            'clinic_telephone' => $this->buildCountryCodeNumber(
                $validated['clinic_phone_code'] ?? null,
                $validated['clinic_phone_number'] ?? null
            ),
            'clinic_fax' => $this->buildCountryCodeNumber(
                $validated['clinic_fax_code'] ?? null,
                $validated['clinic_fax_number'] ?? null
            ),
            'clinic_email' => $validated['clinic_email'] ?: null,
            'clinic_status' => $validated['clinic_status'],
        ];

        $optionalFields = [
            'clinic_registration' => $validated['registration'] ?: null,
            'clinic_header_path' => $headerPath !== '' ? $headerPath : null,
        ];

        foreach ($optionalFields as $column => $value) {
            if (Schema::hasColumn('clinic', $column)) {
                $payload[$column] = $value;
            }
        }

        DB::table('clinic')
            ->where('clinic_id', $record->clinic_id)
            ->update($payload);

        return redirect()
            ->route('admin.clinic_list')
            ->with('status', 'Clinic updated successfully.');
    }

    public function updateClinicStatus(Request $request, int $clinic): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $validated = $request->validate([
            'clinic_status' => ['required', 'in:active,not active'],
        ]);

        DB::table('clinic')
            ->where('clinic_id', $record->clinic_id)
            ->update(['clinic_status' => $validated['clinic_status']]);

        return redirect()
            ->route('admin.clinic_list')
            ->with('status', 'Clinic status updated successfully.');
    }

    public function destroyClinic(Request $request, int $clinic): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canManageClinics($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findClinic($clinic);
        if ($record === null) {
            return redirect()->route('admin.clinic_list')->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $this->deletePublicFile((string) ($record->clinic_header_path ?? ''));

        DB::table('clinic')
            ->where('clinic_id', $record->clinic_id)
            ->delete();

        if ((int) $request->session()->get('active_clinic_id', 0) === (int) $record->clinic_id) {
            $request->session()->forget('active_clinic_id');
            $request->session()->put('panel_mode', 'admin');
        }

        return redirect()
            ->route('admin.clinic_list')
            ->with('status', 'Clinic deleted successfully.');
    }

    public function storeDoctor(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $validated = $request->validate([
            'doctor_firstName' => ['required', 'string', 'max:100'],
            'doctor_lastName' => ['required', 'string', 'max:100'],
            'doctor_NRIC' => ['nullable', 'string', 'max:20'],
            'doctor_passportNo' => ['nullable', 'string', 'max:30'],
            'doctor_DOB' => ['nullable', 'date'],
            'doctor_gender' => ['nullable', 'string', 'max:20'],
            'doctor_address' => ['nullable', 'string', 'max:255'],
            'doctor_postcode' => ['nullable', 'string', 'max:10'],
            'doctor_district' => ['nullable', 'string', 'max:100'],
            'doctor_state' => ['nullable', 'string', 'max:100'],
            'doctor_phone_code' => ['nullable', 'string', 'max:10'],
            'doctor_phone_number' => ['nullable', 'string', 'max:30'],
            'doctor_fax_code' => ['nullable', 'string', 'max:10'],
            'doctor_fax_number' => ['nullable', 'string', 'max:30'],
            'doctor_email' => ['nullable', 'email', 'max:150'],
            'doctor_ethnicity' => ['nullable', 'string', 'max:50'],
            'doctor_citizenship' => ['nullable', 'string', 'max:50'],
            'doctor_martialStatus' => ['nullable', 'string', 'max:30'],
            'MMC_no' => ['required', 'string', 'max:50'],
            'OHD_registrationNo' => ['required', 'string', 'max:50'],
            'doctor_status' => ['nullable', 'in:active,not active'],
            'doctor_sign_data' => ['nullable', 'string'],
            'doctor_sign_upload' => ['nullable', 'image', 'max:3072'],
            'doctor_picture' => ['nullable', 'image', 'max:3072'],
        ]);

        $uploadedSignatureFile = $request->hasFile('doctor_sign_upload') ? $request->file('doctor_sign_upload') : null;
        $drawnSignatureData = trim((string) $request->input('doctor_sign_data', ''));
        $existingSignaturePath = trim((string) $request->input('doctor_sign', ''));
        $hasDrawnSignature = $drawnSignatureData !== '' && preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $drawnSignatureData) === 1;

        if (! $uploadedSignatureFile && ! $hasDrawnSignature && $existingSignaturePath === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'doctor_sign_upload' => 'Please provide the doctor signature by upload or eSign.',
            ]);
        }

        $signaturePath = null;
        if ($uploadedSignatureFile) {
            $signaturePath = $this->storeUploadedFile(
                $uploadedSignatureFile,
                'doctor-sign-',
                'uploads/doctor-signatures'
            );
        } elseif ($hasDrawnSignature) {
            $signaturePath = $this->storeBase64Image(
                $drawnSignatureData,
                'doctor-sign-',
                'uploads/doctor-signatures'
            );
        } elseif ($existingSignaturePath !== '') {
            $signaturePath = $existingSignaturePath;
        }

        $picturePath = null;
        if ($request->hasFile('doctor_picture')) {
            $picturePath = $this->storeUploadedFile(
                $request->file('doctor_picture'),
                'doctor-picture-',
                'uploads/doctor-pictures'
            );
        }

        $usernameBase = Str::slug(trim($validated['doctor_firstName'] . $validated['doctor_lastName']), '');
        $candidateUsername = $usernameBase !== '' ? $usernameBase : 'doctor' . now()->timestamp;
        $uniqueUsername = $candidateUsername;
        $suffix = 1;

        while (DB::table('doctor')->where('doctor_username', $uniqueUsername)->exists()) {
            $suffix++;
            $uniqueUsername = $candidateUsername . $suffix;
        }

        $payload = [
            'doctor_firstName' => $validated['doctor_firstName'],
            'doctor_lastName' => $validated['doctor_lastName'],
            'doctor_NRIC' => $validated['doctor_NRIC'] ?: null,
            'doctor_passportNo' => $validated['doctor_passportNo'] ?: null,
            'doctor_DOB' => $validated['doctor_DOB'] ?: null,
            'doctor_gender' => $validated['doctor_gender'] ?: null,
            'doctor_address' => $validated['doctor_address'] ?: null,
            'doctor_postcode' => $validated['doctor_postcode'] ?: null,
            'doctor_district' => $validated['doctor_district'] ?: null,
            'doctor_state' => $validated['doctor_state'] ?: null,
            'doctor_telephone' => $this->buildCountryCodeNumber(
                $validated['doctor_phone_code'] ?? null,
                $validated['doctor_phone_number'] ?? null
            ),
            'doctor_fax' => $this->buildCountryCodeNumber(
                $validated['doctor_fax_code'] ?? null,
                $validated['doctor_fax_number'] ?? null
            ),
            'doctor_email' => $validated['doctor_email'] ?: null,
            'doctor_ethnicity' => $validated['doctor_ethnicity'] ?: null,
            'doctor_citizenship' => $validated['doctor_citizenship'] ?: null,
            'doctor_martialStatus' => $validated['doctor_martialStatus'] ?: null,
            'MMC_no' => $validated['MMC_no'] ?: null,
            'OHD_registrationNo' => $validated['OHD_registrationNo'] ?: null,
            'doctor_username' => $uniqueUsername,
            'doctor_password' => Hash::make(Str::random(32)),
            'doctor_status' => $validated['doctor_status'] ?: 'active',
            'doctor_sign' => $signaturePath,
            'doctor_picture' => $picturePath,
        ];

        DB::table('doctor')->insert($payload);
        $request->session()->put('panel_mode', 'admin');

        return redirect()
            ->route('admin.doctor_list')
            ->with('status', 'Doctor saved successfully.');
    }

    public function updateDoctor(Request $request, int $doctor): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $validated = $request->validate([
            'doctor_firstName' => ['required', 'string', 'max:100'],
            'doctor_lastName' => ['required', 'string', 'max:100'],
            'doctor_NRIC' => ['nullable', 'string', 'max:20'],
            'doctor_passportNo' => ['nullable', 'string', 'max:30'],
            'doctor_DOB' => ['nullable', 'date'],
            'doctor_gender' => ['nullable', 'string', 'max:20'],
            'doctor_address' => ['nullable', 'string', 'max:255'],
            'doctor_postcode' => ['nullable', 'string', 'max:10'],
            'doctor_district' => ['nullable', 'string', 'max:100'],
            'doctor_state' => ['nullable', 'string', 'max:100'],
            'doctor_phone_code' => ['nullable', 'string', 'max:10'],
            'doctor_phone_number' => ['nullable', 'string', 'max:30'],
            'doctor_fax_code' => ['nullable', 'string', 'max:10'],
            'doctor_fax_number' => ['nullable', 'string', 'max:30'],
            'doctor_email' => ['nullable', 'email', 'max:150'],
            'doctor_ethnicity' => ['nullable', 'string', 'max:50'],
            'doctor_citizenship' => ['nullable', 'string', 'max:50'],
            'doctor_martialStatus' => ['nullable', 'string', 'max:30'],
            'MMC_no' => ['required', 'string', 'max:50'],
            'OHD_registrationNo' => ['required', 'string', 'max:50'],
            'doctor_status' => ['nullable', 'in:active,not active'],
            'doctor_sign_data' => ['nullable', 'string'],
            'doctor_sign_upload' => ['nullable', 'image', 'max:3072'],
            'doctor_picture' => ['nullable', 'image', 'max:3072'],
        ]);

        $signaturePath = (string) ($record->doctor_sign ?? '');
        $uploadedSignatureFile = $request->hasFile('doctor_sign_upload') ? $request->file('doctor_sign_upload') : null;
        $drawnSignatureData = trim((string) $request->input('doctor_sign_data', ''));
        $hasDrawnSignature = $drawnSignatureData !== '' && preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $drawnSignatureData) === 1;

        if ($hasDrawnSignature) {
            $this->deletePublicFile($signaturePath);
            $signaturePath = (string) $this->storeBase64Image(
                $drawnSignatureData,
                'doctor-sign-',
                'uploads/doctor-signatures'
            );
        }
        if ($uploadedSignatureFile) {
            $this->deletePublicFile($signaturePath);
            $signaturePath = (string) $this->storeUploadedFile(
                $uploadedSignatureFile,
                'doctor-sign-',
                'uploads/doctor-signatures'
            );
        }

        if ($signaturePath === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'doctor_sign_upload' => 'Please provide the doctor signature by upload or eSign.',
            ]);
        }

        $picturePath = (string) ($record->doctor_picture ?? '');
        if ($request->hasFile('doctor_picture')) {
            $this->deletePublicFile($picturePath);
            $picturePath = (string) $this->storeUploadedFile(
                $request->file('doctor_picture'),
                'doctor-picture-',
                'uploads/doctor-pictures'
            );
        }

        $payload = [
            'doctor_firstName' => $validated['doctor_firstName'],
            'doctor_lastName' => $validated['doctor_lastName'],
            'doctor_NRIC' => $validated['doctor_NRIC'] ?: null,
            'doctor_passportNo' => $validated['doctor_passportNo'] ?: null,
            'doctor_DOB' => $validated['doctor_DOB'] ?: null,
            'doctor_gender' => $validated['doctor_gender'] ?: null,
            'doctor_address' => $validated['doctor_address'] ?: null,
            'doctor_postcode' => $validated['doctor_postcode'] ?: null,
            'doctor_district' => $validated['doctor_district'] ?: null,
            'doctor_state' => $validated['doctor_state'] ?: null,
            'doctor_telephone' => $this->buildCountryCodeNumber(
                $validated['doctor_phone_code'] ?? null,
                $validated['doctor_phone_number'] ?? null
            ),
            'doctor_fax' => $this->buildCountryCodeNumber(
                $validated['doctor_fax_code'] ?? null,
                $validated['doctor_fax_number'] ?? null
            ),
            'doctor_email' => $validated['doctor_email'] ?: null,
            'doctor_ethnicity' => $validated['doctor_ethnicity'] ?: null,
            'doctor_citizenship' => $validated['doctor_citizenship'] ?: null,
            'doctor_martialStatus' => $validated['doctor_martialStatus'] ?: null,
            'MMC_no' => $validated['MMC_no'] ?: null,
            'OHD_registrationNo' => $validated['OHD_registrationNo'] ?: null,
            'doctor_status' => $validated['doctor_status'] ?: ((string) ($record->doctor_status ?? 'active')),
            'doctor_sign' => $signaturePath !== '' ? $signaturePath : null,
            'doctor_picture' => $picturePath !== '' ? $picturePath : null,
        ];

        DB::table('doctor')
            ->where('doctor_id', $record->doctor_id)
            ->update($payload);

        return redirect()
            ->route('admin.doctor_list')
            ->with('status', 'Doctor updated successfully.');
    }

    public function updateDoctorStatus(Request $request, int $doctor): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $validated = $request->validate([
            'doctor_status' => ['required', 'in:active,not active'],
        ]);

        DB::table('doctor')
            ->where('doctor_id', $record->doctor_id)
            ->update(['doctor_status' => $validated['doctor_status']]);

        return redirect()
            ->route('admin.doctor_list')
            ->with('status', 'Doctor status updated successfully.');
    }

    public function destroyDoctor(Request $request, int $doctor): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $record = $this->findDoctor($doctor);
        if ($record === null) {
            return redirect()->route('admin.doctor_list')->withErrors(['doctor' => 'The selected doctor could not be found.']);
        }

        $references = $this->doctorReferenceSummary((int) $record->doctor_id);
        if ($references !== []) {
            return redirect()
                ->route('admin.doctor_list')
                ->withErrors([
                    'doctor' => 'This doctor cannot be deleted because it is still used in: ' . implode(', ', $references) . '.',
                ]);
        }

        try {
            DB::table('doctor')
                ->where('doctor_id', $record->doctor_id)
                ->delete();
        } catch (QueryException $exception) {
            return redirect()
                ->route('admin.doctor_list')
                ->withErrors([
                    'doctor' => 'This doctor cannot be deleted because related records still exist in the system.',
                ]);
        }

        $this->deletePublicFile((string) ($record->doctor_sign ?? ''));
        $this->deletePublicFile((string) ($record->doctor_picture ?? ''));

        return redirect()
            ->route('admin.doctor_list')
            ->with('status', 'Doctor deleted successfully.');
    }

    public function switchClinic(Request $request, int $clinic): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $exists = DB::table('clinic')
            ->where('clinic_id', $clinic)
            ->exists();

        if (! $exists) {
            return back()->withErrors(['clinic' => 'The selected clinic could not be found.']);
        }

        $request->session()->put([
            'active_clinic_id' => $clinic,
            'panel_mode' => 'clinic',
        ]);

        return redirect()->route('panel.dashboard');
    }

    public function updateAdminUsername(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100'],
        ]);

        $newUsername = trim((string) $validated['username']);

        $exists = User::query()
            ->where('username', $newUsername)
            ->where($user->getKeyName(), '!=', $user->getKey())
            ->exists();

        if ($exists) {
            return back()->withErrors(['username' => 'That username is already in use.'])->withInput();
        }

        User::query()
            ->where($user->getKeyName(), $user->getKey())
            ->update(['username' => $newUsername]);

        $doctorRecord = $this->linkedDoctorRecord($user);
        if ($doctorRecord !== null) {
            DB::table('doctor')
                ->where('doctor_id', $doctorRecord->doctor_id)
                ->update(['doctor_username' => $newUsername]);
        }

        $request->session()->put('panel_user_username', $newUsername);

        return redirect()
            ->route('admin.settings')
            ->with('status', 'Username updated successfully.');
    }

    public function updateAdminPassword(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], (string) $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $hashedPassword = Hash::make($validated['new_password']);

        User::query()
            ->where($user->getKeyName(), $user->getKey())
            ->update(['password' => $hashedPassword]);

        $doctorRecord = $this->linkedDoctorRecord($user);
        if ($doctorRecord !== null) {
            DB::table('doctor')
                ->where('doctor_id', $doctorRecord->doctor_id)
                ->update(['doctor_password' => $hashedPassword]);
        }

        return redirect()
            ->route('admin.settings')
            ->with('status', 'Password updated successfully.');
    }

    public function storeSurveillanceCompany(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId <= 0) {
            return redirect()->route('admin.dashboard')->withErrors([
                'clinic' => 'Select a clinic first before adding a company.',
            ]);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'mykpp_registration_no' => ['required', 'string', 'max:100'],
            'company_module' => ['required', 'in:surveillance,audiometry'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_postcode' => ['nullable', 'string', 'max:10'],
            'company_district' => ['nullable', 'string', 'max:100'],
            'company_state' => ['nullable', 'string', 'max:100'],
            'company_phone_code' => ['nullable', 'string', 'max:10'],
            'company_telephone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_fax' => ['nullable', 'string', 'max:30'],
            'work_unit_name' => ['array'],
            'work_unit_name.*' => ['nullable', 'string', 'max:150'],
            'work_unit_chemical_name' => ['array'],
            'work_unit_chemical_name.*' => ['array'],
            'work_unit_chemical_name.*.*' => ['nullable', 'string', 'max:150'],
            'work_unit_chemical_chra_report_no' => ['array'],
            'work_unit_chemical_chra_report_no.*' => ['array'],
            'work_unit_chemical_chra_report_no.*.*' => ['nullable', 'string', 'max:150'],
            'work_unit_chemical_total_workers' => ['array'],
            'work_unit_chemical_total_workers.*' => ['array'],
            'work_unit_chemical_total_workers.*.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = [
            'company_name' => trim((string) $validated['company_name']),
            'mykpp_registration_no' => trim((string) $validated['mykpp_registration_no']),
            'company_address' => $this->nullableTrim($validated['company_address'] ?? null),
            'company_postcode' => $this->nullableTrim($validated['company_postcode'] ?? null),
            'company_district' => $this->nullableTrim($validated['company_district'] ?? null),
            'company_state' => $this->nullableTrim($validated['company_state'] ?? null),
            'company_telephone' => $this->buildCountryCodeNumber($validated['company_phone_code'] ?? null, $validated['company_telephone'] ?? null),
            'company_email' => $this->nullableTrim($validated['company_email'] ?? null),
            'company_fax' => $this->nullableTrim($validated['company_fax'] ?? null),
            'total_workers' => 0,
        ];

        if (Schema::hasColumn('company', 'clinic_id')) {
            $payload['clinic_id'] = $clinicId;
        }

        if (Schema::hasColumn('company', 'company_module')) {
            $payload['company_module'] = $validated['company_module'];
        }

        $companyId = (int) DB::table('company')->insertGetId($payload);
        $this->syncCompanyWorkUnits($companyId, $validated);

        return redirect()
            ->route($validated['company_module'] === 'audiometry' ? 'audiometry.company' : 'surveillance.company')
            ->with('status', 'Company saved successfully for the active clinic.');
    }

    public function updateCompany(Request $request, int $company): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $record = $this->findCompany($request, $company);
        if ($record === null) {
            return redirect()->route('panel.company_list')->withErrors(['company' => 'The selected company could not be found.']);
        }

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'mykpp_registration_no' => ['required', 'string', 'max:100'],
            'company_module' => ['required', 'in:surveillance,audiometry'],
            'company_address' => ['nullable', 'string', 'max:255'],
            'company_postcode' => ['nullable', 'string', 'max:10'],
            'company_district' => ['nullable', 'string', 'max:100'],
            'company_state' => ['nullable', 'string', 'max:100'],
            'company_phone_code' => ['nullable', 'string', 'max:10'],
            'company_telephone' => ['nullable', 'string', 'max:20'],
            'company_email' => ['nullable', 'email', 'max:150'],
            'company_fax' => ['nullable', 'string', 'max:30'],
            'work_unit_name' => ['array'],
            'work_unit_name.*' => ['nullable', 'string', 'max:150'],
            'work_unit_chemical_name' => ['array'],
            'work_unit_chemical_name.*' => ['array'],
            'work_unit_chemical_name.*.*' => ['nullable', 'string', 'max:150'],
            'work_unit_chemical_chra_report_no' => ['array'],
            'work_unit_chemical_chra_report_no.*' => ['array'],
            'work_unit_chemical_chra_report_no.*.*' => ['nullable', 'string', 'max:150'],
            'work_unit_chemical_total_workers' => ['array'],
            'work_unit_chemical_total_workers.*' => ['array'],
            'work_unit_chemical_total_workers.*.*' => ['nullable', 'integer', 'min:0'],
        ]);

        $payload = [
            'company_name' => trim((string) $validated['company_name']),
            'mykpp_registration_no' => trim((string) $validated['mykpp_registration_no']),
            'company_address' => $this->nullableTrim($validated['company_address'] ?? null),
            'company_postcode' => $this->nullableTrim($validated['company_postcode'] ?? null),
            'company_district' => $this->nullableTrim($validated['company_district'] ?? null),
            'company_state' => $this->nullableTrim($validated['company_state'] ?? null),
            'company_telephone' => $this->buildCountryCodeNumber($validated['company_phone_code'] ?? null, $validated['company_telephone'] ?? null),
            'company_email' => $this->nullableTrim($validated['company_email'] ?? null),
            'company_fax' => $this->nullableTrim($validated['company_fax'] ?? null),
            'total_workers' => 0,
        ];

        if (Schema::hasColumn('company', 'company_module')) {
            $payload['company_module'] = $validated['company_module'];
        }

        DB::table('company')
            ->where('company_id', $record->company_id)
            ->update($payload);
        $this->syncCompanyWorkUnits((int) $record->company_id, $validated);

        return redirect()
            ->route('panel.company_list')
            ->with('status', 'Company updated successfully.');
    }

    public function destroyCompany(Request $request, int $company): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $record = $this->findCompany($request, $company);
        if ($record === null) {
            return redirect()->route('panel.company_list')->withErrors(['company' => 'The selected company could not be found.']);
        }

        DB::table('company')
            ->where('company_id', $record->company_id)
            ->delete();

        return redirect()
            ->to($this->companyReturnUrl($request, $record))
            ->with('status', 'Company deleted successfully.');
    }

    public function destroyLegacyCompany(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'company_id' => ['required', 'integer', 'min:1'],
        ]);

        $record = $this->findCompany($request, (int) $validated['company_id']);
        if ($record === null) {
            return redirect()->route('panel.company_list')->withErrors(['company' => 'The selected company could not be found.']);
        }

        DB::table('company')
            ->where('company_id', $record->company_id)
            ->delete();

        return redirect()
            ->to($this->companyReturnUrl($request, $record))
            ->with('status', 'Company deleted successfully.');
    }

    public function storeSurveillanceEmployee(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId <= 0) {
            return redirect()->route('admin.dashboard')->withErrors([
                'clinic' => 'Select a clinic first before adding a patient.',
            ]);
        }

        $validated = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'employee_firstName' => ['required', 'string', 'max:100'],
            'employee_lastName' => ['required', 'string', 'max:100'],
            'employee_NRIC' => ['nullable', 'string', 'max:20'],
            'employee_passportNo' => ['nullable', 'string', 'max:30'],
            'employee_DOB' => ['nullable', 'date'],
            'employee_gender' => ['nullable', 'in:Male,Female'],
            'employee_address' => ['nullable', 'string', 'max:255'],
            'employee_postcode' => ['nullable', 'string', 'max:10'],
            'employee_district' => ['nullable', 'string', 'max:100'],
            'employee_state' => ['nullable', 'string', 'max:100'],
            'employee_phone_code' => ['nullable', 'string', 'max:10'],
            'employee_telephone' => ['nullable', 'string', 'max:20'],
            'employee_email' => ['nullable', 'email', 'max:150'],
            'employee_ethnicity' => ['nullable', 'string', 'max:50'],
            'employee_ethnicity_other' => ['nullable', 'string', 'max:1000'],
            'employee_citizenship' => ['nullable', 'string', 'max:50'],
            'employee_citizenship_other' => ['nullable', 'string', 'max:1000'],
            'employee_martialStatus' => ['nullable', 'string', 'max:50'],
            'employee_martial_other' => ['nullable', 'string', 'max:1000'],
            'no_of_children' => ['nullable', 'integer', 'min:0'],
            'years_married' => ['nullable', 'integer', 'min:0'],
            'diagnosed_history' => ['nullable', 'string'],
            'diagnosed_history_status' => ['nullable', 'in:Yes,No'],
            'medication_history' => ['nullable', 'string'],
            'medication_history_status' => ['nullable', 'in:Yes,No'],
            'admitted_history' => ['nullable', 'string'],
            'admitted_history_status' => ['nullable', 'in:Yes,No'],
            'family_history' => ['nullable', 'string'],
            'family_history_status' => ['nullable', 'in:Yes,No'],
            'others_history' => ['nullable', 'string'],
            'others_history_status' => ['nullable', 'in:Yes,No'],
            'current_job_title' => ['nullable', 'string', 'max:150'],
            'current_company_name' => ['nullable', 'string', 'max:150'],
            'current_start_employment_date' => ['nullable', 'date'],
            'current_employment_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_incidents' => ['nullable', 'string'],
            'occup_job_title' => ['array'],
            'occup_job_title.*' => ['nullable', 'string', 'max:150'],
            'occup_company_name' => ['array'],
            'occup_company_name.*' => ['nullable', 'string', 'max:150'],
            'start_employment_date' => ['array'],
            'start_employment_date.*' => ['nullable', 'date'],
            'employment_duration' => ['array'],
            'employment_duration.*' => ['nullable', 'string', 'max:100'],
            'chemical_exposure_duration' => ['array'],
            'chemical_exposure_duration.*' => ['nullable', 'string', 'max:100'],
            'chemical_exposure_incidents' => ['array'],
            'chemical_exposure_incidents.*' => ['nullable', 'string'],
            'smoking_history' => ['nullable', 'string', 'max:50'],
            'years_of_smoking' => ['nullable', 'integer', 'min:0'],
            'no_of_cigarettes' => ['nullable', 'integer', 'min:0'],
            'vaping_history' => ['nullable', 'string', 'max:10'],
            'years_of_vaping' => ['nullable', 'integer', 'min:0'],
            'hobby' => ['nullable', 'string'],
            'handling_of_chemical' => ['nullable', 'string', 'max:10'],
            'chemical_comments' => ['nullable', 'string'],
            'sign_symptoms' => ['nullable', 'string', 'max:10'],
            'sign_comments' => ['nullable', 'string'],
            'chemical_poisoning' => ['nullable', 'string', 'max:10'],
            'poisoning_comments' => ['nullable', 'string'],
            'proper_PPE' => ['nullable', 'string', 'max:10'],
            'proper_comments' => ['nullable', 'string'],
            'PPE_usage' => ['nullable', 'string', 'max:10'],
            'usage_comments' => ['nullable', 'string'],
        ]);

        $selectedCompany = null;
        $selectedCompanyId = (int) ($validated['company_id'] ?? 0);

        if ($selectedCompanyId > 0) {
            $companyQuery = DB::table('company')->where('company_id', $selectedCompanyId);
            if (Schema::hasColumn('company', 'clinic_id')) {
                $companyQuery->where('clinic_id', $clinicId);
            }
            $selectedCompany = $companyQuery->first();

            if (! $selectedCompany) {
                return back()
                    ->withErrors(['company_id' => 'The selected company does not belong to the active clinic.'])
                    ->withInput();
            }
        }

        $employeePayload = [
            'employee_firstName' => trim((string) $validated['employee_firstName']),
            'employee_lastName' => trim((string) $validated['employee_lastName']),
            'employee_NRIC' => trim((string) ($validated['employee_NRIC'] ?? '')) ?: null,
            'employee_passportNo' => trim((string) ($validated['employee_passportNo'] ?? '')) ?: null,
            'employee_DOB' => $validated['employee_DOB'] ?? null,
            'employee_gender' => $validated['employee_gender'] ?? null,
            'employee_address' => trim((string) ($validated['employee_address'] ?? '')) ?: null,
            'employee_postcode' => trim((string) ($validated['employee_postcode'] ?? '')) ?: null,
            'employee_district' => trim((string) ($validated['employee_district'] ?? '')) ?: null,
            'employee_state' => trim((string) ($validated['employee_state'] ?? '')) ?: null,
            'employee_telephone' => $this->buildCountryCodeNumber($validated['employee_phone_code'], $validated['employee_telephone']),
            'employee_email' => trim((string) ($validated['employee_email'] ?? '')) ?: null,
            'employee_ethnicity' => trim((string) ($validated['employee_ethnicity'] ?? '')) ?: null,
            'employee_citizenship' => trim((string) ($validated['employee_citizenship'] ?? '')) ?: null,
            'employee_martialStatus' => trim((string) ($validated['employee_martialStatus'] ?? '')) ?: null,
            'no_of_children' => $validated['no_of_children'] ?? null,
            'years_married' => $validated['years_married'] ?? null,
        ];

        $employeeEthnicity = (string) ($employeePayload['employee_ethnicity'] ?? '');
        $employeeCitizenship = (string) ($employeePayload['employee_citizenship'] ?? '');
        $employeeMaritalStatus = (string) ($employeePayload['employee_martialStatus'] ?? '');

        if (Schema::hasColumn('employee', 'employee_ethnicity_other')) {
            $employeePayload['employee_ethnicity_other'] = $employeeEthnicity === 'Others'
                ? (trim((string) ($validated['employee_ethnicity_other'] ?? '')) ?: null)
                : null;
        }
        if (Schema::hasColumn('employee', 'employee_citizenship_other')) {
            $employeePayload['employee_citizenship_other'] = $employeeCitizenship === 'Others'
                ? (trim((string) ($validated['employee_citizenship_other'] ?? '')) ?: null)
                : null;
        }
        if (Schema::hasColumn('employee', 'employee_martial_other')) {
            $employeePayload['employee_martial_other'] = $employeeMaritalStatus === 'Others'
                ? (trim((string) ($validated['employee_martial_other'] ?? '')) ?: null)
                : null;
        }

        if (Schema::hasColumn('employee', 'clinic_id')) {
            $employeePayload['clinic_id'] = $clinicId;
        }
        if ($selectedCompany && Schema::hasColumn('employee', 'company_id')) {
            $employeePayload['company_id'] = (int) $selectedCompany->company_id;
        }

        $employeeId = DB::table('employee')->insertGetId($employeePayload);

        $medicalHistoryPayload = [
            'diagnosed_history' => trim((string) ($validated['diagnosed_history'] ?? '')) ?: null,
            'medication_history' => trim((string) ($validated['medication_history'] ?? '')) ?: null,
            'admitted_history' => trim((string) ($validated['admitted_history'] ?? '')) ?: null,
            'family_history' => trim((string) ($validated['family_history'] ?? '')) ?: null,
            'others_history' => trim((string) ($validated['others_history'] ?? '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => null,
        ];
        foreach (['diagnosed_history', 'medication_history', 'admitted_history', 'family_history', 'others_history'] as $field) {
            $resultColumn = $field . '_result';
            if (Schema::hasColumn('medical_history', $resultColumn)) {
                $medicalHistoryPayload[$resultColumn] = $this->medicalHistoryResultValue($validated, $field);
            }
        }
        DB::table('medical_history')->insert($medicalHistoryPayload);

        DB::table('occupational_history')->insert([
            'job_title' => trim((string) ($validated['current_job_title'] ?? '')) ?: null,
            'company_name' => trim((string) ($selectedCompany->company_name ?? ($validated['current_company_name'] ?? ''))) ?: null,
            'start_employment_date' => $validated['current_start_employment_date'] ?? null,
            'employment_duration' => trim((string) ($validated['current_employment_duration'] ?? '')) ?: null,
            'chemical_exposure_duration' => trim((string) ($validated['current_chemical_exposure_duration'] ?? '')) ?: null,
            'chemical_exposure_incidents' => trim((string) ($validated['current_chemical_exposure_incidents'] ?? '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => null,
        ]);

        $jobTitles = $validated['occup_job_title'] ?? [];
        $companyNames = $validated['occup_company_name'] ?? [];
        $startEmploymentDates = $validated['start_employment_date'] ?? [];
        $employmentDurations = $validated['employment_duration'] ?? [];
        $exposureDurations = $validated['chemical_exposure_duration'] ?? [];
        $exposureIncidents = $validated['chemical_exposure_incidents'] ?? [];

        $rowCount = max(
            count($jobTitles),
            count($companyNames),
            count($startEmploymentDates),
            count($employmentDurations),
            count($exposureDurations),
            count($exposureIncidents)
        );

        for ($index = 0; $index < $rowCount; $index++) {
            $payload = [
                'job_title' => trim((string) ($jobTitles[$index] ?? '')),
                'company_name' => trim((string) ($companyNames[$index] ?? '')),
                'start_employment_date' => $startEmploymentDates[$index] ?? null,
                'employment_duration' => trim((string) ($employmentDurations[$index] ?? '')),
                'chemical_exposure_duration' => trim((string) ($exposureDurations[$index] ?? '')),
                'chemical_exposure_incidents' => trim((string) ($exposureIncidents[$index] ?? '')),
            ];

            if (implode('', $payload) === '') {
                continue;
            }

            DB::table('occupational_history')->insert($payload + [
                'employee_id' => $employeeId,
                'surveillance_id' => null,
            ]);
        }

        DB::table('personal_social_history')->insert([
            'smoking_history' => trim((string) ($validated['smoking_history'] ?? '')) ?: null,
            'years_of_smoking' => $validated['years_of_smoking'] ?? null,
            'no_of_cigarettes' => $validated['no_of_cigarettes'] ?? null,
            'vaping_history' => trim((string) ($validated['vaping_history'] ?? '')) ?: null,
            'years_of_vaping' => $validated['years_of_vaping'] ?? null,
            'hobby' => trim((string) ($validated['hobby'] ?? '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => null,
        ]);

        DB::table('training_history')->insert([
            'handling_of_chemical' => trim((string) ($validated['handling_of_chemical'] ?? '')) ?: null,
            'chemical_comments' => trim((string) ($validated['chemical_comments'] ?? '')) ?: null,
            'sign_symptoms' => trim((string) ($validated['sign_symptoms'] ?? '')) ?: null,
            'sign_comments' => trim((string) ($validated['sign_comments'] ?? '')) ?: null,
            'chemical_poisoning' => trim((string) ($validated['chemical_poisoning'] ?? '')) ?: null,
            'poisoning_comments' => trim((string) ($validated['poisoning_comments'] ?? '')) ?: null,
            'proper_PPE' => trim((string) ($validated['proper_PPE'] ?? '')) ?: null,
            'proper_comments' => trim((string) ($validated['proper_comments'] ?? '')) ?: null,
            'PPE_usage' => trim((string) ($validated['PPE_usage'] ?? '')) ?: null,
            'usage_comments' => trim((string) ($validated['usage_comments'] ?? '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => null,
        ]);

        $redirectParams = [];
        if ($selectedCompany) {
            $redirectParams['company_id'] = $selectedCompany->company_id;
        }

        return redirect()
            ->route('surveillance.patient', $redirectParams)
            ->with('status', $selectedCompany
                ? 'Patient saved successfully for the selected company in the active clinic.'
                : 'Patient saved successfully for the active clinic.');
    }

    public function surveillanceRecordView(Request $request, int $declaration): View|RedirectResponse
    {
        return $this->renderSurveillanceRecordPage($request, $declaration, true);
    }

    public function surveillanceRecordEdit(Request $request, int $declaration): View|RedirectResponse
    {
        $declarationRecord = DB::table('declaration')->where('declaration_id', $declaration)->first();
        $surveillanceId = (int) ($declarationRecord->surveillance_id ?? 0);
        $recommendationData = $surveillanceId > 0 && Schema::hasTable('recommendation')
            ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first()
            : null;

        return $this->renderSurveillanceRecordPage($request, $declaration, $this->isRecommendationFinalized($recommendationData));
    }

    public function surveillanceRecordDelete(Request $request, int $declaration): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $record = DB::table('declaration')->where('declaration_id', $declaration)->first();
        if (! $record) {
            return redirect()->route('surveillance.list')->withErrors(['record' => 'The selected surveillance record could not be found.']);
        }

        $employee = ! empty($record->employee_id) ? DB::table('employee')->where('employee_id', $record->employee_id)->first() : null;
        $company = ! empty($record->company_id) ? DB::table('company')->where('company_id', $record->company_id)->first() : null;

        return view('surveillance.survList_delete', array_merge(
            $this->buildViewData($request, $user),
            [
                'record' => $record,
                'selectedEmployee' => $employee,
                'selectedCompany' => $company,
                'declarationId' => (int) $record->declaration_id,
                'surveillanceId' => (int) ($record->surveillance_id ?? 0),
            ]
        ));
    }

    protected function isRecommendationFinalized(?object $recommendationData): bool
    {
        if (empty($recommendationData) || empty($recommendationData->is_final)) {
            return false;
        }

        $storedRecommendationLines = preg_split('/\r\n|\r|\n/', trim((string) ($recommendationData->recommencation_type ?? ''))) ?: [];
        $needsMrpDates = collect($storedRecommendationLines)
            ->contains(static fn ($line) => trim((string) $line) === 'Permanent Medical Removal Protection');

        if ($needsMrpDates && (
            trim((string) ($recommendationData->MRPdate_start ?? '')) === ''
            || trim((string) ($recommendationData->MRPdate_end ?? '')) === ''
        )) {
            return false;
        }

        return true;
    }

    public function saveSurveillanceExamination(Request $request)
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $employeeId = (int) $request->input('employee_id', 0);
        $companyId = (int) $request->input('company_id', 0);
        $surveillanceId = (int) $request->input('surveillance_id', 0);
        $declarationId = (int) $request->input('declaration_id', 0);
        $doctorId = (int) $request->input('doctor_id', 0);
        $saveMode = trim((string) $request->input('save_mode', 'draft'));
        $isFinalSave = $saveMode === 'final';
        $isAutosave = (bool) $request->input('autosave');

        if ($employeeId <= 0 || $companyId <= 0) {
            return $this->surveillanceExamSaveResponse(
                $request,
                false,
                ['error' => 'Patient and company are required to save the surveillance examination.']
            );
        }

        $employee = DB::table('employee')->where('employee_id', $employeeId)->first();
        $company = DB::table('company')->where('company_id', $companyId)->first();
        $doctorId = $doctorId > 0 ? $doctorId : (int) optional($this->linkedDoctorRecord($user))->doctor_id;

        if (! $employee || ! $company || $doctorId <= 0) {
            return $this->surveillanceExamSaveResponse(
                $request,
                false,
                ['error' => 'Unable to resolve the employee, company, or doctor for this examination record.']
            );
        }

        $patientSupportingData = $this->validateSurveillancePatientSupportingRequest($request);

        $chemicalName = trim((string) $request->input('company_name', (string) $company->company_name));
        $chemicalSelection = trim((string) $request->input('chemicals', ''));
        $examinationType = trim((string) $request->input('examination_type', ''));
        $examinationDate = trim((string) $request->input('examination_date', ''));

        if (! $isAutosave && ($chemicalName === '' || $chemicalSelection === '' || $examinationType === '' || $examinationDate === '')) {
            return $this->surveillanceExamSaveResponse(
                $request,
                false,
                ['error' => 'Chemical Information is required before saving this examination.']
            );
        }

        $chemicalPayload = [
            'chemicals' => $chemicalSelection,
            'examination_type' => $examinationType !== '' ? $examinationType : null,
            'examination_date' => $examinationDate !== '' ? $examinationDate : null,
            'company_name' => $chemicalName,
            'employee_id' => $employeeId,
            'doctor_id' => $doctorId,
            'company_id' => $companyId,
        ];

        $surveillanceId = $this->upsertSurveillanceRow(
            'chemical_information',
            'surveillance_id',
            $surveillanceId,
            ['employee_id' => $employeeId, 'doctor_id' => $doctorId, 'company_id' => $companyId],
            $chemicalPayload
        );
        $this->syncSurveillancePatientSupportingData($employeeId, $patientSupportingData, $company, $surveillanceId);

        $existingDeclaration = $declarationId > 0
            ? DB::table('declaration')->where('declaration_id', $declarationId)->first()
            : DB::table('declaration')->where('surveillance_id', $surveillanceId)->orderByDesc('declaration_id')->first();

        $existingRecommendation = $surveillanceId > 0 && Schema::hasTable('recommendation')
            ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first()
            : null;

        if ($this->isRecommendationFinalized($existingRecommendation)) {
            return $this->surveillanceExamSaveResponse(
                $request,
                false,
                ['error' => 'This surveillance record has been finalized and can no longer be edited.']
            );
        }

        $declarationPayload = [
            'surveillance_id' => $surveillanceId,
            'doctor_id' => $doctorId,
            'company_id' => $companyId,
            'employee_id' => $employeeId,
            'company_name' => (string) $company->company_name,
            'employee_firstName' => (string) $employee->employee_firstName,
            'employee_lastName' => (string) $employee->employee_lastName,
            'employee_signature' => $existingDeclaration->employee_signature ?? null,
            'employee_date' => $existingDeclaration->employee_date ?? null,
            'doctor_signature' => $existingDeclaration->doctor_signature ?? null,
            'doctor_date' => $existingDeclaration->doctor_date ?? null,
        ];

        if ($existingDeclaration) {
            DB::table('declaration')
                ->where('declaration_id', $existingDeclaration->declaration_id)
                ->update($declarationPayload);
            $declarationId = (int) $existingDeclaration->declaration_id;
        } else {
            $declarationId = (int) DB::table('declaration')->insertGetId($declarationPayload);
        }

        $historyPayload = [
            'breathing_difficulty' => $this->nullableChoice($request->input('breathing_difficulty')),
            'cough' => $this->nullableChoice($request->input('cough')),
            'sore_throat' => $this->nullableChoice($request->input('sore_throat')),
            'sneezing' => $this->nullableChoice($request->input('sneezing')),
            'chest_pain' => $this->nullableChoice($request->input('chest_pain')),
            'palpitation' => $this->nullableChoice($request->input('palpitation')),
            'limb_oedema' => $this->nullableChoice($request->input('limb_oedema')),
            'drowsiness' => $this->nullableChoice($request->input('drowsiness')),
            'dizziness' => $this->nullableChoice($request->input('dizziness')),
            'headache' => $this->nullableChoice($request->input('headache')),
            'confusion' => $this->nullableChoice($request->input('confusion')),
            'lethargy' => $this->nullableChoice($request->input('lethargy')),
            'nausea' => $this->nullableChoice($request->input('nausea')),
            'vomiting' => $this->nullableChoice($request->input('vomiting')),
            'eye_irritations' => $this->nullableChoice($request->input('eye_irritations')),
            'blurred_vision' => $this->nullableChoice($request->input('blurred_vision')),
            'blisters' => $this->nullableChoice($request->input('blisters')),
            'burns' => $this->nullableChoice($request->input('burns')),
            'itching' => $this->nullableChoice($request->input('itching')),
            'rash' => $this->nullableChoice($request->input('rash')),
            'redness' => $this->nullableChoice($request->input('redness')),
            'abdominal_pain' => $this->nullableChoice($request->input('abdominal_pain')),
            'abdominal_mass' => $this->nullableChoice($request->input('history_abdominal_mass')),
            'jaundice' => $this->nullableChoice($request->input('history_jaundice')),
            'diarrhoea' => $this->nullableChoice($request->input('diarrhoea')),
            'loss_of_weight' => $this->nullableChoice($request->input('loss_of_weight')),
            'loss_of_appetite' => $this->nullableChoice($request->input('loss_of_appetite')),
            'dysuria' => $this->nullableChoice($request->input('dysuria')),
            'haematuria' => $this->nullableChoice($request->input('haematuria')),
            'others_symptoms' => trim((string) $request->input('others_effect', '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => $surveillanceId,
        ];
        $this->upsertSurveillanceChildRow('history_of_health', 'hoh_id', $surveillanceId, $employeeId, $historyPayload);

        $clinicalPayload = [
            'result_clinical_findings' => $this->nullableChoice($request->input('result_clinical_findings')),
            'elaboration' => trim((string) $request->input('elaboration', '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => $surveillanceId,
        ];
        $this->upsertSurveillanceChildRow('clinical_findings', 'chHistory_id', $surveillanceId, $employeeId, $clinicalPayload);

        $physicalColumns = [
            'weight', 'height', 'BMI', 'bp_systolic', 'bp_distolic', 'pulse_rate', 'respiratory_rate',
            'general_appearances', 's1_s2', 'murmur', 'ear_nose_throat', 'visual_acuity_right', 'visual_acuity_left',
            'colour_blindness', 'gas_tenderness', 'abdominal_mass', 'lymph_nodes', 'splenomegaly', 'kidney_tenderness',
            'ballotable', 'jaundice', 'hepatomegaly', 'muscle_tone', 'muscle_tenderness', 'power', 'sensation',
            'sound', 'air_entry', 'reproductive', 'skin', 'others',
        ];
        $physicalPayload = ['employee_id' => $employeeId, 'surveillance_id' => $surveillanceId];
        foreach ($physicalColumns as $column) {
            $value = $request->input($column);
            $physicalPayload[$column] = is_string($value) ? (trim($value) !== '' ? trim($value) : null) : $value;
        }
        $this->upsertSurveillanceChildRow('physical_examination', 'pexamHistory_id', $surveillanceId, $employeeId, $physicalPayload);

        $targetPayload = ['employee_id' => $employeeId, 'surveillance_id' => $surveillanceId];
        foreach ([
            'blood_comments', 'renal_comments', 'liver_comments', 'chest_comments',
            'spirometry_FEV1', 'spirometry_FVC', 'spirometry_FEV_FVC', 'spirometry_comments',
        ] as $column) {
            $value = $request->input($column);
            $targetPayload[$column] = is_string($value) ? (trim($value) !== '' ? trim($value) : null) : $value;
        }
        foreach ([
            'blood_count_result',
            'renal_function_result',
            'liver_function_result',
            'chest_xray_result',
        ] as $resultColumn) {
            $resultValue = trim((string) $request->input($resultColumn, ''));
            $targetPayload[$resultColumn] = $resultValue !== '' ? $resultValue : null;
        }
        $otherTargetTestNames = (array) $request->input('other_target_test_name', []);
        $otherTargetTestResults = (array) $request->input('other_target_test_result', []);
        $otherTargetTestComments = (array) $request->input('other_target_test_comments', []);
        $otherTargetTests = [];
        $otherTargetTestCount = max(count($otherTargetTestNames), count($otherTargetTestResults), count($otherTargetTestComments));
        for ($index = 0; $index < $otherTargetTestCount; $index++) {
            $name = trim((string) ($otherTargetTestNames[$index] ?? ''));
            $result = trim((string) ($otherTargetTestResults[$index] ?? ''));
            $comments = trim((string) ($otherTargetTestComments[$index] ?? ''));
            if ($name === '' && $result === '' && $comments === '') {
                continue;
            }

            $otherTargetTests[] = [
                'name' => $name,
                'result' => $result,
                'comments' => $comments,
            ];
        }
        if (Schema::hasTable('target_organ') && Schema::hasColumn('target_organ', 'other_tests')) {
            $targetPayload['other_tests'] = $otherTargetTests !== [] ? json_encode($otherTargetTests) : null;
        }
        $targetId = $this->upsertSurveillanceChildRow('target_organ', 'target_id', $surveillanceId, $employeeId, $targetPayload);
        $this->syncTargetOrganOtherTests($targetId, $surveillanceId, $employeeId, $otherTargetTests);

        $baselineResults = trim((string) $request->input('baseline_results', ''));
        $baselineAnnual = trim((string) $request->input('baseline_annual', ''));
        $employeeRecord = $employeeId > 0
            ? DB::table('employee')->where('employee_id', $employeeId)->first()
            : null;
        $patientNameForFile = trim(
            ((string) ($employeeRecord->employee_firstName ?? '')) . ' ' . ((string) ($employeeRecord->employee_lastName ?? ''))
        );
        $existingBloodResultFiles = [];
        $removedBloodResultFiles = array_values(array_filter(array_map(
            static fn ($path) => trim((string) $path),
            preg_split('/\r\n|\r|\n/', (string) $request->input('removed_blood_result_files', '')) ?: []
        ), static fn ($path) => $path !== ''));
        if (Schema::hasTable('biological_monitoring') && Schema::hasColumn('biological_monitoring', 'blood_result_files')) {
            $existingBiologicalRecord = $surveillanceId > 0
                ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first()
                : null;

            if ($existingBiologicalRecord && ! empty($existingBiologicalRecord->blood_result_files)) {
                $decodedBloodResultFiles = json_decode((string) $existingBiologicalRecord->blood_result_files, true);
                if (is_array($decodedBloodResultFiles)) {
                    $existingBloodResultFiles = array_values(array_filter($decodedBloodResultFiles, static fn ($path) => is_string($path) && trim($path) !== ''));
                }
            }
        }

        if (! empty($removedBloodResultFiles)) {
            foreach ($removedBloodResultFiles as $removedBloodResultFile) {
                if (in_array($removedBloodResultFile, $existingBloodResultFiles, true)) {
                    $this->deletePublicFile($removedBloodResultFile);
                }
            }

            $existingBloodResultFiles = array_values(array_filter(
                $existingBloodResultFiles,
                static fn ($path) => ! in_array($path, $removedBloodResultFiles, true)
            ));
        }

        $uploadedBloodResultInputs = $request->file('blood_result_files', []);
        $uploadedBloodResultFiles = is_array($uploadedBloodResultInputs)
            ? array_values($uploadedBloodResultInputs)
            : [$uploadedBloodResultInputs];
        $normalizedBloodResultFiles = [];
        while ($uploadedBloodResultFiles !== []) {
            $candidate = array_shift($uploadedBloodResultFiles);
            if (is_array($candidate)) {
                foreach ($candidate as $nestedCandidate) {
                    $uploadedBloodResultFiles[] = $nestedCandidate;
                }
                continue;
            }

            if ($candidate instanceof UploadedFile) {
                $normalizedBloodResultFiles[] = $candidate;
            }
        }

        $newBloodResultFiles = [];
        foreach ($normalizedBloodResultFiles as $uploadedFile) {
            if (! $uploadedFile->isValid()) {
                continue;
            }

            $storedFilename = $this->buildBloodResultStoredFilename($uploadedFile, $patientNameForFile, 'surveillance/blood-results');
            Storage::disk('public')->putFileAs('surveillance/blood-results', $uploadedFile, $storedFilename);
            $newBloodResultFiles[] = 'surveillance/blood-results/' . $storedFilename;
        }

        $mergedBloodResultFiles = array_values(array_unique(array_merge($existingBloodResultFiles, $newBloodResultFiles)));
        $biologicalPayload = [
            'biological_exposure' => ($baselineResults !== '' || $baselineAnnual !== '' || ! empty($mergedBloodResultFiles)) ? 'Yes' : null,
            'baseline_results' => $baselineResults !== '' ? $baselineResults : null,
            'baseline_annual' => $baselineAnnual !== '' ? $baselineAnnual : null,
            'employee_id' => $employeeId,
            'surveillance_id' => $surveillanceId,
        ];
        if (Schema::hasTable('biological_monitoring') && Schema::hasColumn('biological_monitoring', 'manual_completed')) {
            $biologicalPayload['manual_completed'] = $request->boolean('biological_monitoring_manual_complete') ? 1 : 0;
        }
        if (Schema::hasTable('biological_monitoring') && Schema::hasColumn('biological_monitoring', 'blood_result_files')) {
            $biologicalPayload['blood_result_files'] = ! empty($mergedBloodResultFiles) ? json_encode($mergedBloodResultFiles) : null;
        }
        $this->upsertSurveillanceChildRow('biological_monitoring', 'bioMonitor_id', $surveillanceId, $employeeId, $biologicalPayload);

        $respiratorPayload = [
            'fitness_result' => trim((string) $request->input('fitness_result', '')) ?: null,
            'fitness_justification' => trim((string) $request->input('fitness_justification', '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => $surveillanceId,
        ];
        $this->upsertSurveillanceChildRow('fitness_respirator', 'fitness_id', $surveillanceId, $employeeId, $respiratorPayload);

        $msPayload = [
            'history_of_health' => $this->nullableChoice($request->input('history_of_health')),
            'clinical_findings' => $this->nullableChoice($request->input('clinical_findings')),
            'CF_work_related' => $this->nullableChoice($request->input('CF_work_related')),
            'target_organ' => $this->nullableChoice($request->input('target_organ')),
            'TO_work_related' => $this->nullableChoice($request->input('TO_work_related')),
            'biological_monitoring' => $this->nullableChoice($request->input('biological_monitoring')),
            'BM_work_related' => $this->nullableChoice($request->input('BM_work_related')),
            'pregnancy_breastFeding' => $this->nullableChoice($request->input('pregnancy_breastFeding')),
            'conclusion_fitness' => trim((string) $request->input('conclusion_fitness', '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => $surveillanceId,
        ];
        $this->upsertSurveillanceChildRow('ms_findings', 'msFindings_id', $surveillanceId, $employeeId, $msPayload);

        $selectedRecommendationTypes = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), (array) $request->input('recommendation_types', [])), static fn ($value) => $value !== ''));
        $recommendationTypeOther = trim((string) $request->input('recommendation_type_other', ''));
        if ($recommendationTypeOther !== '') {
            $selectedRecommendationTypes[] = 'Other: ' . $recommendationTypeOther;
        }
        $activeClinic = $this->activeClinic($request);
        if (! $activeClinic && isset($company->clinic_id) && (int) $company->clinic_id > 0 && Schema::hasTable('clinic')) {
            $activeClinic = DB::table('clinic')->where('clinic_id', (int) $company->clinic_id)->first();
        }
        $recommendationSignature = trim((string) $request->input('recommendation_employee_signature', ''));
        $recommendationSignatureDate = trim((string) $request->input('recommendation_ack_date', ''));
        $recommendationPayload = [
            'recommencation_type' => ! empty($selectedRecommendationTypes) ? implode("\n", $selectedRecommendationTypes) : null,
            'MRPdate_start' => trim((string) $request->input('MRPdate_start', '')) ?: null,
            'MRPdate_end' => trim((string) $request->input('MRPdate_end', '')) ?: null,
            'nextReview_date' => trim((string) $request->input('nextReview_date', '')) ?: null,
            'notes' => trim((string) $request->input('notes', '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => $surveillanceId,
        ];
        if (Schema::hasTable('recommendation')) {
            $recommendationOptionalColumns = [
                'doctor_id' => $doctorId > 0 ? $doctorId : null,
                'clinic_id' => isset($activeClinic->clinic_id) ? (int) $activeClinic->clinic_id : null,
                'employee_signature' => $recommendationSignature !== '' ? $recommendationSignature : null,
                'employee_signature_date' => $recommendationSignatureDate !== '' ? $recommendationSignatureDate : null,
                'doctor_name' => trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? ''))) ?: null,
                'doctor_registration_no' => trim((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? '')) ?: null,
                'clinic_name' => trim((string) ($activeClinic->clinic_name ?? '')) ?: null,
                'clinic_telephone' => trim((string) ($activeClinic->clinic_telephone ?? '')) ?: null,
                'clinic_fax' => trim((string) ($activeClinic->clinic_fax ?? '')) ?: null,
                'clinic_email' => trim((string) ($activeClinic->clinic_email ?? '')) ?: null,
                'is_final' => $isFinalSave ? 1 : (! empty($existingRecommendation?->is_final) ? 1 : 0),
                'finalized_at' => $isFinalSave ? now() : ($existingRecommendation->finalized_at ?? null),
            ];
            foreach ($recommendationOptionalColumns as $column => $value) {
                if (Schema::hasColumn('recommendation', $column)) {
                    $recommendationPayload[$column] = $value;
                }
            }
        }
        $this->upsertSurveillanceChildRow('recommendation', 'recommendation_id', $surveillanceId, $employeeId, $recommendationPayload);

        $sectionStatuses = $this->surveillanceSectionStatusesFromRequest($request);

        return $this->surveillanceExamSaveResponse(
            $request,
            true,
            [
                'surveillance_id' => $surveillanceId,
                'declaration_id' => $declarationId,
                'sectionStatuses' => $sectionStatuses,
                'employee_id' => $employeeId,
                'company_id' => $companyId,
                'readOnly' => $isFinalSave,
                'save_mode' => $isFinalSave ? 'final' : 'draft',
            ]
        );
    }

    public function destroySurveillanceRecord(Request $request, int $declaration): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $record = DB::table('declaration')->where('declaration_id', $declaration)->first();
        if (! $record) {
            return redirect()->route('surveillance.list')->withErrors(['record' => 'The selected surveillance record could not be found.']);
        }

        $surveillanceId = (int) ($record->surveillance_id ?? 0);
        foreach ($this->surveillanceRelatedTables() as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if ($table === 'declaration') {
                DB::table($table)->where('declaration_id', $declaration)->delete();
                continue;
            }

            if ($surveillanceId > 0 && Schema::hasColumn($table, 'surveillance_id')) {
                DB::table($table)->where('surveillance_id', $surveillanceId)->delete();
            }
        }

        return redirect()
            ->route('surveillance.list', array_filter([
                'company_id' => $record->company_id ?? null,
                'employee_id' => $record->employee_id ?? null,
            ], static fn ($value) => $value !== null && $value !== ''))
            ->with('status', 'Surveillance record deleted successfully.');
    }

    public function saveSurveillanceFitnessReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'surveillance_id' => ['required', 'integer', 'min:1'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'declaration_id' => ['nullable', 'integer', 'min:1'],
            'remarks' => ['nullable', 'string'],
            'doctor_practice_address' => ['nullable', 'string'],
            'doctor_email_address' => ['nullable', 'string'],
            'doctor_telephone' => ['nullable', 'string'],
        ]);

        if (! Schema::hasTable('fitness_report')) {
            return redirect()->back()->with('status', 'Fitness report table is not available.');
        }

        $surveillanceId = (int) $validated['surveillance_id'];
        $remarks = trim((string) ($validated['remarks'] ?? ''));
        $doctorPracticeAddress = trim((string) ($validated['doctor_practice_address'] ?? ''));
        $doctorEmailAddress = trim((string) ($validated['doctor_email_address'] ?? ''));
        $doctorTelephone = trim((string) ($validated['doctor_telephone'] ?? ''));

        $record = DB::table('fitness_report')
            ->where('surveillance_id', $surveillanceId)
            ->first();

        if ($record) {
            DB::table('fitness_report')
                ->where('fitnessReport_id', $record->fitnessReport_id)
                ->update([
                    'remarks' => $remarks,
                    'employee_id' => $validated['employee_id'] ?? $record->employee_id,
                    'company_id' => $validated['company_id'] ?? $record->company_id,
                    'doctor_practice_address' => $doctorPracticeAddress !== '' ? $doctorPracticeAddress : null,
                    'doctor_email_address' => $doctorEmailAddress !== '' ? $doctorEmailAddress : null,
                    'doctor_telephone' => $doctorTelephone !== '' ? $doctorTelephone : null,
                ]);
        } else {
            DB::table('fitness_report')->insert([
                'result' => 'Pending review',
                'remarks' => $remarks,
                'doctor_practice_address' => $doctorPracticeAddress !== '' ? $doctorPracticeAddress : null,
                'doctor_email_address' => $doctorEmailAddress !== '' ? $doctorEmailAddress : null,
                'doctor_telephone' => $doctorTelephone !== '' ? $doctorTelephone : null,
                'employee_id' => $validated['employee_id'] ?? null,
                'surveillance_id' => $surveillanceId,
                'company_id' => $validated['company_id'] ?? null,
                'doctor_id' => null,
            ]);
        }

        $params = array_filter([
            'declaration_id' => $validated['declaration_id'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'company_id' => $validated['company_id'] ?? null,
            'surveillance_id' => $surveillanceId,
        ], static fn ($value) => $value !== null && $value !== '');

        return redirect()
            ->route('surveillance.report.fitness', $params)
            ->with('status', 'USECHH 3 report saved successfully.');
    }

    public function saveSurveillanceSummaryReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'surveillance_id' => ['required', 'integer', 'min:1'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'declaration_id' => ['nullable', 'integer', 'min:1'],
            'chemical_name' => ['nullable', 'string'],
            'totalNo_workplace' => ['nullable', 'integer', 'min:0'],
            'name_of_workUnit' => ['nullable', 'string'],
            'no_exposedWorkers' => ['nullable', 'integer', 'min:0'],
            'totalNo_examined' => ['nullable', 'integer', 'min:0'],
            'CHRA_reportNo' => ['nullable', 'string'],
            'indication_flags' => ['nullable', 'array'],
            'indication_flags.*' => ['nullable', 'string'],
            'indication_other' => ['nullable', 'string'],
            'name_of_laboratoy' => ['nullable', 'string'],
            'no_ofWorkersNormal_H' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersNormal_I' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersNormal_J' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersNormal_K' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersAbormal_OccupationalH' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersAbormal_OccupationalI' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersAbormal_nonOccupationalI' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersAbormal_OccupationalJ' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersAbormal_nonOccupationalJ' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersAbormal_OccupationalK' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersAbormal_nonOccupationalK' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersRecommended_I' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersRecommended_J' => ['nullable', 'integer', 'min:0'],
            'no_ofWorkersRecommended_K' => ['nullable', 'integer', 'min:0'],
            'specify_J' => ['nullable', 'string'],
            'specify_K' => ['nullable', 'string'],
            'totalNo_MRP' => ['nullable', 'integer', 'min:0'],
            'recommendation' => ['nullable', 'string'],
            'decision' => ['nullable', 'string'],
            'justification_decision' => ['nullable', 'string'],
            'date_of_implementation' => ['nullable', 'date'],
        ]);

        if (! Schema::hasTable('summary_report')) {
            return redirect()->back()->with('status', 'Summary report table is not available.');
        }

        $surveillanceId = (int) $validated['surveillance_id'];
        $employeeId = $validated['employee_id'] ?? null;
        $companyId = $validated['company_id'] ?? null;

        $record = DB::table('summary_report')
            ->where('surveillance_id', $surveillanceId)
            ->first();

        $declaration = null;
        if (Schema::hasTable('declaration')) {
            if (! empty($validated['declaration_id'])) {
                $declaration = DB::table('declaration')
                    ->where('declaration_id', (int) $validated['declaration_id'])
                    ->first();
            }

            if (! $declaration) {
                $declaration = DB::table('declaration')
                    ->where('surveillance_id', $surveillanceId)
                    ->when($employeeId, static fn ($query) => $query->where('employee_id', (int) $employeeId))
                    ->when($companyId, static fn ($query) => $query->where('company_id', (int) $companyId))
                    ->orderByDesc('declaration_id')
                    ->first();
            }
        }

        $user = auth()->user();
        $doctor = $this->resolvedSurveillanceDoctorRecord($request, $user, $declaration);
        $resolvedDoctorId = (int) ($record->doctor_id ?? ($doctor->doctor_id ?? ($declaration->doctor_id ?? 0)));

        if ($resolvedDoctorId <= 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['doctor_id' => 'Unable to determine the active occupational health doctor for this USECHH 4 report.']);
        }

        $indicationLabels = [
            'significant_personal_exposure' => 'Significant personal exposure (>= 50% PEL)',
            'reported_health_effects' => 'Reported health effects',
            'skin_absorption' => 'Skin absorption',
            'others' => 'Others',
        ];
        $selectedIndicationFlags = array_values(array_filter(
            array_map(static fn ($value) => trim((string) $value), (array) ($validated['indication_flags'] ?? [])),
            static fn ($value) => $value !== '' && array_key_exists($value, $indicationLabels)
        ));
        $indicationParts = [];
        foreach ($selectedIndicationFlags as $flag) {
            if ($flag === 'others') {
                $otherText = trim((string) ($validated['indication_other'] ?? ''));
                $indicationParts[] = $otherText !== '' ? 'Others: '.$otherText : 'Others';
                continue;
            }

            $indicationParts[] = $indicationLabels[$flag];
        }
        $indicationSummary = implode("\n", $indicationParts);
        if ($indicationSummary === '') {
            $indicationSummary = trim((string) ($record->indication_CHRAreport ?? ''));
        }

        $recommendedTotal = (int) ($validated['no_ofWorkersRecommended_I'] ?? ($record->no_ofWorkersRecommended_I ?? 0))
            + (int) ($validated['no_ofWorkersRecommended_J'] ?? ($record->no_ofWorkersRecommended_J ?? 0))
            + (int) ($validated['no_ofWorkersRecommended_K'] ?? ($record->no_ofWorkersRecommended_K ?? 0));

        $normalizedDecision = $this->normalizeSummaryReportDecision(
            trim((string) ($validated['decision'] ?? ($record->decision ?? '')))
        );

        $payload = [
            'employee_id' => $employeeId ?? ($record->employee_id ?? null),
            'company_id' => $companyId ?? ($record->company_id ?? null),
            'surveillance_id' => $surveillanceId,
            'doctor_id' => $resolvedDoctorId,
            'chemical_name' => trim((string) ($validated['chemical_name'] ?? ($record->chemical_name ?? ''))),
            'totalNo_workplace' => $validated['totalNo_workplace'] ?? ($record->totalNo_workplace ?? null),
            'name_of_workUnit' => trim((string) ($validated['name_of_workUnit'] ?? ($record->name_of_workUnit ?? ''))),
            'no_exposedWorkers' => $validated['no_exposedWorkers'] ?? ($record->no_exposedWorkers ?? null),
            'totalNo_examined' => $validated['totalNo_examined'] ?? ($record->totalNo_examined ?? null),
            'CHRA_reportNo' => trim((string) ($validated['CHRA_reportNo'] ?? ($record->CHRA_reportNo ?? ''))),
            'indication_CHRAreport' => $indicationSummary,
            'name_of_laboratoy' => trim((string) ($validated['name_of_laboratoy'] ?? ($record->name_of_laboratoy ?? ''))),
            'no_ofWorkersNormal_H' => $validated['no_ofWorkersNormal_H'] ?? ($record->no_ofWorkersNormal_H ?? 0),
            'no_ofWorkersNormal_I' => $validated['no_ofWorkersNormal_I'] ?? ($record->no_ofWorkersNormal_I ?? 0),
            'no_ofWorkersNormal_J' => $validated['no_ofWorkersNormal_J'] ?? ($record->no_ofWorkersNormal_J ?? 0),
            'no_ofWorkersNormal_K' => $validated['no_ofWorkersNormal_K'] ?? ($record->no_ofWorkersNormal_K ?? 0),
            'no_ofWorkersAbormal_OccupationalH' => $validated['no_ofWorkersAbormal_OccupationalH'] ?? ($record->no_ofWorkersAbormal_OccupationalH ?? 0),
            'no_ofWorkersAbormal_OccupationalI' => $validated['no_ofWorkersAbormal_OccupationalI'] ?? ($record->no_ofWorkersAbormal_OccupationalI ?? 0),
            'no_ofWorkersAbormal_nonOccupationalI' => $validated['no_ofWorkersAbormal_nonOccupationalI'] ?? ($record->no_ofWorkersAbormal_nonOccupationalI ?? 0),
            'no_ofWorkersAbormal_OccupationalJ' => $validated['no_ofWorkersAbormal_OccupationalJ'] ?? ($record->no_ofWorkersAbormal_OccupationalJ ?? 0),
            'no_ofWorkersAbormal_nonOccupationalJ' => $validated['no_ofWorkersAbormal_nonOccupationalJ'] ?? ($record->no_ofWorkersAbormal_nonOccupationalJ ?? 0),
            'no_ofWorkersAbormal_OccupationalK' => $validated['no_ofWorkersAbormal_OccupationalK'] ?? ($record->no_ofWorkersAbormal_OccupationalK ?? 0),
            'no_ofWorkersAbormal_nonOccupationalK' => $validated['no_ofWorkersAbormal_nonOccupationalK'] ?? ($record->no_ofWorkersAbormal_nonOccupationalK ?? 0),
            'no_ofWorkersRecommended_I' => $validated['no_ofWorkersRecommended_I'] ?? ($record->no_ofWorkersRecommended_I ?? 0),
            'no_ofWorkersRecommended_J' => $validated['no_ofWorkersRecommended_J'] ?? ($record->no_ofWorkersRecommended_J ?? 0),
            'no_ofWorkersRecommended_K' => $validated['no_ofWorkersRecommended_K'] ?? ($record->no_ofWorkersRecommended_K ?? 0),
            'specify_J' => trim((string) ($validated['specify_J'] ?? ($record->specify_J ?? ''))),
            'specify_K' => trim((string) ($validated['specify_K'] ?? ($record->specify_K ?? ''))),
            'totalNo_MRP' => $validated['totalNo_MRP'] ?? $recommendedTotal,
            'recommendation' => trim((string) ($validated['recommendation'] ?? ($record->recommendation ?? ''))),
            'decision' => $normalizedDecision,
            'justification_decision' => trim((string) ($validated['justification_decision'] ?? ($record->justification_decision ?? ''))),
            'date_of_implementation' => $validated['date_of_implementation'] ?? ($record->date_of_implementation ?? null),
        ];

        if ($record) {
            DB::table('summary_report')
                ->where('summaryReport_id', $record->summaryReport_id)
                ->update($payload);
        } else {
            DB::table('summary_report')->insert($payload);
        }

        $company = $companyId ? DB::table('company')->where('company_id', $companyId)->first() : null;
        $chemicalInfo = $surveillanceId > 0 && Schema::hasTable('chemical_information')
            ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
            : null;

        $folderDate = trim((string) ($declaration->employee_date ?? $declaration->doctor_date ?? $request->input('folder_date', $chemicalInfo->examination_date ?? '')));

        return redirect()
            ->route('general.report.folder', array_filter([
                'module' => 'surveillance',
                'company' => trim((string) ($company->company_name ?? '')),
                'date' => $folderDate,
                'tab' => 'usechh 4',
            ], static fn ($value) => $value !== ''))
            ->with('status', 'USECHH 4 details saved successfully.');
    }

    public function saveSurveillanceRemovalReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'surveillance_id' => ['required', 'integer', 'min:1'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'declaration_id' => ['nullable', 'integer', 'min:1'],
            'removal_type' => ['nullable', 'string'],
            'worker_identity_no' => ['nullable', 'string'],
            'worker_date_of_birth' => ['nullable', 'date'],
            'worker_sex' => ['nullable', 'string'],
            'company_name_address' => ['nullable', 'string'],
            'employment_start_date' => ['nullable', 'date'],
            'employment_duration_text' => ['nullable', 'string'],
            'health_hazard_present' => ['nullable', 'string'],
            'work_unit_department' => ['nullable', 'string'],
            'doctor_practice_address' => ['nullable', 'string'],
            'doctor_email_address' => ['nullable', 'string'],
            'doctor_telephone' => ['nullable', 'string'],
            'doctor_fax' => ['nullable', 'string'],
            'recommendation_reasons' => ['nullable', 'array'],
            'recommendation_reasons.*' => ['nullable', 'string'],
            'recommendation_reason_other' => ['nullable', 'string'],
        ]);

        if (! Schema::hasTable('removal_report')) {
            return redirect()->back()->with('status', 'Removal report table is not available.');
        }

        $surveillanceId = (int) $validated['surveillance_id'];
        $employeeId = $validated['employee_id'] ?? null;
        $companyId = $validated['company_id'] ?? null;
        $declarationId = $validated['declaration_id'] ?? null;

        $record = DB::table('removal_report')
            ->where('surveillance_id', $surveillanceId)
            ->first();
        $declaration = $declarationId && Schema::hasTable('declaration')
            ? DB::table('declaration')->where('declaration_id', $declarationId)->first()
            : null;
        $company = $companyId && Schema::hasTable('company')
            ? DB::table('company')->where('company_id', $companyId)->first()
            : null;
        $chemicalInfo = Schema::hasTable('chemical_information')
            ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
            : null;
        $fitnessRecord = Schema::hasTable('fitness_report')
            ? DB::table('fitness_report')->where('surveillance_id', $surveillanceId)->first()
            : null;

        $allowedReasonKeys = [
            'pregnancy',
            'breastfeeding',
            'abnormal_bm_bem',
            'adverse_clinical_findings',
            'target_organ_abnormality',
            'other',
        ];
        $selectedReasons = array_values(array_filter(
            array_map(static fn ($value) => trim((string) $value), (array) ($validated['recommendation_reasons'] ?? [])),
            static fn ($value) => $value !== '' && in_array($value, $allowedReasonKeys, true)
        ));
        $reasonOther = trim((string) ($validated['recommendation_reason_other'] ?? ''));
        $serializedReasons = null;
        if ($selectedReasons !== [] || $reasonOther !== '') {
            $serializedReasons = json_encode([
                'selected' => $selectedReasons,
                'other' => $reasonOther,
            ], JSON_UNESCAPED_UNICODE);
        }

        $payload = [
            'employee_id' => $employeeId ?? ($record->employee_id ?? null),
            'company_id' => $companyId ?? ($record->company_id ?? null),
            'surveillance_id' => $surveillanceId,
            'removal_type' => trim((string) ($validated['removal_type'] ?? ($record->removal_type ?? ''))),
            'reasons_recommendations' => $serializedReasons,
            'doctor_id' => $record->doctor_id ?? ($declaration->doctor_id ?? null),
            'fitnessReport_id' => $record->fitnessReport_id ?? ($fitnessRecord->fitnessReport_id ?? null),
        ];

        foreach ([
            'worker_identity_no',
            'worker_date_of_birth',
            'worker_sex',
            'company_name_address',
            'employment_start_date',
            'employment_duration_text',
            'health_hazard_present',
            'work_unit_department',
            'doctor_practice_address',
            'doctor_email_address',
            'doctor_telephone',
            'doctor_fax',
        ] as $column) {
            if (Schema::hasColumn('removal_report', $column)) {
                $payload[$column] = $validated[$column] ?? null;
            }
        }

        if ($record) {
            DB::table('removal_report')
                ->where('removalReport_id', $record->removalReport_id)
                ->update($payload);
        } else {
            DB::table('removal_report')->insert($payload);
        }

        return redirect()
            ->route('general.report.folder', array_filter([
                'module' => 'surveillance',
                'company' => trim((string) ($company->company_name ?? '')),
                'date' => trim((string) ($chemicalInfo->examination_date ?? $declaration->employee_date ?? $declaration->doctor_date ?? '')),
                'tab' => 'usechh 5i',
            ], static fn ($value) => $value !== ''))
            ->with('status', 'USECHH 5i details saved successfully.');
    }

    public function surveillanceAbnormalReport(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);

        return view('report.suveillance_abnormalReport', array_merge(
            $viewData,
            $this->buildUsechh5iiReportContext($request, $user, true)
        ));
    }

    public function surveillanceSummaryEmployeeReport(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);

        return view('report.surveillance_fitnessReport.summaryEmpReport', array_merge(
            $viewData,
            $this->buildUsechh2ReportContext($request, $user, true)
        ));
    }

    public function surveillanceFitnessReport(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);

        return view('report.surveillance_fitnessReport', array_merge(
            $viewData,
            $this->buildUsechh3ReportContext($request, $user)
        ));
    }

    public function surveillanceSummaryReport(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);

        return view('report.surveillance_summaryReport', $viewData);
    }

    public function surveillanceRemovalReport(Request $request): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $viewData = $this->buildViewData($request, $user);

        return view('report.surveillance_removalReport', $viewData);
    }

    public function saveSurveillanceSummaryEmployeeReport(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'summary_employee_report_id' => ['nullable', 'integer', 'min:1'],
            'employee_id' => ['required', 'integer', 'min:1'],
            'company_id' => ['required', 'integer', 'min:1'],
            'group_chemical' => ['required', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['nullable', 'integer', 'min:1'],
            'items.*.assessment_type' => ['nullable', 'string'],
            'items.*.history_effect' => ['nullable', 'string'],
            'items.*.clinical_findings' => ['nullable', 'string'],
            'items.*.target_organ_function' => ['nullable', 'string'],
            'items.*.bei_determinants' => ['nullable', 'string'],
            'items.*.work_relatedness' => ['nullable', 'string'],
            'items.*.conclusion' => ['nullable', 'string'],
            'items.*.mrp_date' => ['nullable', 'string'],
        ]);

        if (! Schema::hasTable('summary_employee_report') || ! Schema::hasTable('summary_employee_report_items')) {
            return back()->withErrors(['usechh2' => 'USECHH 2 tables are not available yet. Please run the migration first.']);
        }

        $context = $this->buildUsechh2ReportContext($request, $user, true);
        $report = $context['usechh2Report'] ?? null;
        if (! $report) {
            return back()->withErrors(['usechh2' => 'Unable to locate the USECHH 2 report group for saving.']);
        }

        $submittedItems = is_array($validated['items'] ?? null) ? $validated['items'] : [];
        foreach ($submittedItems as $submittedItem) {
            $itemId = (int) ($submittedItem['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            DB::table('summary_employee_report_items')
                ->where('summary_employee_report_item_id', $itemId)
                ->where('summary_employee_report_id', $report->summary_employee_report_id)
                ->update([
                    'assessment_type' => $submittedItem['assessment_type'] ?? null,
                    'history_effect' => $submittedItem['history_effect'] ?? null,
                    'clinical_findings' => $submittedItem['clinical_findings'] ?? null,
                    'target_organ_function' => $submittedItem['target_organ_function'] ?? null,
                    'bei_determinants' => $submittedItem['bei_determinants'] ?? null,
                    'work_relatedness' => $submittedItem['work_relatedness'] ?? null,
                    'conclusion' => $submittedItem['conclusion'] ?? null,
                    'mrp_date' => $submittedItem['mrp_date'] ?? null,
                    'updated_at' => now(),
                ]);
        }

        DB::table('summary_employee_report')
            ->where('summary_employee_report_id', $report->summary_employee_report_id)
            ->update(['updated_at' => now()]);

        $companyName = trim((string) DB::table('company')->where('company_id', (int) $validated['company_id'])->value('company_name'));
        $latestDate = trim((string) ($context['usechh2LatestDateRaw'] ?? ''));

        return redirect()
            ->route('general.report.folder', array_filter([
                'module' => 'surveillance',
                'company' => $companyName,
                'date' => $latestDate,
                'tab' => 'usechh 2',
            ], static fn ($value) => $value !== ''))
            ->with('status', 'USECHH 2 report saved successfully.');
    }

    public function saveSurveillanceAbnormalReport(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        $validated = $request->validate([
            'abnormal_report_id' => ['nullable', 'integer', 'min:1'],
            'company_id' => ['required', 'integer', 'min:1'],
            'group_date' => ['required', 'date'],
            'group_chemical' => ['required', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['nullable', 'integer', 'min:1'],
            'items.*.assessment_type' => ['nullable', 'string'],
            'items.*.history_effect' => ['nullable', 'string'],
            'items.*.clinical_findings' => ['nullable', 'string'],
            'items.*.target_organ_function' => ['nullable', 'string'],
            'items.*.bm_determinant' => ['nullable', 'string'],
            'items.*.work_relatedness' => ['nullable', 'string'],
            'items.*.recommendation_action' => ['nullable', 'string'],
            'items.*.conclusion' => ['nullable', 'string'],
            'items.*.designation' => ['nullable', 'string'],
        ]);

        if (! Schema::hasTable('abnormal_report') || ! Schema::hasTable('abnormal_report_items')) {
            return back()->withErrors(['usechh5ii' => 'USECHH 5ii tables are not available yet. Please run the migration first.']);
        }

        $companyId = (int) $validated['company_id'];
        $groupDate = trim((string) $validated['group_date']);
        $groupChemical = trim((string) $validated['group_chemical']);

        $context = $this->buildUsechh5iiReportContext($request, $user, true);
        $report = $context['usechh5iiReport'] ?? null;

        if (! $report) {
            return back()->withErrors(['usechh5ii' => 'Unable to locate the USECHH 5ii report group for saving.']);
        }

        $submittedItems = is_array($validated['items'] ?? null) ? $validated['items'] : [];
        foreach ($submittedItems as $submittedItem) {
            $itemId = (int) ($submittedItem['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            DB::table('abnormal_report_items')
                ->where('abnormal_report_item_id', $itemId)
                ->where('abnormal_report_id', $report->abnormal_report_id)
                ->update([
                    'designation' => $submittedItem['designation'] ?? null,
                    'assessment_type' => $submittedItem['assessment_type'] ?? null,
                    'history_effect' => $submittedItem['history_effect'] ?? null,
                    'clinical_findings' => $submittedItem['clinical_findings'] ?? null,
                    'target_organ_function' => $submittedItem['target_organ_function'] ?? null,
                    'bm_determinant' => $submittedItem['bm_determinant'] ?? null,
                    'work_relatedness' => $submittedItem['work_relatedness'] ?? null,
                    'recommendation_action' => $submittedItem['recommendation_action'] ?? null,
                    'conclusion' => $submittedItem['conclusion'] ?? null,
                    'updated_at' => now(),
                ]);
        }

        DB::table('abnormal_report')
            ->where('abnormal_report_id', $report->abnormal_report_id)
            ->update([
                'updated_at' => now(),
            ]);

        $companyName = trim((string) DB::table('company')->where('company_id', $companyId)->value('company_name'));

        return redirect()
            ->route('general.report.folder', array_filter([
                'module' => 'surveillance',
                'company' => $companyName,
                'date' => $groupDate,
                'tab' => 'usechh 5ii',
            ], static fn ($value) => $value !== ''))
            ->with('status', 'USECHH 5ii report saved successfully.');
    }

    public function switchAdmin(Request $request): RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        if (! $this->canUseAdminMode($user)) {
            return redirect()->route('panel.dashboard');
        }

        $request->session()->put('panel_mode', 'admin');

        return redirect()->route('admin.dashboard');
    }

    public function showLogout(Request $request): View
    {
        return view('panel.logout', $this->buildViewData($request, $this->resolvePanelUser($request)));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget([
            'panel_user_id',
            'panel_user_email',
            'panel_user_role',
            'panel_user_username',
            'panel_user_original_role',
            'panel_mode',
            'active_clinic_id',
            'doctor_user_id',
            'doctor_user_email',
            'doctor_user_role',
        ]);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login');
    }

    protected function buildViewData(Request $request, ?User $user = null): array
    {
        $user = $user ?? $this->resolvePanelUser($request);
        $inAdminMode = $request->session()->get('panel_mode', $this->isAdmin($user) ? 'admin' : 'clinic') === 'admin';
        $activeClinic = $inAdminMode ? null : $this->activeClinic($request);
        $clinicHeaderPath = is_object($activeClinic) && isset($activeClinic->clinic_header_path)
            ? (string) $activeClinic->clinic_header_path
            : null;

        return [
            'clinicName' => $activeClinic?->clinic_name ?? ($this->isAdmin($user) ? 'Admin' : 'Medis SHAMS'),
            'clinicLogoUrl' => $clinicHeaderPath ? asset($clinicHeaderPath) : null,
            'username' => $user ? $this->displayName($user) : 'User',
            'activeClinic' => $activeClinic,
            'panelUser' => $user,
        ];
    }

    protected function dashboardContext(int $clinicId): array
    {
        try {
            $companyCount = Schema::hasTable('company')
                ? $this->dashboardCompaniesQuery($clinicId)->count()
                : 0;

            $patientCount = Schema::hasTable('employee')
                ? $this->dashboardEmployeesQuery($clinicId)->count()
                : 0;

            $surveillanceCount = Schema::hasTable('declaration')
                ? $this->dashboardDeclarationsQuery($clinicId)->count()
                : 0;

            $audiometryCount = Schema::hasTable('audiometry_test')
                ? $this->dashboardAudiometryQuery($clinicId)->count()
                : 0;

            $pendingDeclarationCount = Schema::hasTable('declaration')
                ? (clone $this->dashboardDeclarationsQuery($clinicId))
                    ->where(function ($query) {
                        $query->whereNull('declaration.employee_signature')
                            ->orWhere('declaration.employee_signature', '')
                            ->orWhereNull('declaration.employee_date')
                            ->orWhereNull('declaration.doctor_signature')
                            ->orWhere('declaration.doctor_signature', '')
                            ->orWhereNull('declaration.doctor_date');
                    })
                    ->count()
                : 0;

            $incompleteExamCount = Schema::hasTable('declaration')
                ? (clone $this->dashboardDeclarationsQuery($clinicId))
                    ->leftJoin('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
                    ->leftJoin('recommendation', 'recommendation.surveillance_id', '=', 'declaration.surveillance_id')
                    ->where(function ($query) {
                        $query->whereNull('declaration.surveillance_id')
                            ->orWhereNull('chemical_information.surveillance_id')
                            ->orWhereNull('recommendation.surveillance_id');
                    })
                    ->count()
                : 0;

            $completedSurveillanceCount = Schema::hasTable('declaration')
                ? (clone $this->dashboardDeclarationsQuery($clinicId))
                    ->whereNotNull('declaration.employee_signature')
                    ->where('declaration.employee_signature', '!=', '')
                    ->whereNotNull('declaration.doctor_signature')
                    ->where('declaration.doctor_signature', '!=', '')
                    ->whereNotNull('declaration.surveillance_id')
                    ->count()
                : 0;

            $recentCompanies = Schema::hasTable('company')
                ? $this->dashboardCompaniesQuery($clinicId)
                    ->select([
                        'company.company_id',
                        'company.company_name',
                        'company.company_module',
                        'company.total_workers',
                        'company.company_email',
                    ])
                    ->orderByDesc('company.company_id')
                    ->limit(4)
                    ->get()
                : collect();

            $recentPatients = Schema::hasTable('employee')
                ? $this->dashboardEmployeesQuery($clinicId)
                    ->select([
                        'employee.employee_id',
                        'employee.employee_firstName',
                        'employee.employee_lastName',
                        'employee.employee_NRIC',
                        'employee.employee_passportNo',
                        'employee.employee_telephone',
                    ])
                    ->orderByDesc('employee.employee_id')
                    ->limit(4)
                    ->get()
                : collect();

            $recentSurveillance = Schema::hasTable('declaration')
                ? (clone $this->dashboardDeclarationsQuery($clinicId))
                    ->select([
                        'declaration.declaration_id',
                        'declaration.surveillance_id',
                        'declaration.employee_date',
                        'declaration.doctor_date',
                        'declaration.employee_signature',
                        'declaration.doctor_signature',
                        'company.company_name',
                        'employee.employee_firstName',
                        'employee.employee_lastName',
                    ])
                    ->orderByDesc('declaration.declaration_id')
                    ->limit(4)
                    ->get()
                : collect();

            $recentAudiometry = Schema::hasTable('audiometry_test')
                ? (clone $this->dashboardAudiometryQuery($clinicId))
                    ->select([
                        'audiometry_test.audiometry_id',
                        'audiometry_test.audioTest_date',
                        'company.company_name',
                        'employee.employee_firstName',
                        'employee.employee_lastName',
                    ])
                    ->orderByDesc('audiometry_test.audiometry_id')
                    ->limit(4)
                    ->get()
                : collect();

            $incompleteRecords = Schema::hasTable('declaration')
                ? (clone $this->dashboardDeclarationsQuery($clinicId))
                    ->leftJoin('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
                    ->leftJoin('recommendation', 'recommendation.surveillance_id', '=', 'declaration.surveillance_id')
                    ->select([
                        'declaration.declaration_id',
                        'declaration.surveillance_id',
                        'declaration.employee_id',
                        'declaration.company_id',
                        'declaration.employee_date',
                        'declaration.doctor_date',
                        'declaration.employee_signature',
                        'declaration.doctor_signature',
                        'company.company_name',
                        'employee.employee_firstName',
                        'employee.employee_lastName',
                        'chemical_information.surveillance_id as has_chemical',
                        'recommendation.surveillance_id as has_recommendation',
                    ])
                    ->where(function ($query) {
                        $query->whereNull('declaration.employee_signature')
                            ->orWhere('declaration.employee_signature', '')
                            ->orWhereNull('declaration.doctor_signature')
                            ->orWhere('declaration.doctor_signature', '')
                            ->orWhereNull('declaration.surveillance_id')
                            ->orWhereNull('chemical_information.surveillance_id')
                            ->orWhereNull('recommendation.surveillance_id');
                    })
                    ->orderByDesc('declaration.declaration_id')
                    ->limit(8)
                    ->get()
                    ->map(function ($row) {
                        $patientName = trim(((string) ($row->employee_firstName ?? '')) . ' ' . ((string) ($row->employee_lastName ?? '')));
                        $hasPatientSignature = ! empty($row->employee_signature);
                        $hasDoctorSignature = ! empty($row->doctor_signature);
                        $hasExam = ! empty($row->surveillance_id) && ! empty($row->has_chemical);
                        $hasRecommendation = ! empty($row->has_recommendation);

                        if (! $hasPatientSignature || ! $hasDoctorSignature) {
                            $step = 'Declaration';
                        } elseif (! $hasExam) {
                            $step = 'Examination';
                        } elseif (! $hasRecommendation) {
                            $step = 'Recommendation';
                        } else {
                            $step = 'Review';
                        }

                        return [
                            'module' => 'Surveillance',
                            'patient_name' => $patientName !== '' ? $patientName : 'Not set',
                            'company_name' => (string) ($row->company_name ?? '-'),
                            'current_step' => $step,
                            'last_updated' => (string) ($row->employee_date ?: $row->doctor_date ?: ('#SUR' . $row->declaration_id)),
                            'action_url' => ! empty($row->declaration_id)
                                ? route('surveillance.record.edit', ['declaration' => $row->declaration_id])
                                : route('surveillance.declaration', array_filter([
                                    'company_id' => $row->company_id ?? null,
                                    'employee_id' => $row->employee_id ?? null,
                                ])),
                        ];
                    })
                : collect();
        } catch (QueryException $exception) {
            $companyCount = 0;
            $patientCount = 0;
            $surveillanceCount = 0;
            $audiometryCount = 0;
            $pendingDeclarationCount = 0;
            $incompleteExamCount = 0;
            $completedSurveillanceCount = 0;
            $recentCompanies = collect();
            $recentPatients = collect();
            $recentSurveillance = collect();
            $recentAudiometry = collect();
            $incompleteRecords = collect();
        }

        return [
            'dashboardStats' => [
                'company_total' => $companyCount,
                'patient_total' => $patientCount,
                'surveillance_total' => $surveillanceCount,
                'audiometry_total' => $audiometryCount,
                'pending_total' => $pendingDeclarationCount + $incompleteExamCount,
            ],
            'dashboardActionItems' => [
                [
                    'label' => 'Pending declarations',
                    'count' => $pendingDeclarationCount,
                    'note' => 'Patients or doctors still need to complete signatures.',
                ],
                [
                    'label' => 'Incomplete examinations',
                    'count' => $incompleteExamCount,
                    'note' => 'Surveillance forms started but not fully completed.',
                ],
                [
                    'label' => 'Completed surveillance',
                    'count' => $completedSurveillanceCount,
                    'note' => 'Records ready for review and follow-up reporting.',
                ],
                [
                    'label' => 'Audiometry records',
                    'count' => $audiometryCount,
                    'note' => 'Audiometry activity stored under this clinic.',
                ],
            ],
            'recentCompanies' => $recentCompanies,
            'recentPatients' => $recentPatients,
            'recentSurveillance' => $recentSurveillance,
            'recentAudiometry' => $recentAudiometry,
            'dashboardIncompleteRecords' => $incompleteRecords,
        ];
    }

    protected function dashboardCompaniesQuery(int $clinicId)
    {
        $query = DB::table('company');
        if ($clinicId > 0 && Schema::hasColumn('company', 'clinic_id')) {
            $query->where('company.clinic_id', $clinicId);
        }

        return $query;
    }

    protected function dashboardEmployeesQuery(int $clinicId)
    {
        $query = DB::table('employee');
        if ($clinicId > 0 && Schema::hasColumn('employee', 'clinic_id')) {
            $query->where('employee.clinic_id', $clinicId);
        }

        return $query;
    }

    protected function dashboardDeclarationsQuery(int $clinicId)
    {
        $query = DB::table('declaration')
            ->leftJoin('company', 'company.company_id', '=', 'declaration.company_id')
            ->leftJoin('employee', 'employee.employee_id', '=', 'declaration.employee_id');

        if ($clinicId > 0) {
            if (Schema::hasColumn('company', 'clinic_id')) {
                $query->where('company.clinic_id', $clinicId);
            } elseif (Schema::hasColumn('employee', 'clinic_id')) {
                $query->where('employee.clinic_id', $clinicId);
            }
        }

        return $query;
    }

    protected function dashboardAudiometryQuery(int $clinicId)
    {
        $query = DB::table('audiometry_test')
            ->leftJoin('company', 'company.company_id', '=', 'audiometry_test.company_id')
            ->leftJoin('employee', 'employee.employee_id', '=', 'audiometry_test.employee_id');

        if ($clinicId > 0) {
            if (Schema::hasColumn('company', 'clinic_id')) {
                $query->where('company.clinic_id', $clinicId);
            } elseif (Schema::hasColumn('employee', 'clinic_id')) {
                $query->where('employee.clinic_id', $clinicId);
            }
        }

        return $query;
    }

    protected function reportCompanies(Request $request)
    {
        if (! Schema::hasTable('company')) {
            return collect();
        }

        $clinicId = (int) $request->session()->get('active_clinic_id', 0);

        return $this->dashboardCompaniesQuery($clinicId)
            ->select(['company.company_id', 'company.company_name'])
            ->orderBy('company.company_name')
            ->get();
    }

    protected function requirePanelUser(Request $request): User|RedirectResponse
    {
        $user = $this->resolvePanelUser($request);

        if (! $user) {
            return redirect()->route('panel.login');
        }

        return $user;
    }

    protected function resolvePanelUser(Request $request): ?User
    {
        $userId = $request->session()->get('panel_user_id');

        if (! $userId) {
            return null;
        }

        return User::query()->find($userId);
    }

    protected function passwordMatches(User $user, string $plainPassword): bool
    {
        $storedPassword = (string) ($user->password ?? '');

        if ($storedPassword === '') {
            return false;
        }

        if (Hash::check($plainPassword, $storedPassword)) {
            return true;
        }

        $normalizedStored = strtolower($storedPassword);
        $matchesLegacyPlain = hash_equals($storedPassword, $plainPassword);
        $matchesLegacyMd5 = preg_match('/^[a-f0-9]{32}$/i', $storedPassword) === 1
            && hash_equals($normalizedStored, md5($plainPassword));
        $matchesLegacySha1 = preg_match('/^[a-f0-9]{40}$/i', $storedPassword) === 1
            && hash_equals($normalizedStored, sha1($plainPassword));

        if (! $matchesLegacyPlain && ! $matchesLegacyMd5 && ! $matchesLegacySha1) {
            return false;
        }

        $user->forceFill([
            'password' => Hash::make($plainPassword),
        ])->save();

        return true;
    }

    protected function activeClinic(Request $request): ?object
    {
        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId <= 0) {
            return null;
        }

        return DB::table('clinic')
            ->select($this->clinicSelectColumns())
            ->where('clinic_id', $clinicId)
            ->first();
    }

    protected function shouldUseAdminNavigation(Request $request, ?User $user): bool
    {
        if (! $this->canUseAdminMode($user)) {
            return false;
        }

        return $request->session()->get('panel_mode', 'admin') === 'admin';
    }

    protected function isAdmin(?User $user): bool
    {
        return strtolower((string) ($user?->role ?? '')) === 'admin';
    }

    protected function isDoctor(?User $user): bool
    {
        return strtolower((string) ($user?->role ?? '')) === 'doctor';
    }

    protected function displayName(User $user): string
    {
        $fullName = trim((string) (($user->name ?? '') ?: ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $username = trim((string) (($user->username ?? '') ?: ''));
        if ($username !== '') {
            return Str::title(str_replace(['.', '_', '-'], ' ', $username));
        }

        return 'User';
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : 'U';
    }

    protected function redirectToHome(Request $request): RedirectResponse
    {
        $user = $this->resolvePanelUser($request);

        if ($this->requiresClinicSelection($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        if ($this->isInAdminMode($request, $user)) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('panel.dashboard');
    }

    protected function buildUsechh2ReportContext(Request $request, ?User $user, bool $persistReport): array
    {
        $declarationId = (int) $request->query('declaration_id', $request->input('declaration_id', 0));
        $employeeId = (int) $request->query('employee_id', $request->input('employee_id', 0));
        $companyId = (int) $request->query('company_id', $request->input('company_id', 0));
        $surveillanceId = (int) $request->query('surveillance_id', $request->input('surveillance_id', 0));
        $groupChemical = trim((string) $request->query('group_chemical', $request->input('group_chemical', '')));
        $viewMode = (bool) $request->query('view', false);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')
                ->when($employeeId > 0, fn ($builder) => $builder->where('employee_id', $employeeId))
                ->when($companyId > 0, fn ($builder) => $builder->where('company_id', $companyId))
                ->when($surveillanceId > 0, fn ($builder) => $builder->where('surveillance_id', $surveillanceId))
                ->orderByDesc('declaration_id')
                ->first();
        }

        $declarationId = (int) ($declaration->declaration_id ?? $declarationId);
        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $companyId = (int) ($declaration->company_id ?? $companyId);
        $surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);

        $company = $companyId > 0 && Schema::hasTable('company')
            ? DB::table('company')->where('company_id', $companyId)->first()
            : null;
        $employee = $employeeId > 0 && Schema::hasTable('employee')
            ? DB::table('employee')->where('employee_id', $employeeId)->first()
            : null;
        $chemicalInfo = $surveillanceId > 0 && Schema::hasTable('chemical_information')
            ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
            : null;

        if ($groupChemical === '') {
            $groupChemical = trim((string) ($chemicalInfo->chemicals ?? ''));
        }

        $doctor = $this->resolvedSurveillanceDoctorRecord($request, $user, $declaration);
        $report = null;
        if (
            $persistReport
            && $employeeId > 0
            && $companyId > 0
            && $groupChemical !== ''
            && Schema::hasTable('summary_employee_report')
        ) {
            $report = DB::table('summary_employee_report')
                ->where('employee_id', $employeeId)
                ->where('company_id', $companyId)
                ->where('chemical_name', $groupChemical)
                ->first();

            if (! $report) {
                $reportId = DB::table('summary_employee_report')->insertGetId([
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'chemical_name' => $groupChemical,
                    'doctor_id' => $doctor->doctor_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $report = DB::table('summary_employee_report')->where('summary_employee_report_id', $reportId)->first();
            }
        }

        $candidateRows = $this->buildUsechh2CandidateRows($employeeId, $companyId, $groupChemical, $doctor);
        $reportItems = [];

        if ($report && Schema::hasTable('summary_employee_report_items')) {
            $candidateIds = array_values(array_filter(array_map(
                static fn (array $row): int => (int) ($row['surveillance_id'] ?? 0),
                $candidateRows
            )));

            $existingItems = DB::table('summary_employee_report_items')
                ->where('summary_employee_report_id', $report->summary_employee_report_id)
                ->get()
                ->keyBy('surveillance_id');

            foreach ($candidateRows as $index => $candidateRow) {
                $survId = (int) ($candidateRow['surveillance_id'] ?? 0);
                $existingItem = $existingItems->get($survId);

                $payload = [
                    'summary_employee_report_id' => $report->summary_employee_report_id,
                    'declaration_id' => $candidateRow['declaration_id'] ?? null,
                    'employee_id' => $candidateRow['employee_id'] ?? null,
                    'surveillance_id' => $candidateRow['surveillance_id'] ?? null,
                    'ms_date' => $candidateRow['ms_date_raw'] ?? null,
                    'assessment_type' => $existingItem->assessment_type ?? $candidateRow['assessment_type'],
                    'history_effect' => $existingItem->history_effect ?? $candidateRow['history_effect'],
                    'clinical_findings' => $existingItem->clinical_findings ?? $candidateRow['clinical_findings'],
                    'target_organ_function' => $existingItem->target_organ_function ?? $candidateRow['target_organ_function'],
                    'bei_determinants' => $existingItem->bei_determinants ?? $candidateRow['bei_determinants'],
                    'work_relatedness' => $existingItem->work_relatedness ?? $candidateRow['work_relatedness'],
                    'conclusion' => $existingItem->conclusion ?? $candidateRow['conclusion'],
                    'mrp_date' => $existingItem->mrp_date ?? $candidateRow['mrp_date'],
                    'doctor_name' => $candidateRow['doctor_name'],
                    'doctor_registration_no' => $candidateRow['doctor_registration_no'],
                    'sort_order' => $index + 1,
                    'updated_at' => now(),
                ];

                if ($existingItem) {
                    DB::table('summary_employee_report_items')
                        ->where('summary_employee_report_item_id', $existingItem->summary_employee_report_item_id)
                        ->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('summary_employee_report_items')->insert($payload);
                }
            }

            if ($candidateIds !== []) {
                DB::table('summary_employee_report_items')
                    ->where('summary_employee_report_id', $report->summary_employee_report_id)
                    ->whereNotIn('surveillance_id', $candidateIds)
                    ->delete();
            }

            $reportItems = DB::table('summary_employee_report_items')
                ->where('summary_employee_report_id', $report->summary_employee_report_id)
                ->orderBy('sort_order')
                ->orderBy('summary_employee_report_item_id')
                ->get()
                ->map(static function ($item): array {
                    return [
                        'summary_employee_report_item_id' => $item->summary_employee_report_item_id,
                        'declaration_id' => $item->declaration_id,
                        'employee_id' => $item->employee_id,
                        'surveillance_id' => $item->surveillance_id,
                        'ms_date' => trim((string) $item->ms_date) !== '' && strtotime((string) $item->ms_date)
                            ? date('d/m/Y', strtotime((string) $item->ms_date))
                            : (trim((string) $item->ms_date) !== '' ? (string) $item->ms_date : 'Not recorded'),
                        'assessment_type' => (string) ($item->assessment_type ?? ''),
                        'history_effect' => (string) ($item->history_effect ?? ''),
                        'clinical_findings' => (string) ($item->clinical_findings ?? ''),
                        'target_organ_function' => (string) ($item->target_organ_function ?? ''),
                        'bei_determinants' => (string) ($item->bei_determinants ?? ''),
                        'work_relatedness' => (string) ($item->work_relatedness ?? ''),
                        'conclusion' => (string) ($item->conclusion ?? ''),
                        'mrp_date' => (string) ($item->mrp_date ?? ''),
                        'doctor' => trim((string) ($item->doctor_registration_no ?? '')) !== ''
                            ? trim((string) ($item->doctor_name ?? '')) . "\n" . 'Reg. No. ' . trim((string) ($item->doctor_registration_no ?? ''))
                            : (string) ($item->doctor_name ?? ''),
                    ];
                })
                ->all();
        } else {
            $reportItems = $candidateRows;
        }

        return [
            'usechh2Report' => $report,
            'usechh2Items' => $reportItems,
            'usechh2Company' => $company,
            'usechh2Employee' => $employee,
            'usechh2Chemical' => $groupChemical,
            'usechh2Doctor' => $doctor,
            'usechh2ViewMode' => $viewMode,
            'usechh2DownloadMode' => (bool) $request->query('download', false),
            'usechh2DeclarationId' => $declarationId,
            'usechh2CompanyId' => $companyId,
            'usechh2SurveillanceId' => $surveillanceId,
            'usechh2EmployeeId' => $employeeId,
            'usechh2LatestDateRaw' => $candidateRows[0]['ms_date_raw'] ?? '',
        ];
    }

    protected function buildUsechh3ReportContext(Request $request, ?User $user): array
    {
        $declarationId = (int) $request->query('declaration_id', $request->input('declaration_id', 0));
        $employeeId = (int) $request->query('employee_id', $request->input('employee_id', 0));
        $companyId = (int) $request->query('company_id', $request->input('company_id', 0));
        $surveillanceId = (int) $request->query('surveillance_id', $request->input('surveillance_id', 0));
        $viewMode = (bool) $request->query('view', false);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')
                ->when($employeeId > 0, fn ($builder) => $builder->where('employee_id', $employeeId))
                ->when($companyId > 0, fn ($builder) => $builder->where('company_id', $companyId))
                ->when($surveillanceId > 0, fn ($builder) => $builder->where('surveillance_id', $surveillanceId))
                ->orderByDesc('declaration_id')
                ->first();
        }

        $declarationId = (int) ($declaration->declaration_id ?? $declarationId);
        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $companyId = (int) ($declaration->company_id ?? $companyId);
        $surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);

        $employee = $employeeId > 0 && Schema::hasTable('employee')
            ? DB::table('employee')->where('employee_id', $employeeId)->first()
            : null;
        $company = $companyId > 0 && Schema::hasTable('company')
            ? DB::table('company')->where('company_id', $companyId)->first()
            : null;
        $chemical = $surveillanceId > 0 && Schema::hasTable('chemical_information')
            ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
            : null;
        $fitnessReport = $surveillanceId > 0 && Schema::hasTable('fitness_report')
            ? DB::table('fitness_report')->where('surveillance_id', $surveillanceId)->first()
            : null;
        $doctor = $this->resolvedSurveillanceDoctorRecord($request, $user, $declaration);

        $employeeName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
        $companyAddress = trim((string) (($company->company_address ?? '') . ', ' . ($company->company_postcode ?? '') . ' ' . ($company->company_district ?? '') . ', ' . ($company->company_state ?? '')), " ,");
        $doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
        $doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? 'Doctor'));
        $doctorRegNo = trim((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? ''));
        $doctorSignature = trim((string) ($doctor->doctor_sign ?? $declaration->doctor_signature ?? ''));
        $activeClinic = $this->activeClinic($request);
        $formatMultilineAddress = static function (?string $line1, ?string $postcode, ?string $district, ?string $state): string {
            $line1 = trim((string) $line1);
            $postcode = trim((string) $postcode);
            $district = trim((string) $district);
            $state = trim((string) $state);

            $line2 = trim($postcode . ($postcode !== '' && $district !== '' ? ' ' : '') . $district);

            return implode("\n", array_values(array_filter([
                $line1 !== '' ? $line1 : null,
                $line2 !== '' ? $line2 : null,
                $state !== '' ? $state : null,
            ])));
        };
        $defaultPracticeAddress = $formatMultilineAddress(
            $activeClinic->clinic_address ?? '',
            $activeClinic->clinic_postcode ?? '',
            $activeClinic->clinic_district ?? '',
            $activeClinic->clinic_state ?? ''
        );
        if ($defaultPracticeAddress === '') {
            $defaultPracticeAddress = $formatMultilineAddress(
                $doctor->doctor_address ?? '',
                $doctor->doctor_postcode ?? '',
                $doctor->doctor_district ?? '',
                $doctor->doctor_state ?? ''
            );
        }
        $defaultDoctorTelephone = trim((string) ($activeClinic->clinic_telephone ?? ''));
        $defaultDoctorTelephone = $defaultDoctorTelephone !== '' ? $defaultDoctorTelephone : trim((string) ($doctor->doctor_telephone ?? ''));
        $defaultDoctorEmail = trim((string) ($activeClinic->clinic_email ?? ''));
        $defaultDoctorEmail = $defaultDoctorEmail !== '' ? $defaultDoctorEmail : trim((string) ($doctor->doctor_email ?? ''));
        $practiceAddress = trim((string) ($fitnessReport->doctor_practice_address ?? ''));
        $practiceAddress = $practiceAddress !== '' ? $practiceAddress : $defaultPracticeAddress;
        $doctorTelephone = trim((string) ($fitnessReport->doctor_telephone ?? ''));
        $doctorTelephone = $doctorTelephone !== '' ? $doctorTelephone : $defaultDoctorTelephone;
        $doctorEmail = trim((string) ($fitnessReport->doctor_email_address ?? ''));
        $doctorEmail = $doctorEmail !== '' ? $doctorEmail : $defaultDoctorEmail;

        return [
            'usechh3Declaration' => $declaration,
            'usechh3Employee' => $employee,
            'usechh3Company' => $company,
            'usechh3Chemical' => $chemical,
            'usechh3FitnessReport' => $fitnessReport,
            'usechh3Doctor' => $doctor,
            'usechh3EmployeeName' => $employeeName !== '' ? $employeeName : 'Not recorded',
            'usechh3CompanyAddress' => $companyAddress !== '' ? $companyAddress : '-',
            'usechh3DoctorName' => $doctorName,
            'usechh3DoctorRegNo' => $doctorRegNo,
            'usechh3DoctorSignature' => $doctorSignature,
            'usechh3DoctorPracticeAddress' => $practiceAddress,
            'usechh3DoctorTelephone' => $doctorTelephone,
            'usechh3DoctorEmail' => $doctorEmail,
            'usechh3ViewMode' => $viewMode,
            'usechh3DownloadMode' => (bool) $request->query('download', false),
            'usechh3DeclarationId' => $declarationId,
            'usechh3EmployeeId' => $employeeId,
            'usechh3CompanyId' => $companyId,
            'usechh3SurveillanceId' => $surveillanceId,
        ];
    }

    protected function buildUsechh2CandidateRows(int $employeeId, int $companyId, string $groupChemical, ?object $doctor): array
    {
        if (
            $employeeId <= 0
            || $companyId <= 0
            || $groupChemical === ''
            || ! Schema::hasTable('declaration')
            || ! Schema::hasTable('chemical_information')
        ) {
            return [];
        }

        $declarations = DB::table('declaration')
            ->join('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
            ->leftJoin('recommendation', 'recommendation.surveillance_id', '=', 'declaration.surveillance_id')
            ->where('declaration.employee_id', $employeeId)
            ->where('declaration.company_id', $companyId)
            ->where('chemical_information.chemicals', $groupChemical)
            ->when(
                Schema::hasColumn('recommendation', 'is_final'),
                static fn ($builder) => $builder->where('recommendation.is_final', 1)
            )
            ->orderByRaw('COALESCE(chemical_information.examination_date, declaration.doctor_date, declaration.employee_date) desc')
            ->orderByDesc('declaration.declaration_id')
            ->get([
                'declaration.declaration_id',
                'declaration.employee_id',
                'declaration.company_id',
                'declaration.surveillance_id',
                'declaration.doctor_id',
                'declaration.employee_date',
                'declaration.doctor_date',
                'chemical_information.examination_type',
                'chemical_information.examination_date',
            ]);

        $doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
        $doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? ''));
        $doctorRegNo = trim((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? ''));

        $rows = [];
        foreach ($declarations as $index => $declaration) {
            $rowSurveillanceId = (int) $declaration->surveillance_id;
            $findings = Schema::hasTable('ms_findings')
                ? DB::table('ms_findings')->where('surveillance_id', $rowSurveillanceId)->first()
                : null;
            $targetOrgan = Schema::hasTable('target_organ')
                ? DB::table('target_organ')->where('surveillance_id', $rowSurveillanceId)->first()
                : null;
            $biological = Schema::hasTable('biological_monitoring')
                ? DB::table('biological_monitoring')->where('surveillance_id', $rowSurveillanceId)->first()
                : null;
            $recommendation = Schema::hasTable('recommendation')
                ? DB::table('recommendation')->where('surveillance_id', $rowSurveillanceId)->first()
                : null;

            $targetParts = [];
            foreach ([
                'blood_count_result' => 'Full Blood Count',
                'renal_function_result' => 'Renal Function Test',
                'liver_function_result' => 'Liver Function Test',
                'chest_xray_result' => 'Chest X-Ray',
            ] as $column => $label) {
                $value = trim((string) ($targetOrgan->{$column} ?? ''));
                if ($value !== '') {
                    $targetParts[] = $label . ': ' . $value;
                }
            }
            if (trim((string) ($targetOrgan->spirometry_FEV_FVC ?? '')) !== '') {
                $targetParts[] = 'Spirometry FEV/FVC: ' . trim((string) $targetOrgan->spirometry_FEV_FVC);
            }
            if (Schema::hasTable('target_organ_other_tests')) {
                $otherTargetTests = DB::table('target_organ_other_tests')
                    ->where('surveillance_id', $rowSurveillanceId)
                    ->orderBy('sort_order')
                    ->orderBy('other_target_test_id')
                    ->get(['test_name', 'result']);

                foreach ($otherTargetTests as $otherTargetTest) {
                    $testName = trim((string) ($otherTargetTest->test_name ?? ''));
                    $testResult = trim((string) ($otherTargetTest->result ?? ''));
                    if ($testName !== '') {
                        $targetParts[] = $testResult !== '' ? $testName . ': ' . $testResult : $testName;
                    }
                }
            }

            $workRelatedValues = array_values(array_filter([
                trim((string) ($findings->CF_work_related ?? '')),
                trim((string) ($findings->TO_work_related ?? '')),
                trim((string) ($findings->BM_work_related ?? '')),
            ], static fn ($value) => $value !== ''));
            $workRelatedness = 'Not recorded';
            if ($workRelatedValues !== []) {
                $workRelatedness = count(array_unique(array_map('strtolower', $workRelatedValues))) === 1
                    ? trim((string) $workRelatedValues[0])
                    : implode(' / ', $workRelatedValues);
            }

            $msDateRaw = trim((string) ($declaration->examination_date ?? $declaration->doctor_date ?? $declaration->employee_date ?? ''));
            $rows[] = [
                'declaration_id' => (int) $declaration->declaration_id,
                'employee_id' => (int) $declaration->employee_id,
                'surveillance_id' => $rowSurveillanceId,
                'ms_date_raw' => $msDateRaw,
                'ms_date' => $msDateRaw !== '' && strtotime($msDateRaw) ? date('d/m/Y', strtotime($msDateRaw)) : ($msDateRaw !== '' ? $msDateRaw : 'Not recorded'),
                'assessment_type' => trim((string) ($declaration->examination_type ?? '')) ?: 'Not recorded',
                'history_effect' => trim((string) ($findings->history_of_health ?? '')) ?: 'Not recorded',
                'clinical_findings' => trim((string) ($findings->clinical_findings ?? '')) ?: 'Not recorded',
                'target_organ_function' => $targetParts !== [] ? implode("\n", $targetParts) : 'Not recorded',
                'bei_determinants' => trim((string) ($biological->baseline_annual ?? $biological->baseline_results ?? $biological->biological_exposure ?? '')) ?: 'Not recorded',
                'work_relatedness' => $workRelatedness,
                'conclusion' => trim((string) ($findings->conclusion_fitness ?? '')) ?: 'Not recorded',
                'mrp_date' => trim((string) ($recommendation->MRPdate_start ?? $recommendation->nextReview_date ?? '')) ?: 'Not recorded',
                'doctor_name' => $doctorName !== '' ? $doctorName : 'Occupational Health Doctor',
                'doctor_registration_no' => $doctorRegNo,
                'doctor' => $doctorRegNo !== ''
                    ? ($doctorName !== '' ? $doctorName : 'Occupational Health Doctor') . "\n" . 'Reg. No. ' . $doctorRegNo
                    : ($doctorName !== '' ? $doctorName : 'Occupational Health Doctor'),
            ];
        }

        return $rows;
    }

    protected function buildUsechh5iiReportContext(Request $request, ?User $user, bool $persistReport): array
    {
        $schema = Schema::getFacadeRoot();
        $declarationId = (int) $request->query('declaration_id', $request->input('declaration_id', 0));
        $employeeId = (int) $request->query('employee_id', $request->input('employee_id', 0));
        $companyId = (int) $request->query('company_id', $request->input('company_id', 0));
        $surveillanceId = (int) $request->query('surveillance_id', $request->input('surveillance_id', 0));
        $groupChemical = trim((string) $request->query('group_chemical', $request->input('group_chemical', '')));
        $groupDate = trim((string) $request->query('group_date', $request->input('group_date', '')));
        $viewMode = (bool) $request->query('view', false);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')
                ->when($employeeId > 0, fn ($builder) => $builder->where('employee_id', $employeeId))
                ->when($companyId > 0, fn ($builder) => $builder->where('company_id', $companyId))
                ->when($surveillanceId > 0, fn ($builder) => $builder->where('surveillance_id', $surveillanceId))
                ->orderByDesc('declaration_id')
                ->first();
        }

        $declarationId = (int) ($declaration->declaration_id ?? $declarationId);
        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $companyId = (int) ($declaration->company_id ?? $companyId);
        $surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);

        $company = $companyId > 0 && Schema::hasTable('company')
            ? DB::table('company')->where('company_id', $companyId)->first()
            : null;
        $chemicalInfo = $surveillanceId > 0 && Schema::hasTable('chemical_information')
            ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
            : null;

        if ($groupChemical === '') {
            $groupChemical = trim((string) ($chemicalInfo->chemicals ?? ''));
        }
        if ($groupDate === '') {
            $groupDate = trim((string) ($chemicalInfo->examination_date ?? $declaration->employee_date ?? $declaration->doctor_date ?? ''));
        }

        $doctor = $this->resolvedSurveillanceDoctorRecord($request, $user, $declaration);
        $report = null;
        if (
            $persistReport
            && $companyId > 0
            && $groupDate !== ''
            && $groupChemical !== ''
            && Schema::hasTable('abnormal_report')
        ) {
            $report = DB::table('abnormal_report')
                ->where('company_id', $companyId)
                ->whereDate('examination_date', $groupDate)
                ->where('chemical_name', $groupChemical)
                ->first();

            if (! $report) {
                $reportId = DB::table('abnormal_report')->insertGetId([
                    'company_id' => $companyId,
                    'examination_date' => $groupDate,
                    'chemical_name' => $groupChemical,
                    'doctor_id' => $doctor->doctor_id ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $report = DB::table('abnormal_report')->where('abnormal_report_id', $reportId)->first();
            }
        }

        $candidateRows = $this->buildUsechh5iiCandidateRows($companyId, $groupDate, $groupChemical);
        $reportItems = [];

        if ($report && Schema::hasTable('abnormal_report_items')) {
            $candidateIds = array_values(array_filter(array_map(
                static fn (array $row): int => (int) ($row['surveillance_id'] ?? 0),
                $candidateRows
            )));

            $existingItems = DB::table('abnormal_report_items')
                ->where('abnormal_report_id', $report->abnormal_report_id)
                ->get()
                ->keyBy('surveillance_id');

            foreach ($candidateRows as $index => $candidateRow) {
                $survId = (int) ($candidateRow['surveillance_id'] ?? 0);
                $existingItem = $existingItems->get($survId);

                $payload = [
                    'abnormal_report_id' => $report->abnormal_report_id,
                    'declaration_id' => $candidateRow['declaration_id'] ?? null,
                    'employee_id' => $candidateRow['employee_id'] ?? null,
                    'surveillance_id' => $candidateRow['surveillance_id'] ?? null,
                    'patient_name' => $candidateRow['patient_name'],
                    'identity_no' => $candidateRow['identity_no'],
                    'sex' => $candidateRow['sex'],
                    'designation' => $existingItem->designation ?? $candidateRow['designation'],
                    'assessment_type' => $existingItem->assessment_type ?? $candidateRow['assessment_type'],
                    'history_effect' => $existingItem->history_effect ?? $candidateRow['history_effect'],
                    'clinical_findings' => $existingItem->clinical_findings ?? $candidateRow['clinical_findings'],
                    'target_organ_function' => $existingItem->target_organ_function ?? $candidateRow['target_organ_function'],
                    'bm_determinant' => $existingItem->bm_determinant ?? $candidateRow['bm_determinant'],
                    'work_relatedness' => $existingItem->work_relatedness ?? $candidateRow['work_relatedness'],
                    'recommendation_action' => $existingItem->recommendation_action ?? $candidateRow['recommendation_action'],
                    'conclusion' => $existingItem->conclusion ?? $candidateRow['conclusion'],
                    'sort_order' => $index + 1,
                    'updated_at' => now(),
                ];

                if ($existingItem) {
                    DB::table('abnormal_report_items')
                        ->where('abnormal_report_item_id', $existingItem->abnormal_report_item_id)
                        ->update($payload);
                } else {
                    $payload['created_at'] = now();
                    DB::table('abnormal_report_items')->insert($payload);
                }
            }

            if ($candidateIds !== []) {
                DB::table('abnormal_report_items')
                    ->where('abnormal_report_id', $report->abnormal_report_id)
                    ->whereNotIn('surveillance_id', $candidateIds)
                    ->delete();
            }

            $reportItems = DB::table('abnormal_report_items')
                ->where('abnormal_report_id', $report->abnormal_report_id)
                ->orderBy('sort_order')
                ->orderBy('abnormal_report_item_id')
                ->get()
                ->map(static fn ($item) => (array) $item)
                ->all();
        } else {
            $reportItems = $candidateRows;
        }

        return [
            'usechh5iiReport' => $report,
            'usechh5iiItems' => $reportItems,
            'usechh5iiCompany' => $company,
            'usechh5iiChemical' => $groupChemical,
            'usechh5iiDate' => $groupDate,
            'usechh5iiDoctor' => $doctor,
            'usechh5iiViewMode' => $viewMode,
            'usechh5iiDownloadMode' => (bool) $request->query('download', false),
            'usechh5iiDeclarationId' => $declarationId,
            'usechh5iiCompanyId' => $companyId,
            'usechh5iiSurveillanceId' => $surveillanceId,
            'usechh5iiEmployeeId' => $employeeId,
        ];
    }

    protected function buildUsechh5iiCandidateRows(int $companyId, string $groupDate, string $groupChemical): array
    {
        if (
            $companyId <= 0
            || $groupDate === ''
            || $groupChemical === ''
            || ! Schema::hasTable('declaration')
            || ! Schema::hasTable('removal_report')
            || ! Schema::hasTable('chemical_information')
        ) {
            return [];
        }

        $declarations = DB::table('declaration')
            ->join('removal_report', 'removal_report.surveillance_id', '=', 'declaration.surveillance_id')
            ->join('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
            ->leftJoin('employee', 'employee.employee_id', '=', 'declaration.employee_id')
            ->where('declaration.company_id', $companyId)
            ->where('chemical_information.chemicals', $groupChemical)
            ->where(function ($builder) use ($groupDate): void {
                $builder->whereDate('declaration.employee_date', $groupDate)
                    ->orWhere(function ($fallback) use ($groupDate): void {
                        $fallback->whereNull('declaration.employee_date')
                            ->whereDate('declaration.doctor_date', $groupDate);
                    });
            })
            ->orderBy('employee.employee_firstName')
            ->orderBy('employee.employee_lastName')
            ->get([
                'declaration.declaration_id',
                'declaration.employee_id',
                'declaration.company_id',
                'declaration.surveillance_id',
                'employee.employee_firstName',
                'employee.employee_lastName',
                'employee.employee_NRIC',
                'employee.employee_passportNo',
                'employee.employee_gender',
                'chemical_information.examination_type',
            ]);

        $rows = [];
        foreach ($declarations as $index => $declaration) {
            $surveillanceId = (int) $declaration->surveillance_id;
            $findings = Schema::hasTable('ms_findings')
                ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first()
                : null;
            $targetOrgan = Schema::hasTable('target_organ')
                ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first()
                : null;
            $biological = Schema::hasTable('biological_monitoring')
                ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first()
                : null;
            $removal = DB::table('removal_report')->where('surveillance_id', $surveillanceId)->first();

            $otherTargetTests = [];
            if (Schema::hasTable('target_organ_other_tests')) {
                $otherTargetTests = DB::table('target_organ_other_tests')
                    ->where('surveillance_id', $surveillanceId)
                    ->orderBy('sort_order')
                    ->orderBy('other_target_test_id')
                    ->get(['test_name', 'result'])
                    ->map(static fn ($row) => [
                        'name' => trim((string) ($row->test_name ?? '')),
                        'result' => trim((string) ($row->result ?? '')),
                    ])
                    ->all();
            }

            $targetParts = [];
            foreach ([
                'blood_count_result' => 'Full Blood Count',
                'renal_function_result' => 'Renal Function Test',
                'liver_function_result' => 'Liver Function Test',
                'chest_xray_result' => 'Chest X-Ray',
            ] as $column => $label) {
                if (trim((string) ($targetOrgan->{$column} ?? '')) === 'Abnormal') {
                    $targetParts[] = $label;
                }
            }
            if (trim((string) ($targetOrgan->spirometry_FEV_FVC ?? '')) !== '' || trim((string) ($targetOrgan->spirometry_comments ?? '')) !== '') {
                $targetParts[] = 'Spirometry';
            }
            foreach ($otherTargetTests as $otherTargetTest) {
                if (($otherTargetTest['name'] ?? '') !== '' && ($otherTargetTest['result'] ?? '') === 'Abnormal') {
                    $targetParts[] = $otherTargetTest['name'];
                }
            }

            $removalType = trim((string) ($removal->removal_type ?? ''));
            $reviewDate = trim((string) ($removal->review_date ?? ''));
            $removalDuration = trim((string) ($removal->removal_duration ?? ''));
            $recommendationParts = [];
            if ($removalType !== '') {
                $recommendationParts[] = ucfirst($removalType);
            }
            if ($removalDuration !== '') {
                $recommendationParts[] = 'MRP for ' . $removalDuration;
            }
            if ($reviewDate !== '' && strtotime($reviewDate)) {
                $recommendationParts[] = 'Review ' . date('d/m/Y', strtotime($reviewDate));
            }

            $workRelatedLabels = [];
            $addWorkRelatedLabel = static function (array &$labels, ?string $value, string $label): void {
                if (strtolower(trim((string) $value)) === 'yes') {
                    $labels[] = $label;
                }
            };

            $addWorkRelatedLabel($workRelatedLabels, $findings->history_of_health ?? null, 'History of Health Effects Due to Chemical Exposure');
            $addWorkRelatedLabel($workRelatedLabels, $findings->clinical_findings ?? null, 'Clinical Findings');
            $addWorkRelatedLabel($workRelatedLabels, $findings->CF_work_related ?? null, 'Clinical Findings');
            $addWorkRelatedLabel($workRelatedLabels, $findings->target_organ ?? null, 'Target Organ Function Test Results');
            $addWorkRelatedLabel($workRelatedLabels, $findings->TO_work_related ?? null, 'Target Organ Function Test Results');
            $addWorkRelatedLabel($workRelatedLabels, $findings->biological_monitoring ?? null, 'BEI Determinant (BM/BEM)');
            $addWorkRelatedLabel($workRelatedLabels, $findings->BM_work_related ?? null, 'BEI Determinant (BM/BEM)');
            $addWorkRelatedLabel($workRelatedLabels, $findings->pregnancy_breastFeding ?? null, 'Pregnancy/Breastfeeding');
            $workRelatedLabels = array_values(array_unique($workRelatedLabels));
            $workRelatedness = $workRelatedLabels === []
                ? 'No'
                : 'Yes, ' . implode(', ', $workRelatedLabels);

            $rows[] = [
                'declaration_id' => (int) $declaration->declaration_id,
                'employee_id' => (int) $declaration->employee_id,
                'surveillance_id' => $surveillanceId,
                'patient_name' => trim((string) (($declaration->employee_firstName ?? '') . ' ' . ($declaration->employee_lastName ?? ''))) ?: '-',
                'identity_no' => trim((string) (($declaration->employee_NRIC ?? '') !== '' ? $declaration->employee_NRIC : ($declaration->employee_passportNo ?? ''))) ?: '-',
                'sex' => trim((string) ($declaration->employee_gender ?? '')) ?: '-',
                'designation' => trim((string) ($removal->designated_role ?? '')) ?: '-',
                'assessment_type' => trim((string) ($declaration->examination_type ?? 'Medical Surveillance')) ?: 'Medical Surveillance',
                'history_effect' => trim((string) ($findings->history_of_health ?? '')) ?: 'No',
                'clinical_findings' => trim((string) ($findings->clinical_findings ?? '')) ?: 'No',
                'target_organ_function' => $targetParts !== [] ? implode(', ', array_values(array_unique($targetParts))) : 'Not recorded',
                'bm_determinant' => trim((string) ($biological->baseline_annual ?? $biological->baseline_results ?? '')) ?: 'Not recorded',
                'work_relatedness' => $workRelatedness,
                'recommendation_action' => $recommendationParts !== [] ? implode(', ', $recommendationParts) : 'Monitoring',
                'conclusion' => strtolower($removalType) === 'permanent' ? 'Permanent Unfit' : (trim((string) ($findings->conclusion_fitness ?? '')) ?: 'Temporary Unfit'),
                'sort_order' => $index + 1,
            ];
        }

        return $rows;
    }

    protected function firstClinicId(): ?int
    {
        $clinicId = DB::table('clinic')
            ->orderBy('clinic_name')
            ->value('clinic_id');

        return $clinicId ? (int) $clinicId : null;
    }

    protected function clinicSelectColumns(): array
    {
        return $this->existingColumns('clinic', [
            'clinic_id',
            'clinic_name',
            'clinic_email',
            'clinic_telephone',
            'clinic_address',
            'clinic_postcode',
            'clinic_district',
            'clinic_state',
            'clinic_header_path',
        ]);
    }

    protected function clinicListColumns(): array
    {
        return $this->existingColumns('clinic', [
            'clinic_id',
            'clinic_name',
            'clinic_address',
            'clinic_postcode',
            'clinic_district',
            'clinic_state',
            'clinic_telephone',
            'clinic_fax',
            'clinic_email',
            'clinic_username',
            'clinic_registration',
            'clinic_header_path',
            'clinic_status',
        ]);
    }

    protected function doctorListColumns(): array
    {
        return $this->existingColumns('doctor', [
            'doctor_id',
            'doctor_firstName',
            'doctor_lastName',
            'doctor_telephone',
            'doctor_fax',
            'doctor_email',
            'OHD_registrationNo',
            'doctor_status',
        ]);
    }

    protected function clinicFormDefaults(?object $clinic = null): array
    {
        [$phoneCode, $phoneNumber] = $this->splitCountryCodeNumber((string) ($clinic->clinic_telephone ?? ''), '60');
        [$faxCode, $faxNumber] = $this->splitCountryCodeNumber((string) ($clinic->clinic_fax ?? ''), '60');

        return [
            'clinic_name' => (string) ($clinic->clinic_name ?? ''),
            'registration' => (string) ($clinic->clinic_registration ?? ''),
            'clinic_email' => (string) ($clinic->clinic_email ?? ''),
            'clinic_phone_code' => $phoneCode,
            'clinic_phone_number' => $phoneNumber,
            'clinic_fax_code' => $faxCode,
            'clinic_fax_number' => $faxNumber,
            'clinic_address' => (string) ($clinic->clinic_address ?? ''),
            'clinic_postcode' => (string) ($clinic->clinic_postcode ?? ''),
            'clinic_district' => (string) ($clinic->clinic_district ?? ''),
            'clinic_state' => (string) ($clinic->clinic_state ?? ''),
            'clinic_status' => (string) ($clinic->clinic_status ?? 'active'),
            'clinic_header_path' => (string) ($clinic->clinic_header_path ?? ''),
        ];
    }

    protected function doctorFormDefaults(?object $doctor = null): array
    {
        [$phoneCode, $phoneNumber] = $this->splitCountryCodeNumber((string) ($doctor->doctor_telephone ?? ''), '60');
        [$faxCode, $faxNumber] = $this->splitCountryCodeNumber((string) ($doctor->doctor_fax ?? ''), '60');

        return [
            'doctor_firstName' => (string) ($doctor->doctor_firstName ?? ''),
            'doctor_lastName' => (string) ($doctor->doctor_lastName ?? ''),
            'doctor_email' => (string) ($doctor->doctor_email ?? ''),
            'doctor_NRIC' => (string) ($doctor->doctor_NRIC ?? ''),
            'doctor_passportNo' => (string) ($doctor->doctor_passportNo ?? ''),
            'doctor_DOB' => (string) ($doctor->doctor_DOB ?? ''),
            'doctor_gender' => (string) ($doctor->doctor_gender ?? ''),
            'doctor_ethnicity' => (string) ($doctor->doctor_ethnicity ?? ''),
            'doctor_citizenship' => (string) ($doctor->doctor_citizenship ?? ''),
            'doctor_martialStatus' => (string) ($doctor->doctor_martialStatus ?? ''),
            'MMC_no' => (string) ($doctor->MMC_no ?? ''),
            'OHD_registrationNo' => (string) ($doctor->OHD_registrationNo ?? ''),
            'doctor_phone_code' => $phoneCode,
            'doctor_phone_number' => $phoneNumber,
            'doctor_fax_code' => $faxCode,
            'doctor_fax_number' => $faxNumber,
            'doctor_address' => (string) ($doctor->doctor_address ?? ''),
            'doctor_postcode' => (string) ($doctor->doctor_postcode ?? ''),
            'doctor_district' => (string) ($doctor->doctor_district ?? ''),
            'doctor_state' => (string) ($doctor->doctor_state ?? ''),
            'doctor_status' => (string) ($doctor->doctor_status ?? 'active'),
            'doctor_sign' => (string) ($doctor->doctor_sign ?? ''),
            'doctor_picture' => (string) ($doctor->doctor_picture ?? ''),
        ];
    }

    protected function requiresClinicSelection(Request $request, ?User $user): bool
    {
        return $this->isDoctor($user) && (int) $request->session()->get('active_clinic_id', 0) <= 0;
    }

    protected function canAccessAdminDashboard(Request $request, ?User $user): bool
    {
        return $this->canUseAdminMode($user) || $this->requiresClinicSelection($request, $user);
    }

    protected function canManageClinics(?User $user): bool
    {
        return $this->isAdmin($user) || $this->isDoctor($user);
    }

    protected function canUseAdminMode(?User $user): bool
    {
        return $this->isAdmin($user) || $this->isDoctor($user);
    }

    protected function isInAdminMode(Request $request, ?User $user): bool
    {
        return $this->canUseAdminMode($user)
            && $request->session()->get('panel_mode', 'admin') === 'admin';
    }

    protected function buildCountryCodeNumber(?string $countryCode, ?string $number): ?string
    {
        $number = trim((string) $number);
        if ($number === '') {
            return null;
        }

        $normalizedCode = preg_replace('/\D/', '', (string) $countryCode) ?? '';
        $normalizedCode = $normalizedCode !== '' ? $normalizedCode : '60';
        $normalizedNumber = preg_replace('/\D/', '', $number) ?? '';
        if ($normalizedNumber === '') {
            return null;
        }

        if (str_starts_with($normalizedNumber, '0')) {
            $normalizedNumber = substr($normalizedNumber, 1);
        }

        return '+' . $normalizedCode . $normalizedNumber;
    }

    protected function nullableTrim(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed !== '' ? $trimmed : null;
    }

    protected function medicalHistoryResultValue(array $validated, string $field): ?string
    {
        $status = trim((string) ($validated[$field . '_status'] ?? ''));
        if (in_array($status, ['Yes', 'No'], true)) {
            return $status;
        }

        return $this->nullableTrim($validated[$field] ?? null) !== null ? 'Yes' : null;
    }

    protected function medicalHistoryStoredStatus(?object $medicalHistory, string $field): string
    {
        $storedStatus = trim((string) ($medicalHistory->{$field . '_result'} ?? ''));
        if (in_array($storedStatus, ['Yes', 'No'], true)) {
            return $storedStatus;
        }

        return trim((string) ($medicalHistory->{$field} ?? '')) !== '' ? 'Yes' : '';
    }

    protected function applyMedicalHistoryPresenceFilter($query): void
    {
        $query->where(function ($query): void {
            foreach ([
                'diagnosed_history',
                'medication_history',
                'admitted_history',
                'family_history',
                'others_history',
            ] as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';

                $query->{$method}(function ($query) use ($field): void {
                    $query->whereNotNull($field)->where($field, '!=', '');

                    $resultColumn = $field . '_result';
                    if (Schema::hasColumn('medical_history', $resultColumn)) {
                        $query->orWhereIn($resultColumn, ['Yes', 'No']);
                    }
                });
            }
        });
    }

    protected function splitCountryCodeNumber(?string $value, string $defaultCode = '60'): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [$defaultCode, ''];
        }

        $normalized = preg_replace('/\D/', '', $value) ?? '';
        if ($normalized === '') {
            return [$defaultCode, ''];
        }

        foreach (['60', '65', '62', '66'] as $code) {
            if (str_starts_with($normalized, $code)) {
                return [$code, substr($normalized, strlen($code))];
            }
        }

        return [$defaultCode, $normalized];
    }

    protected function linkedDoctorRecord(User $user): ?object
    {
        if (! Schema::hasTable('doctor')) {
            return null;
        }

        $hasDoctorEmail = Schema::hasColumn('doctor', 'doctor_email');
        $hasDoctorUsername = Schema::hasColumn('doctor', 'doctor_username');

        if (! $hasDoctorEmail && ! $hasDoctorUsername) {
            return null;
        }

        $query = DB::table('doctor');

        if ($hasDoctorEmail) {
            $query->where('doctor_email', (string) $user->email);
        }

        if ($hasDoctorUsername) {
            if ($hasDoctorEmail) {
                $query->orWhere('doctor_username', (string) $user->username);
            } else {
                $query->where('doctor_username', (string) $user->username);
            }
        }

        if (Schema::hasColumn('doctor', 'doctor_status')) {
            $query->where('doctor_status', 'active');
        }

        return $query->first();
    }

    protected function resolvedSurveillanceDoctorRecord(Request $request, ?User $user, ?object $declaration = null): ?object
    {
        if (! Schema::hasTable('doctor')) {
            return null;
        }

        if (! empty($declaration?->doctor_id)) {
            $doctor = $this->findDoctor((int) $declaration->doctor_id);
            if ($doctor && (! Schema::hasColumn('doctor', 'doctor_status') || (string) ($doctor->doctor_status ?? '') === 'active')) {
                return $doctor;
            }
        }

        if ($user) {
            $doctor = $this->linkedDoctorRecord($user);
            if ($doctor) {
                return $doctor;
            }
        }

        $activeClinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($activeClinicId > 0 && Schema::hasTable('clinic') && Schema::hasColumn('clinic', 'doctor_id')) {
            $doctorId = (int) DB::table('clinic')
                ->where('clinic_id', $activeClinicId)
                ->value('doctor_id');

            if ($doctorId > 0) {
                $doctor = $this->findDoctor($doctorId);
                if ($doctor) {
                    return $doctor;
                }
            }
        }

        if (Schema::hasColumn('doctor', 'doctor_sign')) {
            $activeSignedDoctor = DB::table('doctor')
                ->when(
                    Schema::hasColumn('doctor', 'doctor_status'),
                    static fn ($query) => $query->where('doctor_status', 'active')
                )
                ->whereNotNull('doctor_sign')
                ->where('doctor_sign', '!=', '')
                ->orderByDesc('doctor_id')
                ->first();

            if ($activeSignedDoctor) {
                return $activeSignedDoctor;
            }
        }

        return null;
    }

    protected function findClinic(int $clinicId): ?object
    {
        $columns = $this->clinicListColumns();
        if ($columns === []) {
            return null;
        }

        return DB::table('clinic')
            ->select($columns)
            ->where('clinic_id', $clinicId)
            ->first();
    }

    protected function findDoctor(int $doctorId): ?object
    {
        $columns = $this->existingColumns('doctor', [
            'doctor_id',
            'doctor_firstName',
            'doctor_lastName',
            'doctor_NRIC',
            'doctor_passportNo',
            'doctor_DOB',
            'doctor_gender',
            'doctor_address',
            'doctor_postcode',
            'doctor_district',
            'doctor_state',
            'doctor_telephone',
            'doctor_fax',
            'doctor_email',
            'doctor_ethnicity',
            'doctor_citizenship',
            'doctor_martialStatus',
            'MMC_no',
            'OHD_registrationNo',
            'doctor_username',
            'doctor_sign',
            'doctor_picture',
            'doctor_status',
        ]);

        if ($columns === []) {
            return null;
        }

        return DB::table('doctor')
            ->select($columns)
            ->where('doctor_id', $doctorId)
            ->first();
    }

    protected function existingColumns(string $table, array $columns): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return array_values(array_filter(
            $columns,
            static fn (string $column): bool => Schema::hasColumn($table, $column)
        ));
    }

    protected function findCompany(Request $request, int $companyId): ?object
    {
        if (! Schema::hasTable('company')) {
            return null;
        }

        $columns = [
            'company_id',
            'company_name',
            'mykpp_registration_no',
            'company_address',
            'company_postcode',
            'company_district',
            'company_state',
            'company_telephone',
            'company_email',
            'company_fax',
            'total_workers',
        ];

        if (Schema::hasColumn('company', 'clinic_id')) {
            $columns[] = 'clinic_id';
        }

        if (Schema::hasColumn('company', 'company_module')) {
            $columns[] = 'company_module';
        }

        $query = DB::table('company')
            ->select($columns)
            ->where('company_id', $companyId);

        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId > 0 && Schema::hasColumn('company', 'clinic_id')) {
            $query->where(function ($scoped) use ($clinicId): void {
                $scoped->where('clinic_id', $clinicId)
                    ->orWhereNull('clinic_id');
            });
        }

        return $query->first();
    }

    protected function findSurveillancePatient(Request $request, int $employeeId, ?object $selectedCompany = null): ?object
    {
        if ($employeeId <= 0 || ! Schema::hasTable('employee')) {
            return null;
        }

        $query = DB::table('employee')->where('employee_id', $employeeId);

        $clinicId = (int) $request->session()->get('active_clinic_id', 0);
        if ($clinicId > 0 && Schema::hasColumn('employee', 'clinic_id')) {
            $query->where(function ($scoped) use ($clinicId): void {
                $scoped->where('clinic_id', $clinicId)
                    ->orWhereNull('clinic_id');
            });
        }

        $patient = $query->first();
        if (! $patient) {
            return null;
        }

        if ($selectedCompany === null) {
            return $patient;
        }

        if (Schema::hasColumn('employee', 'company_id') && (int) ($patient->company_id ?? 0) === (int) $selectedCompany->company_id) {
            return $patient;
        }

        if (Schema::hasTable('occupational_history')) {
            $belongsToCompany = DB::table('occupational_history')
                ->where('employee_id', $employeeId)
                ->where('company_name', (string) $selectedCompany->company_name)
                ->exists();

            if ($belongsToCompany) {
                return $patient;
            }
        }

        return null;
    }

    protected function surveillancePatientPageContext(Request $request, int $employeeId): ?array
    {
        $companyId = (int) $request->input('company_id', $request->query('company_id', 0));
        $selectedCompany = $companyId > 0 ? $this->findCompany($request, $companyId) : null;
        $patientRecord = $this->findSurveillancePatient($request, $employeeId, $selectedCompany);

        if (! $patientRecord) {
            return null;
        }

        if ($selectedCompany === null && Schema::hasColumn('employee', 'company_id') && (int) ($patientRecord->company_id ?? 0) > 0) {
            $selectedCompany = $this->findCompany($request, (int) $patientRecord->company_id);
        }
        $supportingContext = $this->surveillancePatientSupportingContext($employeeId, $selectedCompany, $patientRecord);

        return [
            'selectedCompany' => $selectedCompany,
            'patientRecord' => $patientRecord,
            'patientFormData' => $supportingContext['patientFormData'],
            'returnTo' => $this->surveillancePatientReturnUrl($request, (int) ($selectedCompany->company_id ?? 0)),
        ];
    }

    protected function surveillancePatientSupportingContext(int $employeeId, ?object $selectedCompany = null, ?object $patientRecord = null, ?int $surveillanceId = null): array
    {
        $patientRecord = $patientRecord ?? ($employeeId > 0 ? DB::table('employee')->where('employee_id', $employeeId)->first() : null);

        $occupationalRows = collect();
        if (Schema::hasTable('occupational_history')) {
            $occupationalQuery = DB::table('occupational_history')
                ->where('employee_id', $employeeId);

            if (Schema::hasColumn('occupational_history', 'surveillance_id')) {
                if (($surveillanceId ?? 0) > 0) {
                    $occupationalRows = DB::table('occupational_history')
                        ->where('employee_id', $employeeId)
                        ->where('surveillance_id', $surveillanceId)
                        ->orderBy('occupHistory_id')
                        ->get();
                }

                if ($occupationalRows->isEmpty()) {
                    $occupationalQuery->whereNull('surveillance_id');
                } else {
                    $occupationalQuery = null;
                }
            }

            if ($occupationalQuery !== null && Schema::hasColumn('occupational_history', 'occupHistory_id')) {
                $occupationalQuery->orderBy('occupHistory_id');
            }

            if ($occupationalQuery !== null) {
                $occupationalRows = $occupationalQuery->get();
            }
        }

        $currentOccupational = $occupationalRows->first(function ($row) use ($selectedCompany) {
            return $selectedCompany
                && strcasecmp(trim((string) ($row->company_name ?? '')), trim((string) ($selectedCompany->company_name ?? ''))) === 0;
        }) ?: $occupationalRows->first();

        $pastOccupationalRows = $occupationalRows->values();
        if ($currentOccupational) {
            $pastOccupationalRows = $occupationalRows
                ->reject(static fn ($row) => (int) ($row->occupHistory_id ?? 0) === (int) ($currentOccupational->occupHistory_id ?? 0))
                ->values();
        }

        $medicalHistory = null;
        if (Schema::hasTable('medical_history')) {
            $medicalHistoryQuery = DB::table('medical_history')
                ->where('employee_id', $employeeId);
            $this->applyMedicalHistoryPresenceFilter($medicalHistoryQuery);
            if (Schema::hasColumn('medical_history', 'surveillance_id')) {
                if (($surveillanceId ?? 0) > 0) {
                    $medicalHistory = DB::table('medical_history')
                        ->where('employee_id', $employeeId)
                        ->where('surveillance_id', $surveillanceId);
                    $this->applyMedicalHistoryPresenceFilter($medicalHistory);
                    $medicalHistory = $medicalHistory
                        ->orderByDesc('medHistory_id')
                        ->first();
                }
                if ($medicalHistory === null) {
                    $medicalHistoryQuery->whereNull('surveillance_id');
                } else {
                    $medicalHistoryQuery = null;
                }
            }
            if ($medicalHistoryQuery !== null && Schema::hasColumn('medical_history', 'medHistory_id')) {
                $medicalHistoryQuery->orderByDesc('medHistory_id');
            }
            if ($medicalHistoryQuery !== null) {
                $medicalHistory = $medicalHistoryQuery->first();
            }
        }

        $personalHistory = null;
        if (Schema::hasTable('personal_social_history')) {
            $personalHistoryQuery = DB::table('personal_social_history')->where('employee_id', $employeeId);
            if (Schema::hasColumn('personal_social_history', 'surveillance_id')) {
                if (($surveillanceId ?? 0) > 0) {
                    $personalHistory = DB::table('personal_social_history')
                        ->where('employee_id', $employeeId)
                        ->where('surveillance_id', $surveillanceId)
                        ->orderByDesc('perSocHistory_id')
                        ->first();
                }
                if ($personalHistory === null) {
                    $personalHistoryQuery->whereNull('surveillance_id');
                } else {
                    $personalHistoryQuery = null;
                }
            }
            if ($personalHistoryQuery !== null && Schema::hasColumn('personal_social_history', 'perSocHistory_id')) {
                $personalHistoryQuery->orderByDesc('perSocHistory_id');
            }
            if ($personalHistoryQuery !== null) {
                $personalHistory = $personalHistoryQuery->first();
            }
        }

        $trainingHistory = null;
        if (Schema::hasTable('training_history')) {
            $trainingHistoryQuery = DB::table('training_history')->where('employee_id', $employeeId);
            if (Schema::hasColumn('training_history', 'surveillance_id')) {
                if (($surveillanceId ?? 0) > 0) {
                    $trainingHistory = DB::table('training_history')
                        ->where('employee_id', $employeeId)
                        ->where('surveillance_id', $surveillanceId)
                        ->orderByDesc('trainingHistory_id')
                        ->first();
                }
                if ($trainingHistory === null) {
                    $trainingHistoryQuery->whereNull('surveillance_id');
                } else {
                    $trainingHistoryQuery = null;
                }
            }
            if ($trainingHistoryQuery !== null && Schema::hasColumn('training_history', 'trainingHistory_id')) {
                $trainingHistoryQuery->orderByDesc('trainingHistory_id');
            }
            if ($trainingHistoryQuery !== null) {
                $trainingHistory = $trainingHistoryQuery->first();
            }
        }

        return [
            'medicalHistory' => $medicalHistory,
            'currentOccupational' => $currentOccupational,
            'pastOccupationalRows' => $pastOccupationalRows,
            'personalHistory' => $personalHistory,
            'trainingHistory' => $trainingHistory,
            'patientFormData' => $this->surveillancePatientFormDefaults(
                $patientRecord,
                $selectedCompany,
                $medicalHistory,
                $currentOccupational,
                $pastOccupationalRows,
                $personalHistory,
                $trainingHistory
            ),
        ];
    }

    protected function surveillancePatientReturnUrl(Request $request, int $companyId = 0): string
    {
        $returnTo = trim((string) $request->input('return_to', $request->query('return_to', '')));
        if ($returnTo !== '') {
            $parts = parse_url($returnTo);
            $host = strtolower((string) ($parts['host'] ?? ''));
            $requestHost = strtolower((string) $request->getHost());

            if ($host === '' || $host === $requestHost) {
                return $returnTo;
            }
        }

        return route('surveillance.patient', array_filter(['company_id' => $companyId > 0 ? $companyId : null]));
    }

    protected function surveillancePatientFormDefaults(?object $patient, ?object $selectedCompany, ?object $medicalHistory, ?object $currentOccupational, $pastOccupationalRows, ?object $personalHistory, ?object $trainingHistory): array
    {
        [$phoneCode, $phoneNumber] = $this->splitCountryCodeNumber((string) ($patient->employee_telephone ?? ''), '60');

        $pastRows = collect($pastOccupationalRows)->values();

        return [
            'company_id' => (string) ($selectedCompany->company_id ?? ''),
            'employee_firstName' => (string) ($patient->employee_firstName ?? ''),
            'employee_lastName' => (string) ($patient->employee_lastName ?? ''),
            'employee_NRIC' => (string) ($patient->employee_NRIC ?? ''),
            'employee_passportNo' => (string) ($patient->employee_passportNo ?? ''),
            'employee_DOB' => (string) ($patient->employee_DOB ?? ''),
            'employee_gender' => (string) ($patient->employee_gender ?? ''),
            'employee_address' => (string) ($patient->employee_address ?? ''),
            'employee_postcode' => (string) ($patient->employee_postcode ?? ''),
            'employee_district' => (string) ($patient->employee_district ?? ''),
            'employee_state' => (string) ($patient->employee_state ?? ''),
            'employee_phone_code' => '+' . ltrim((string) $phoneCode, '+'),
            'employee_telephone' => (string) $phoneNumber,
            'employee_email' => (string) ($patient->employee_email ?? ''),
            'employee_ethnicity' => (string) ($patient->employee_ethnicity ?? ''),
            'employee_citizenship' => (string) ($patient->employee_citizenship ?? ''),
            'employee_martialStatus' => (string) ($patient->employee_martialStatus ?? ''),
            'employee_ethnicity_other' => (string) ($patient->employee_ethnicity_other ?? ''),
            'employee_citizenship_other' => (string) ($patient->employee_citizenship_other ?? ''),
            'employee_martial_other' => (string) ($patient->employee_martial_other ?? ''),
            'no_of_children' => (string) ($patient->no_of_children ?? ''),
            'years_married' => (string) ($patient->years_married ?? ''),
            'diagnosed_history' => (string) ($medicalHistory->diagnosed_history ?? ''),
            'diagnosed_history_status' => $this->medicalHistoryStoredStatus($medicalHistory, 'diagnosed_history'),
            'medication_history' => (string) ($medicalHistory->medication_history ?? ''),
            'medication_history_status' => $this->medicalHistoryStoredStatus($medicalHistory, 'medication_history'),
            'admitted_history' => (string) ($medicalHistory->admitted_history ?? ''),
            'admitted_history_status' => $this->medicalHistoryStoredStatus($medicalHistory, 'admitted_history'),
            'family_history' => (string) ($medicalHistory->family_history ?? ''),
            'family_history_status' => $this->medicalHistoryStoredStatus($medicalHistory, 'family_history'),
            'others_history' => (string) ($medicalHistory->others_history ?? ''),
            'others_history_status' => $this->medicalHistoryStoredStatus($medicalHistory, 'others_history'),
            'current_job_title' => (string) ($currentOccupational->job_title ?? ''),
            'current_company_name' => (string) ($selectedCompany->company_name ?? ($currentOccupational->company_name ?? '')),
            'current_start_employment_date' => (string) ($currentOccupational->start_employment_date ?? ''),
            'current_employment_duration' => (string) ($currentOccupational->employment_duration ?? ''),
            'current_chemical_exposure_duration' => (string) ($currentOccupational->chemical_exposure_duration ?? ''),
            'current_chemical_exposure_incidents' => (string) ($currentOccupational->chemical_exposure_incidents ?? ''),
            'occup_job_title' => $pastRows->pluck('job_title')->map(static fn ($value) => (string) $value)->all(),
            'occup_company_name' => $pastRows->pluck('company_name')->map(static fn ($value) => (string) $value)->all(),
            'start_employment_date' => $pastRows->pluck('start_employment_date')->map(static fn ($value) => (string) $value)->all(),
            'employment_duration' => $pastRows->pluck('employment_duration')->map(static fn ($value) => (string) $value)->all(),
            'chemical_exposure_duration' => $pastRows->pluck('chemical_exposure_duration')->map(static fn ($value) => (string) $value)->all(),
            'chemical_exposure_incidents' => $pastRows->pluck('chemical_exposure_incidents')->map(static fn ($value) => (string) $value)->all(),
            'smoking_history' => (string) ($personalHistory->smoking_history ?? ''),
            'years_of_smoking' => (string) ($personalHistory->years_of_smoking ?? ''),
            'no_of_cigarettes' => (string) ($personalHistory->no_of_cigarettes ?? ''),
            'vaping_history' => (string) ($personalHistory->vaping_history ?? ''),
            'years_of_vaping' => (string) ($personalHistory->years_of_vaping ?? ''),
            'hobby' => (string) ($personalHistory->hobby ?? ''),
            'handling_of_chemical' => (string) ($trainingHistory->handling_of_chemical ?? ''),
            'chemical_comments' => (string) ($trainingHistory->chemical_comments ?? ''),
            'sign_symptoms' => (string) ($trainingHistory->sign_symptoms ?? ''),
            'sign_comments' => (string) ($trainingHistory->sign_comments ?? ''),
            'chemical_poisoning' => (string) ($trainingHistory->chemical_poisoning ?? ''),
            'poisoning_comments' => (string) ($trainingHistory->poisoning_comments ?? ''),
            'proper_PPE' => (string) ($trainingHistory->proper_PPE ?? ''),
            'proper_comments' => (string) ($trainingHistory->proper_comments ?? ''),
            'PPE_usage' => (string) ($trainingHistory->PPE_usage ?? ''),
            'usage_comments' => (string) ($trainingHistory->usage_comments ?? ''),
        ];
    }

    protected function validateSurveillancePatientRequest(Request $request): array
    {
        return $request->validate([
            'company_id' => ['nullable', 'integer'],
            'employee_firstName' => ['required', 'string', 'max:100'],
            'employee_lastName' => ['required', 'string', 'max:100'],
            'employee_NRIC' => ['nullable', 'string', 'max:20'],
            'employee_passportNo' => ['nullable', 'string', 'max:30'],
            'employee_DOB' => ['nullable', 'date'],
            'employee_gender' => ['nullable', 'in:Male,Female'],
            'employee_address' => ['nullable', 'string', 'max:255'],
            'employee_postcode' => ['nullable', 'string', 'max:10'],
            'employee_district' => ['nullable', 'string', 'max:100'],
            'employee_state' => ['nullable', 'string', 'max:100'],
            'employee_phone_code' => ['nullable', 'string', 'max:10'],
            'employee_telephone' => ['nullable', 'string', 'max:20'],
            'employee_email' => ['nullable', 'email', 'max:150'],
            'employee_ethnicity' => ['nullable', 'string', 'max:50'],
            'employee_ethnicity_other' => ['nullable', 'string', 'max:1000'],
            'employee_citizenship' => ['nullable', 'string', 'max:50'],
            'employee_citizenship_other' => ['nullable', 'string', 'max:1000'],
            'employee_martialStatus' => ['nullable', 'string', 'max:50'],
            'employee_martial_other' => ['nullable', 'string', 'max:1000'],
            'no_of_children' => ['nullable', 'integer', 'min:0'],
            'years_married' => ['nullable', 'integer', 'min:0'],
            'diagnosed_history' => ['nullable', 'string'],
            'diagnosed_history_status' => ['nullable', 'in:Yes,No'],
            'medication_history' => ['nullable', 'string'],
            'medication_history_status' => ['nullable', 'in:Yes,No'],
            'admitted_history' => ['nullable', 'string'],
            'admitted_history_status' => ['nullable', 'in:Yes,No'],
            'family_history' => ['nullable', 'string'],
            'family_history_status' => ['nullable', 'in:Yes,No'],
            'others_history' => ['nullable', 'string'],
            'others_history_status' => ['nullable', 'in:Yes,No'],
            'current_job_title' => ['nullable', 'string', 'max:150'],
            'current_company_name' => ['nullable', 'string', 'max:150'],
            'current_start_employment_date' => ['nullable', 'date'],
            'current_employment_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_incidents' => ['nullable', 'string'],
            'occup_job_title' => ['array'],
            'occup_job_title.*' => ['nullable', 'string', 'max:150'],
            'occup_company_name' => ['array'],
            'occup_company_name.*' => ['nullable', 'string', 'max:150'],
            'start_employment_date' => ['array'],
            'start_employment_date.*' => ['nullable', 'date'],
            'employment_duration' => ['array'],
            'employment_duration.*' => ['nullable', 'string', 'max:100'],
            'chemical_exposure_duration' => ['array'],
            'chemical_exposure_duration.*' => ['nullable', 'string', 'max:100'],
            'chemical_exposure_incidents' => ['array'],
            'chemical_exposure_incidents.*' => ['nullable', 'string'],
            'smoking_history' => ['nullable', 'string', 'max:50'],
            'years_of_smoking' => ['nullable', 'integer', 'min:0'],
            'no_of_cigarettes' => ['nullable', 'integer', 'min:0'],
            'vaping_history' => ['nullable', 'string', 'max:10'],
            'years_of_vaping' => ['nullable', 'integer', 'min:0'],
            'hobby' => ['nullable', 'string'],
            'handling_of_chemical' => ['nullable', 'string', 'max:10'],
            'chemical_comments' => ['nullable', 'string'],
            'sign_symptoms' => ['nullable', 'string', 'max:10'],
            'sign_comments' => ['nullable', 'string'],
            'chemical_poisoning' => ['nullable', 'string', 'max:10'],
            'poisoning_comments' => ['nullable', 'string'],
            'proper_PPE' => ['nullable', 'string', 'max:10'],
            'proper_comments' => ['nullable', 'string'],
            'PPE_usage' => ['nullable', 'string', 'max:10'],
            'usage_comments' => ['nullable', 'string'],
        ]);
    }

    protected function validateSurveillancePatientSupportingRequest(Request $request): array
    {
        return $request->validate([
            'diagnosed_history' => ['nullable', 'string'],
            'diagnosed_history_status' => ['nullable', 'in:Yes,No'],
            'medication_history' => ['nullable', 'string'],
            'medication_history_status' => ['nullable', 'in:Yes,No'],
            'admitted_history' => ['nullable', 'string'],
            'admitted_history_status' => ['nullable', 'in:Yes,No'],
            'family_history' => ['nullable', 'string'],
            'family_history_status' => ['nullable', 'in:Yes,No'],
            'others_history' => ['nullable', 'string'],
            'others_history_status' => ['nullable', 'in:Yes,No'],
            'current_job_title' => ['nullable', 'string', 'max:150'],
            'current_company_name' => ['nullable', 'string', 'max:150'],
            'current_start_employment_date' => ['nullable', 'date'],
            'current_employment_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_incidents' => ['nullable', 'string'],
            'occup_job_title' => ['array'],
            'occup_job_title.*' => ['nullable', 'string', 'max:150'],
            'occup_company_name' => ['array'],
            'occup_company_name.*' => ['nullable', 'string', 'max:150'],
            'start_employment_date' => ['array'],
            'start_employment_date.*' => ['nullable', 'date'],
            'employment_duration' => ['array'],
            'employment_duration.*' => ['nullable', 'string', 'max:100'],
            'chemical_exposure_duration' => ['array'],
            'chemical_exposure_duration.*' => ['nullable', 'string', 'max:100'],
            'chemical_exposure_incidents' => ['array'],
            'chemical_exposure_incidents.*' => ['nullable', 'string'],
            'smoking_history' => ['nullable', 'string', 'max:50'],
            'years_of_smoking' => ['nullable', 'integer', 'min:0'],
            'no_of_cigarettes' => ['nullable', 'integer', 'min:0'],
            'vaping_history' => ['nullable', 'string', 'max:10'],
            'years_of_vaping' => ['nullable', 'integer', 'min:0'],
            'hobby' => ['nullable', 'string'],
            'handling_of_chemical' => ['nullable', 'string', 'max:10'],
            'chemical_comments' => ['nullable', 'string'],
            'sign_symptoms' => ['nullable', 'string', 'max:10'],
            'sign_comments' => ['nullable', 'string'],
            'chemical_poisoning' => ['nullable', 'string', 'max:10'],
            'poisoning_comments' => ['nullable', 'string'],
            'proper_PPE' => ['nullable', 'string', 'max:10'],
            'proper_comments' => ['nullable', 'string'],
            'PPE_usage' => ['nullable', 'string', 'max:10'],
            'usage_comments' => ['nullable', 'string'],
        ]);
    }

    protected function surveillancePatientEmployeePayload(array $validated, Request $request, ?object $selectedCompany = null): array
    {
        $clinicId = (int) $request->session()->get('active_clinic_id', 0);

        $payload = [
            'employee_firstName' => trim((string) $validated['employee_firstName']),
            'employee_lastName' => trim((string) $validated['employee_lastName']),
            'employee_NRIC' => $this->nullableTrim($validated['employee_NRIC'] ?? null),
            'employee_passportNo' => $this->nullableTrim($validated['employee_passportNo'] ?? null),
            'employee_DOB' => $validated['employee_DOB'] ?? null,
            'employee_gender' => $validated['employee_gender'] ?? null,
            'employee_address' => $this->nullableTrim($validated['employee_address'] ?? null),
            'employee_postcode' => $this->nullableTrim($validated['employee_postcode'] ?? null),
            'employee_district' => $this->nullableTrim($validated['employee_district'] ?? null),
            'employee_state' => $this->nullableTrim($validated['employee_state'] ?? null),
            'employee_telephone' => $this->buildCountryCodeNumber($validated['employee_phone_code'] ?? null, $validated['employee_telephone'] ?? null),
            'employee_email' => $this->nullableTrim($validated['employee_email'] ?? null),
            'employee_ethnicity' => $this->nullableTrim($validated['employee_ethnicity'] ?? null),
            'employee_citizenship' => $this->nullableTrim($validated['employee_citizenship'] ?? null),
            'employee_martialStatus' => $this->nullableTrim($validated['employee_martialStatus'] ?? null),
            'no_of_children' => $validated['no_of_children'] ?? null,
            'years_married' => $validated['years_married'] ?? null,
        ];

        if (Schema::hasColumn('employee', 'employee_ethnicity_other')) {
            $payload['employee_ethnicity_other'] = ($payload['employee_ethnicity'] ?? null) === 'Others'
                ? $this->nullableTrim($validated['employee_ethnicity_other'] ?? null)
                : null;
        }

        if (Schema::hasColumn('employee', 'employee_citizenship_other')) {
            $payload['employee_citizenship_other'] = ($payload['employee_citizenship'] ?? null) === 'Others'
                ? $this->nullableTrim($validated['employee_citizenship_other'] ?? null)
                : null;
        }

        if (Schema::hasColumn('employee', 'employee_martial_other')) {
            $payload['employee_martial_other'] = ($payload['employee_martialStatus'] ?? null) === 'Others'
                ? $this->nullableTrim($validated['employee_martial_other'] ?? null)
                : null;
        }

        if ($clinicId > 0 && Schema::hasColumn('employee', 'clinic_id')) {
            $payload['clinic_id'] = $clinicId;
        }

        if (Schema::hasColumn('employee', 'company_id')) {
            $payload['company_id'] = $selectedCompany ? (int) $selectedCompany->company_id : null;
        }

        return $payload;
    }

    protected function syncSurveillancePatientSupportingData(int $employeeId, array $validated, ?object $selectedCompany = null, ?int $surveillanceId = null): void
    {
        $scopedSurveillanceId = ($surveillanceId ?? 0) > 0 ? (int) $surveillanceId : null;

        if (Schema::hasTable('medical_history')) {
            DB::table('medical_history')
                ->where('employee_id', $employeeId)
                ->when(
                    Schema::hasColumn('medical_history', 'surveillance_id'),
                    fn ($query) => $scopedSurveillanceId !== null ? $query->where('surveillance_id', $scopedSurveillanceId) : $query->whereNull('surveillance_id')
                )
                ->delete();

            $payload = [
                'diagnosed_history' => $this->nullableTrim($validated['diagnosed_history'] ?? null),
                'medication_history' => $this->nullableTrim($validated['medication_history'] ?? null),
                'admitted_history' => $this->nullableTrim($validated['admitted_history'] ?? null),
                'family_history' => $this->nullableTrim($validated['family_history'] ?? null),
                'others_history' => $this->nullableTrim($validated['others_history'] ?? null),
                'employee_id' => $employeeId,
            ];
            foreach (['diagnosed_history', 'medication_history', 'admitted_history', 'family_history', 'others_history'] as $field) {
                $resultColumn = $field . '_result';
                if (Schema::hasColumn('medical_history', $resultColumn)) {
                    $payload[$resultColumn] = $this->medicalHistoryResultValue($validated, $field);
                }
            }
            if (Schema::hasColumn('medical_history', 'surveillance_id')) {
                $payload['surveillance_id'] = $scopedSurveillanceId;
            }
            DB::table('medical_history')->insert($payload);
        }

        if (Schema::hasTable('occupational_history')) {
            DB::table('occupational_history')
                ->where('employee_id', $employeeId)
                ->when(
                    Schema::hasColumn('occupational_history', 'surveillance_id'),
                    fn ($query) => $scopedSurveillanceId !== null ? $query->where('surveillance_id', $scopedSurveillanceId) : $query->whereNull('surveillance_id')
                )
                ->delete();

            $currentPayload = [
                'job_title' => $this->nullableTrim($validated['current_job_title'] ?? null),
                'company_name' => $this->nullableTrim($selectedCompany->company_name ?? ($validated['current_company_name'] ?? null)),
                'start_employment_date' => $validated['current_start_employment_date'] ?? null,
                'employment_duration' => $this->nullableTrim($validated['current_employment_duration'] ?? null),
                'chemical_exposure_duration' => $this->nullableTrim($validated['current_chemical_exposure_duration'] ?? null),
                'chemical_exposure_incidents' => $this->nullableTrim($validated['current_chemical_exposure_incidents'] ?? null),
                'employee_id' => $employeeId,
            ];
            if (Schema::hasColumn('occupational_history', 'surveillance_id')) {
                $currentPayload['surveillance_id'] = $scopedSurveillanceId;
            }

            if (implode('', array_map(static fn ($value) => (string) ($value ?? ''), $currentPayload)) !== '') {
                DB::table('occupational_history')->insert($currentPayload);
            }

            $jobTitles = $validated['occup_job_title'] ?? [];
            $companyNames = $validated['occup_company_name'] ?? [];
            $startEmploymentDates = $validated['start_employment_date'] ?? [];
            $employmentDurations = $validated['employment_duration'] ?? [];
            $exposureDurations = $validated['chemical_exposure_duration'] ?? [];
            $exposureIncidents = $validated['chemical_exposure_incidents'] ?? [];

            $rowCount = max(count($jobTitles), count($companyNames), count($startEmploymentDates), count($employmentDurations), count($exposureDurations), count($exposureIncidents));

            for ($index = 0; $index < $rowCount; $index++) {
                $payload = [
                    'job_title' => $this->nullableTrim($jobTitles[$index] ?? null),
                    'company_name' => $this->nullableTrim($companyNames[$index] ?? null),
                    'start_employment_date' => $startEmploymentDates[$index] ?? null,
                    'employment_duration' => $this->nullableTrim($employmentDurations[$index] ?? null),
                    'chemical_exposure_duration' => $this->nullableTrim($exposureDurations[$index] ?? null),
                    'chemical_exposure_incidents' => $this->nullableTrim($exposureIncidents[$index] ?? null),
                ];

                if (implode('', array_map(static fn ($value) => (string) ($value ?? ''), $payload)) === '') {
                    continue;
                }

                $insertPayload = $payload + ['employee_id' => $employeeId];
                if (Schema::hasColumn('occupational_history', 'surveillance_id')) {
                    $insertPayload['surveillance_id'] = $scopedSurveillanceId;
                }
                DB::table('occupational_history')->insert($insertPayload);
            }
        }

        if (Schema::hasTable('personal_social_history')) {
            DB::table('personal_social_history')
                ->where('employee_id', $employeeId)
                ->when(
                    Schema::hasColumn('personal_social_history', 'surveillance_id'),
                    fn ($query) => $scopedSurveillanceId !== null ? $query->where('surveillance_id', $scopedSurveillanceId) : $query->whereNull('surveillance_id')
                )
                ->delete();

            $payload = [
                'smoking_history' => $this->nullableTrim($validated['smoking_history'] ?? null),
                'years_of_smoking' => $validated['years_of_smoking'] ?? null,
                'no_of_cigarettes' => $validated['no_of_cigarettes'] ?? null,
                'vaping_history' => $this->nullableTrim($validated['vaping_history'] ?? null),
                'years_of_vaping' => $validated['years_of_vaping'] ?? null,
                'hobby' => $this->nullableTrim($validated['hobby'] ?? null),
                'employee_id' => $employeeId,
            ];
            if (Schema::hasColumn('personal_social_history', 'surveillance_id')) {
                $payload['surveillance_id'] = $scopedSurveillanceId;
            }
            DB::table('personal_social_history')->insert($payload);
        }

        if (Schema::hasTable('training_history')) {
            DB::table('training_history')
                ->where('employee_id', $employeeId)
                ->when(
                    Schema::hasColumn('training_history', 'surveillance_id'),
                    fn ($query) => $scopedSurveillanceId !== null ? $query->where('surveillance_id', $scopedSurveillanceId) : $query->whereNull('surveillance_id')
                )
                ->delete();

            $payload = [
                'handling_of_chemical' => $this->nullableTrim($validated['handling_of_chemical'] ?? null),
                'chemical_comments' => $this->nullableTrim($validated['chemical_comments'] ?? null),
                'sign_symptoms' => $this->nullableTrim($validated['sign_symptoms'] ?? null),
                'sign_comments' => $this->nullableTrim($validated['sign_comments'] ?? null),
                'chemical_poisoning' => $this->nullableTrim($validated['chemical_poisoning'] ?? null),
                'poisoning_comments' => $this->nullableTrim($validated['poisoning_comments'] ?? null),
                'proper_PPE' => $this->nullableTrim($validated['proper_PPE'] ?? null),
                'proper_comments' => $this->nullableTrim($validated['proper_comments'] ?? null),
                'PPE_usage' => $this->nullableTrim($validated['PPE_usage'] ?? null),
                'usage_comments' => $this->nullableTrim($validated['usage_comments'] ?? null),
                'employee_id' => $employeeId,
            ];
            if (Schema::hasColumn('training_history', 'surveillance_id')) {
                $payload['surveillance_id'] = $scopedSurveillanceId;
            }
            DB::table('training_history')->insert($payload);
        }
    }

    protected function companyReturnUrl(Request $request, ?object $record = null): string
    {
        $returnTo = trim((string) $request->input('return_to', $request->query('return_to', '')));
        if ($returnTo !== '') {
            $parts = parse_url($returnTo);
            $host = strtolower((string) ($parts['host'] ?? ''));
            $requestHost = strtolower((string) $request->getHost());

            if ($host === '' || $host === $requestHost) {
                return $returnTo;
            }
        }

        $module = strtolower((string) ($record->company_module ?? ''));

        if ($module === 'audiometry' && \Illuminate\Support\Facades\Route::has('audiometry.company')) {
            return route('audiometry.company');
        }

        if ($module === 'surveillance' && \Illuminate\Support\Facades\Route::has('surveillance.company')) {
            return route('surveillance.company');
        }

        return route('panel.company_list');
    }

    protected function companyFormDefaults(?object $record = null): array
    {
        $workUnitNames = [];
        $workUnitChemicalNames = [];
        $workUnitChemicalChraReportNos = [];
        $workUnitChemicalWorkers = [];

        if ($record && Schema::hasTable('company_work_units')) {
            $workUnits = DB::table('company_work_units')
                ->where('company_id', $record->company_id)
                ->orderBy('sort_order')
                ->orderBy('work_unit_id')
                ->get();

            foreach ($workUnits as $workUnitIndex => $workUnit) {
                $workUnitNames[] = (string) ($workUnit->work_unit_name ?? '');
                $chemicalRows = Schema::hasTable('company_work_unit_chemicals')
                    ? DB::table('company_work_unit_chemicals')
                        ->where('work_unit_id', $workUnit->work_unit_id)
                        ->orderBy('sort_order')
                        ->orderBy('work_unit_chemical_id')
                        ->get()
                    : collect();

                if ($chemicalRows->isEmpty()) {
                    $workUnitChemicalNames[$workUnitIndex] = [''];
                    $workUnitChemicalChraReportNos[$workUnitIndex] = [''];
                    $workUnitChemicalWorkers[$workUnitIndex] = [''];
                    continue;
                }

                $workUnitChemicalNames[$workUnitIndex] = $chemicalRows->pluck('chemical_name')->map(static fn ($value) => (string) $value)->all();
                $workUnitChemicalChraReportNos[$workUnitIndex] = $chemicalRows->pluck('chra_report_no')->map(static fn ($value) => (string) $value)->all();
                $workUnitChemicalWorkers[$workUnitIndex] = $chemicalRows->pluck('total_workers')->map(static fn ($value) => (string) ($value ?? ''))->all();
            }
        }

        if ($workUnitNames === []) {
            $workUnitNames = [''];
            $workUnitChemicalNames = [['']];
            $workUnitChemicalChraReportNos = [['']];
            $workUnitChemicalWorkers = [['']];
        }

        return [
            'company_name' => (string) ($record->company_name ?? ''),
            'mykpp_registration_no' => (string) ($record->mykpp_registration_no ?? ''),
            'company_address' => (string) ($record->company_address ?? ''),
            'company_postcode' => (string) ($record->company_postcode ?? ''),
            'company_district' => (string) ($record->company_district ?? ''),
            'company_state' => (string) ($record->company_state ?? ''),
            'company_telephone' => (string) ($record->company_telephone ?? ''),
            'company_email' => (string) ($record->company_email ?? ''),
            'company_fax' => (string) ($record->company_fax ?? ''),
            'total_workers' => (string) ($record->total_workers ?? '0'),
            'company_module' => (string) ($record->company_module ?? 'surveillance'),
            'work_unit_name' => $workUnitNames,
            'work_unit_chemical_name' => $workUnitChemicalNames,
            'work_unit_chemical_chra_report_no' => $workUnitChemicalChraReportNos,
            'work_unit_chemical_total_workers' => $workUnitChemicalWorkers,
        ];
    }

    protected function syncCompanyWorkUnits(int $companyId, array $validated): void
    {
        if ($companyId <= 0 || ! Schema::hasTable('company_work_units')) {
            return;
        }

        $existingWorkUnitIds = DB::table('company_work_units')
            ->where('company_id', $companyId)
            ->pluck('work_unit_id')
            ->all();

        if ($existingWorkUnitIds !== [] && Schema::hasTable('company_work_unit_chemicals')) {
            DB::table('company_work_unit_chemicals')->whereIn('work_unit_id', $existingWorkUnitIds)->delete();
        }

        DB::table('company_work_units')->where('company_id', $companyId)->delete();

        $workUnitNames = array_values((array) ($validated['work_unit_name'] ?? []));
        $chemicalNamesByUnit = (array) ($validated['work_unit_chemical_name'] ?? []);
        $chemicalChraByUnit = (array) ($validated['work_unit_chemical_chra_report_no'] ?? []);
        $chemicalWorkersByUnit = (array) ($validated['work_unit_chemical_total_workers'] ?? []);
        $companyTotalWorkers = 0;

        foreach ($workUnitNames as $workUnitIndex => $workUnitNameRaw) {
            $workUnitName = $this->nullableTrim($workUnitNameRaw);
            $chemicalNames = array_values((array) ($chemicalNamesByUnit[$workUnitIndex] ?? []));
            $chemicalChraReports = array_values((array) ($chemicalChraByUnit[$workUnitIndex] ?? []));
            $chemicalWorkers = array_values((array) ($chemicalWorkersByUnit[$workUnitIndex] ?? []));
            $chemicalRowCount = max(count($chemicalNames), count($chemicalChraReports), count($chemicalWorkers));

            $hasChemicalContent = false;
            for ($chemicalIndex = 0; $chemicalIndex < $chemicalRowCount; $chemicalIndex++) {
                if (
                    $this->nullableTrim($chemicalNames[$chemicalIndex] ?? null) !== null
                    || $this->nullableTrim($chemicalChraReports[$chemicalIndex] ?? null) !== null
                    || ($chemicalWorkers[$chemicalIndex] ?? null) !== null
                    && trim((string) ($chemicalWorkers[$chemicalIndex] ?? '')) !== ''
                ) {
                    $hasChemicalContent = true;
                    break;
                }
            }

            if ($workUnitName === null && ! $hasChemicalContent) {
                continue;
            }

            $workUnitId = (int) DB::table('company_work_units')->insertGetId([
                'company_id' => $companyId,
                'work_unit_name' => $workUnitName,
                'sort_order' => $workUnitIndex,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (! Schema::hasTable('company_work_unit_chemicals')) {
                continue;
            }

            for ($chemicalIndex = 0; $chemicalIndex < $chemicalRowCount; $chemicalIndex++) {
                $chemicalName = $this->nullableTrim($chemicalNames[$chemicalIndex] ?? null);
                $chraReportNo = $this->nullableTrim($chemicalChraReports[$chemicalIndex] ?? null);
                $totalWorkersRaw = $chemicalWorkers[$chemicalIndex] ?? null;
                $hasWorkerValue = trim((string) $totalWorkersRaw) !== '';
                $totalWorkers = $hasWorkerValue ? max(0, (int) $totalWorkersRaw) : null;

                if ($chemicalName === null && $chraReportNo === null && $totalWorkers === null) {
                    continue;
                }

                DB::table('company_work_unit_chemicals')->insert([
                    'work_unit_id' => $workUnitId,
                    'chemical_name' => $chemicalName,
                    'chra_report_no' => $chraReportNo,
                    'total_workers' => $totalWorkers,
                    'sort_order' => $chemicalIndex,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $companyTotalWorkers += (int) ($totalWorkers ?? 0);
            }
        }

        if (Schema::hasColumn('company', 'total_workers')) {
            DB::table('company')
                ->where('company_id', $companyId)
                ->update(['total_workers' => $companyTotalWorkers]);
        }
    }

    protected function doctorReferenceSummary(int $doctorId): array
    {
        $references = [];

        $referenceTables = [
            'chemical_information' => 'chemical information',
            'summary_report' => 'summary report',
        ];

        foreach ($referenceTables as $table => $label) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'doctor_id')) {
                continue;
            }

            $count = DB::table($table)
                ->where('doctor_id', $doctorId)
                ->count();

            if ($count > 0) {
                $references[] = $label;
            }
        }

        return $references;
    }

    protected function storeBase64Image(?string $value, string $prefix, string $directory): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (! preg_match('/^data:image\/(?P<type>png|jpe?g);base64,(?P<data>.+)$/i', $value, $matches)) {
            return null;
        }

        $binary = base64_decode(str_replace(' ', '+', $matches['data']), true);
        if ($binary === false) {
            return null;
        }

        $extension = strtolower($matches['type']) === 'jpeg' ? 'jpg' : strtolower($matches['type']);
        $filename = $prefix . Str::uuid() . '.' . $extension;
        $directory = trim($directory, '/\\');

        Storage::disk('public')->put($directory . '/' . $filename, $binary);

        return 'storage/' . $directory . '/' . $filename;
    }

    protected function storeUploadedFile($file, string $prefix, string $directory): ?string
    {
        if (! $file) {
            return null;
        }

        $directory = trim($directory, '/\\');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = $prefix . Str::uuid() . ($extension !== '' ? '.' . $extension : '');

        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return 'storage/' . $directory . '/' . $filename;
    }

    protected function sanitizeStoredFilenamePart(string $value, string $fallback): string
    {
        $value = trim($value);
        $value = preg_replace('/[\\\\\/:*?"<>|]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        $value = trim($value, " .\t\n\r\0\x0B");

        return $value !== '' ? $value : $fallback;
    }

    protected function buildBloodResultStoredFilename(UploadedFile $file, string $patientName, string $directory): string
    {
        $directory = trim($directory, '/\\');
        $originalName = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = strtolower((string) $file->getClientOriginalExtension());

        $baseName = $this->sanitizeStoredFilenamePart($originalName, 'blood-result');
        $patientName = $this->sanitizeStoredFilenamePart($patientName, 'patient');
        $combinedName = $baseName . '_' . $patientName;
        $combinedName = mb_substr($combinedName, 0, 180);

        $filename = $combinedName . ($extension !== '' ? '.' . $extension : '');
        $counter = 2;

        while (Storage::disk('public')->exists($directory . '/' . $filename)) {
            $filename = $combinedName . '_' . $counter . ($extension !== '' ? '.' . $extension : '');
            $counter++;
        }

        return $filename;
    }

    protected function deletePublicFile(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }

        $normalizedPath = str_replace('\\', '/', $path);
        if (Str::startsWith($normalizedPath, 'storage/')) {
            $storagePath = substr($normalizedPath, strlen('storage/'));
            if ($storagePath !== '' && Storage::disk('public')->exists($storagePath)) {
                Storage::disk('public')->delete($storagePath);
            }
        } elseif (Storage::disk('public')->exists($normalizedPath)) {
            Storage::disk('public')->delete($normalizedPath);
        }

        $fullPath = public_path($path);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }

    protected function renderSurveillanceRecordPage(Request $request, int $declarationId, bool $readOnly): View|RedirectResponse
    {
        $user = $this->requirePanelUser($request);
        if ($user instanceof RedirectResponse) {
            return $user;
        }

        $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        if (! $declaration) {
            return redirect()->route('surveillance.list')->withErrors(['record' => 'The selected surveillance record could not be found.']);
        }

        $surveillanceId = $this->resolveSurveillanceIdForDeclaration($declaration);
        $declaration->surveillance_id = $surveillanceId;
        $employeeId = (int) ($declaration->employee_id ?? 0);
        $companyId = (int) ($declaration->company_id ?? 0);

        $selectedEmployee = $employeeId > 0 ? DB::table('employee')->where('employee_id', $employeeId)->first() : null;
        $selectedCompany = $companyId > 0 ? DB::table('company')->where('company_id', $companyId)->first() : null;
        $doctor = ! empty($declaration->doctor_id) ? DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first() : $this->linkedDoctorRecord($user);

        $patientSupportingContext = $this->surveillancePatientSupportingContext($employeeId, $selectedCompany, $selectedEmployee, $surveillanceId > 0 ? $surveillanceId : null);

        $context = [
            'chemicalInfo' => $surveillanceId > 0 && Schema::hasTable('chemical_information') ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first() : null,
            'historyOfHealth' => $surveillanceId > 0 && Schema::hasTable('history_of_health') ? DB::table('history_of_health')->where('surveillance_id', $surveillanceId)->first() : null,
            'clinicalFindings' => $surveillanceId > 0 && Schema::hasTable('clinical_findings') ? DB::table('clinical_findings')->where('surveillance_id', $surveillanceId)->first() : null,
            'physicalExam' => $surveillanceId > 0 && Schema::hasTable('physical_examination') ? DB::table('physical_examination')->where('surveillance_id', $surveillanceId)->first() : null,
            'targetOrgan' => $surveillanceId > 0 && Schema::hasTable('target_organ') ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first() : null,
            'otherTargetTests' => $this->surveillanceOtherTargetTests($surveillanceId),
            'biologicalMonitoring' => $surveillanceId > 0 && Schema::hasTable('biological_monitoring') ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first() : null,
            'fitnessRespirator' => $surveillanceId > 0 && Schema::hasTable('fitness_respirator') ? DB::table('fitness_respirator')->where('surveillance_id', $surveillanceId)->first() : null,
            'msFindings' => $surveillanceId > 0 && Schema::hasTable('ms_findings') ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first() : null,
            'recommendationData' => $surveillanceId > 0 && Schema::hasTable('recommendation') ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first() : null,
            'patientFormData' => $patientSupportingContext['patientFormData'],
        ];

        return view($readOnly ? 'surveillance.survList_view' : 'surveillance.survList_edit', array_merge(
            $this->buildViewData($request, $user),
            $context,
            [
                'selectedEmployee' => $selectedEmployee,
                'selectedCompany' => $selectedCompany,
                'declaration' => $declaration,
                'declarationId' => $declarationId,
                'surveillanceId' => $surveillanceId,
                'doctor' => $doctor,
                'sectionStatuses' => $this->surveillanceSectionStatusesFromModels($context),
                'pageMode' => $readOnly ? 'view' : 'edit',
                'readOnly' => $readOnly,
                'showRecordTabs' => true,
                'recordTabActive' => 'examination',
            ]
        ));
    }

    protected function surveillanceExamSaveResponse(Request $request, bool $ok, array $payload)
    {
        if ($request->ajax() || $request->expectsJson()) {
            $status = $ok ? 200 : 422;
            return response()->json($payload, $status);
        }

        if (! $ok) {
            return back()->withErrors(['surveillance' => (string) ($payload['error'] ?? 'Unable to save the surveillance examination.')])->withInput();
        }

        if (($payload['save_mode'] ?? 'draft') === 'final') {
            return redirect()->route('surveillance.record.view', ['declaration' => $payload['declaration_id']])
                ->with('status', 'Surveillance examination finalized successfully. This record is now locked and available in reports.');
        }

        return redirect()->route('surveillance.record.edit', ['declaration' => $payload['declaration_id']])
            ->with('status', 'Surveillance examination saved as draft successfully.');
    }

    protected function upsertSurveillanceRow(string $table, string $primaryKey, int $id, array $lookup, array $payload): int
    {
        if (! Schema::hasTable($table)) {
            return $id;
        }

        $record = $id > 0
            ? DB::table($table)->where($primaryKey, $id)->first()
            : DB::table($table)->where($lookup)->first();

        if ($record) {
            DB::table($table)->where($primaryKey, $record->{$primaryKey})->update($payload);
            return (int) $record->{$primaryKey};
        }

        return (int) DB::table($table)->insertGetId($payload);
    }

    protected function upsertSurveillanceChildRow(string $table, string $primaryKey, int $surveillanceId, int $employeeId, array $payload): ?int
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $record = DB::table($table)
            ->where('surveillance_id', $surveillanceId)
            ->where('employee_id', $employeeId)
            ->first();

        if ($record) {
            DB::table($table)->where($primaryKey, $record->{$primaryKey})->update($payload);
            return (int) $record->{$primaryKey};
        }

        return (int) DB::table($table)->insertGetId($payload);
    }

    protected function nullableChoice($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }

    protected function normalizeSummaryReportDecision(?string $decision): ?string
    {
        $decision = trim((string) ($decision ?? ''));
        if ($decision === '') {
            return null;
        }

        $allowedValues = [];

        try {
            $column = DB::selectOne("SHOW COLUMNS FROM `summary_report` LIKE 'decision'");
            $type = (string) ($column->Type ?? '');
            if (preg_match("/^enum\\((.*)\\)$/i", $type, $matches) === 1) {
                $allowedValues = str_getcsv($matches[1], ',', "'");
            }
        } catch (\Throwable $exception) {
            $allowedValues = [];
        }

        if ($allowedValues === []) {
            $allowedValues = ['Continue MS', 'Stop MS'];
        }

        foreach ($allowedValues as $allowedValue) {
            if (strcasecmp($decision, (string) $allowedValue) === 0) {
                return (string) $allowedValue;
            }
        }

        $aliases = [
            'continue ms' => ['continue ms', 'continue', 'continue medical surveillance'],
            'stop ms' => ['stop ms', 'stop', 'stop medical surveillance'],
        ];

        $normalizedInput = strtolower($decision);
        foreach ($allowedValues as $allowedValue) {
            $allowedKey = strtolower((string) $allowedValue);
            $candidateAliases = $aliases[$allowedKey] ?? [$allowedKey];
            if (in_array($normalizedInput, $candidateAliases, true)) {
                return (string) $allowedValue;
            }
        }

        return null;
    }

    protected function resolveSurveillanceIdForDeclaration(object $declaration): int
    {
        $surveillanceId = (int) ($declaration->surveillance_id ?? 0);
        if ($surveillanceId > 0 && $this->surveillanceRecordExists($surveillanceId)) {
            return $surveillanceId;
        }

        if (! Schema::hasTable('chemical_information')) {
            return $surveillanceId;
        }

        $fallbackQuery = DB::table('chemical_information')
            ->where('employee_id', (int) ($declaration->employee_id ?? 0))
            ->where('company_id', (int) ($declaration->company_id ?? 0));

        if (! empty($declaration->doctor_id)) {
            $fallbackQuery->where('doctor_id', (int) $declaration->doctor_id);
        }

        $fallbackRecord = $fallbackQuery
            ->orderByDesc('surveillance_id')
            ->first(['surveillance_id']);

        $fallbackSurveillanceId = (int) ($fallbackRecord->surveillance_id ?? 0);
        if ($fallbackSurveillanceId > 0 && $fallbackSurveillanceId !== $surveillanceId) {
            DB::table('declaration')
                ->where('declaration_id', (int) $declaration->declaration_id)
                ->update(['surveillance_id' => $fallbackSurveillanceId]);

            $surveillanceId = $fallbackSurveillanceId;
        }

        return $surveillanceId;
    }

    protected function surveillanceRecordExists(int $surveillanceId): bool
    {
        if ($surveillanceId <= 0 || ! Schema::hasTable('chemical_information')) {
            return false;
        }

        return DB::table('chemical_information')
            ->where('surveillance_id', $surveillanceId)
            ->exists();
    }

    protected function surveillanceSectionStatusesFromRequest(Request $request): array
    {
        $baselineLines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim((string) $request->input('baseline_results', ''))) ?: [], static fn ($line) => trim((string) $line) !== ''));
        $annualLines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim((string) $request->input('baseline_annual', ''))) ?: [], static fn ($line) => trim((string) $line) !== ''));
        $biologicalManualDone = $request->boolean('biological_monitoring_manual_complete');
        $biologicalDone = ! empty($baselineLines) && count($baselineLines) === count($annualLines);
        if (! $biologicalDone) {
            $bloodResultFiles = (array) $request->file('blood_result_files', []);
            $biologicalDone = count(array_filter($bloodResultFiles)) > 0;
        }
        $biologicalDone = $biologicalDone || $biologicalManualDone;

        $patientDone = $this->isPatientSectionCompleteFromValues(static fn (string $key): string => trim((string) $request->input($key, '')));
        $selectedRecommendationTypes = array_values(array_filter((array) $request->input('recommendation_types', []), static fn ($value) => trim((string) $value) !== ''));
        $needsMrpDates = in_array('Permanent Medical Removal Protection', array_map(static fn ($value) => trim((string) $value), $selectedRecommendationTypes), true);

        return [
            'patient' => $patientDone,
            'chemical' => trim((string) $request->input('company_name', '')) !== '' && trim((string) $request->input('chemicals', '')) !== '' && trim((string) $request->input('examination_type', '')) !== '' && trim((string) $request->input('examination_date', '')) !== '',
            'history' => trim((string) $request->input('breathing_difficulty', '')) !== '',
            'clinical' => trim((string) $request->input('result_clinical_findings', '')) !== '',
            'physical' => trim((string) $request->input('weight', '')) !== '' && trim((string) $request->input('height', '')) !== '' && trim((string) $request->input('BMI', '')) !== '',
            'target' => (
                trim((string) $request->input('blood_count_result', '')) !== ''
                && trim((string) $request->input('renal_function_result', '')) !== ''
                && trim((string) $request->input('liver_function_result', '')) !== ''
                && trim((string) $request->input('spirometry_FEV1', '')) !== ''
                && trim((string) $request->input('spirometry_FVC', '')) !== ''
                && trim((string) $request->input('spirometry_FEV_FVC', '')) !== ''
            ),
            'biological' => $biologicalDone,
            'respirator' => trim((string) $request->input('fitness_result', '')) !== '',
            'findings' => trim((string) $request->input('history_of_health', '')) !== '' && trim((string) $request->input('conclusion_fitness', '')) !== '',
            'recommendation' => (
                (! empty($selectedRecommendationTypes) || trim((string) $request->input('recommendation_type_other', '')) !== '')
                && (! $needsMrpDates || (
                    trim((string) $request->input('MRPdate_start', '')) !== ''
                    && trim((string) $request->input('MRPdate_end', '')) !== ''
                ))
            ),
        ];
    }

    protected function surveillanceSectionStatusesFromModels(array $context): array
    {
        $patientFormData = (array) ($context['patientFormData'] ?? []);
        $patientDone = $this->isPatientSectionCompleteFromValues(
            static fn (string $key): string => trim((string) ($patientFormData[$key] ?? ''))
        );
        $storedRecommendationLines = preg_split('/\r\n|\r|\n/', trim((string) ($context['recommendationData']->recommencation_type ?? ''))) ?: [];
        $needsMrpDates = collect($storedRecommendationLines)
            ->contains(static fn ($line) => trim((string) $line) === 'Permanent Medical Removal Protection');

        return [
            'patient' => $patientDone,
            'chemical' => ! empty($context['chemicalInfo']) && trim((string) ($context['chemicalInfo']->chemicals ?? '')) !== '' && trim((string) ($context['chemicalInfo']->examination_type ?? '')) !== '' && trim((string) ($context['chemicalInfo']->examination_date ?? '')) !== '',
            'history' => ! empty($context['historyOfHealth']),
            'clinical' => ! empty($context['clinicalFindings']) && trim((string) ($context['clinicalFindings']->result_clinical_findings ?? '')) !== '',
            'physical' => ! empty($context['physicalExam']) && ($context['physicalExam']->weight ?? null) !== null && ($context['physicalExam']->height ?? null) !== null && ($context['physicalExam']->BMI ?? null) !== null,
            'target' => ! empty($context['targetOrgan']) && (
                trim((string) ($context['targetOrgan']->blood_count_result ?? '')) !== ''
                && trim((string) ($context['targetOrgan']->renal_function_result ?? '')) !== ''
                && trim((string) ($context['targetOrgan']->liver_function_result ?? '')) !== ''
                && trim((string) ($context['targetOrgan']->spirometry_FEV1 ?? '')) !== ''
                && trim((string) ($context['targetOrgan']->spirometry_FVC ?? '')) !== ''
                && trim((string) ($context['targetOrgan']->spirometry_FEV_FVC ?? '')) !== ''
            ),
            'biological' => ! empty($context['biologicalMonitoring']) && (
                trim((string) ($context['biologicalMonitoring']->baseline_results ?? '')) !== ''
                || trim((string) ($context['biologicalMonitoring']->baseline_annual ?? '')) !== ''
                || trim((string) ($context['biologicalMonitoring']->blood_result_files ?? '')) !== ''
                || (bool) ($context['biologicalMonitoring']->manual_completed ?? false)
            ),
            'respirator' => ! empty($context['fitnessRespirator']) && trim((string) ($context['fitnessRespirator']->fitness_result ?? '')) !== '',
            'findings' => ! empty($context['msFindings']) && trim((string) ($context['msFindings']->history_of_health ?? '')) !== '',
            'recommendation' => ! empty($context['recommendationData'])
                && trim((string) ($context['recommendationData']->recommencation_type ?? '')) !== ''
                && (! $needsMrpDates || (
                    trim((string) ($context['recommendationData']->MRPdate_start ?? '')) !== ''
                    && trim((string) ($context['recommendationData']->MRPdate_end ?? '')) !== ''
                )),
        ];
    }

    protected function surveillanceOtherTargetTests(int $surveillanceId): array
    {
        if ($surveillanceId <= 0) {
            return [];
        }

        if (Schema::hasTable('target_organ_other_tests')) {
            $rows = DB::table('target_organ_other_tests')
                ->where('surveillance_id', $surveillanceId)
                ->orderBy('sort_order')
                ->orderBy('other_target_test_id')
                ->get(['test_name', 'result', 'comments']);

            if ($rows->isNotEmpty()) {
                return $rows->map(static fn ($row) => [
                    'name' => trim((string) ($row->test_name ?? '')),
                    'result' => trim((string) ($row->result ?? '')),
                    'comments' => trim((string) ($row->comments ?? '')),
                ])->filter(static fn ($row) => $row['name'] !== '' || $row['result'] !== '' || $row['comments'] !== '')
                    ->values()
                    ->all();
            }
        }

        if (! Schema::hasTable('target_organ') || ! Schema::hasColumn('target_organ', 'other_tests')) {
            return [];
        }

        $targetOrgan = DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first(['other_tests']);
        if (empty($targetOrgan?->other_tests)) {
            return [];
        }

        $decodedOtherTargetTests = json_decode((string) $targetOrgan->other_tests, true);
        if (! is_array($decodedOtherTargetTests)) {
            return [];
        }

        return array_values(array_filter(array_map(static function ($row): array {
            return [
                'name' => trim((string) ($row['name'] ?? '')),
                'result' => trim((string) ($row['result'] ?? '')),
                'comments' => trim((string) ($row['comments'] ?? '')),
            ];
        }, $decodedOtherTargetTests), static fn ($row) => $row['name'] !== '' || $row['result'] !== '' || $row['comments'] !== ''));
    }

    protected function syncTargetOrganOtherTests(?int $targetId, int $surveillanceId, int $employeeId, array $otherTargetTests): void
    {
        if ($targetId === null || ! Schema::hasTable('target_organ_other_tests')) {
            return;
        }

        DB::table('target_organ_other_tests')
            ->where('target_id', $targetId)
            ->delete();

        if ($otherTargetTests === []) {
            return;
        }

        $payload = [];
        foreach (array_values($otherTargetTests) as $index => $otherTargetTest) {
            $payload[] = [
                'target_id' => $targetId,
                'surveillance_id' => $surveillanceId,
                'employee_id' => $employeeId,
                'test_name' => trim((string) ($otherTargetTest['name'] ?? '')) ?: null,
                'result' => trim((string) ($otherTargetTest['result'] ?? '')) ?: null,
                'comments' => trim((string) ($otherTargetTest['comments'] ?? '')) ?: null,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('target_organ_other_tests')->insert($payload);
    }

    protected function isPatientSectionCompleteFromValues(callable $valueResolver): bool
    {
        $requiredFields = [
            'diagnosed_history_status',
            'medication_history_status',
            'admitted_history_status',
            'family_history_status',
            'current_job_title',
            'current_employment_duration',
            'current_chemical_exposure_duration',
            'smoking_history',
            'vaping_history',
            'handling_of_chemical',
            'sign_symptoms',
            'chemical_poisoning',
            'proper_PPE',
            'PPE_usage',
        ];

        foreach ($requiredFields as $requiredField) {
            if ($valueResolver($requiredField) === '') {
                return false;
            }
        }

        $smokingHistory = $valueResolver('smoking_history');
        if (in_array($smokingHistory, ['Current', 'Ex-smoker'], true)) {
            if ($valueResolver('years_of_smoking') === '' || $valueResolver('no_of_cigarettes') === '') {
                return false;
            }
        }

        if ($valueResolver('vaping_history') === 'Yes' && $valueResolver('years_of_vaping') === '') {
            return false;
        }

        return true;
    }

    protected function surveillanceRelatedTables(): array
    {
        return [
            'declaration',
            'summary_report',
            'removal_report',
            'fitness_report',
            'recommendation',
            'ms_findings',
            'fitness_respirator',
            'biological_monitoring',
            'target_organ_other_tests',
            'target_organ',
            'physical_examination',
            'clinical_findings',
            'history_of_health',
            'chemical_information',
        ];
    }

    protected function reportEmailStatuses(): array
    {
        if (! Schema::hasTable('report_email_logs')) {
            return [];
        }

        return DB::table('report_email_logs')
            ->where('module', 'surveillance')
            ->whereNotNull('sent_at')
            ->get([
                'report_key',
                'declaration_id',
                'employee_id',
                'company_id',
                'surveillance_id',
                'sent_at',
            ])
            ->mapWithKeys(static function ($row): array {
                $key = implode('|', [
                    'surveillance',
                    (string) ($row->report_key ?? ''),
                    (string) ($row->declaration_id ?? ''),
                    (string) ($row->employee_id ?? ''),
                    (string) ($row->company_id ?? ''),
                    (string) ($row->surveillance_id ?? ''),
                ]);

                return [$key => (string) ($row->sent_at ?? '')];
            })
            ->all();
    }

    protected function buildSurveillanceReportMailPayload(
        string $reportKey,
        int $declarationId,
        int $employeeId,
        int $companyId,
        int $surveillanceId,
        Request $request,
        User $user
    ): array {
        $baseViewData = $this->buildViewData($request, $user);
        $legacyContext = app(\App\Support\LegacyClinicContext::class);

        $declaration = null;
        if ($declarationId > 0 && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
        }
        if (! $declaration && Schema::hasTable('declaration')) {
            $declaration = DB::table('declaration')
                ->when($employeeId > 0, fn ($query) => $query->where('employee_id', $employeeId))
                ->when($companyId > 0, fn ($query) => $query->where('company_id', $companyId))
                ->when($surveillanceId > 0, fn ($query) => $query->where('surveillance_id', $surveillanceId))
                ->orderByDesc('declaration_id')
                ->first();
        }

        $employeeId = (int) ($declaration->employee_id ?? $employeeId);
        $companyId = (int) ($declaration->company_id ?? $companyId);
        $surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
        $declarationId = (int) ($declaration->declaration_id ?? $declarationId);

        $employee = $employeeId > 0 && Schema::hasTable('employee')
            ? DB::table('employee')->where('employee_id', $employeeId)->first()
            : null;
        $company = $companyId > 0 && Schema::hasTable('company')
            ? DB::table('company')->where('company_id', $companyId)->first()
            : null;
        $chemicalInfo = $surveillanceId > 0 && Schema::hasTable('chemical_information')
            ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
            : null;

        $workerName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
        $identityNo = trim((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '')));
        $examDateSource = (string) ($chemicalInfo->examination_date ?? $declaration->doctor_date ?? $declaration->employee_date ?? '');
        $examDate = $examDateSource !== '' && strtotime($examDateSource) ? date('d/m/Y', strtotime($examDateSource)) : '-';
        $attachmentPatientName = trim(preg_replace('/[\\\\\\/:"*?<>|]+/', '', $workerName));
        $attachmentPatientName = preg_replace('/\s+/', ' ', $attachmentPatientName ?? '');
        $attachmentPatientName = trim((string) $attachmentPatientName);
        $attachmentPatientName = $attachmentPatientName !== '' ? $attachmentPatientName : 'patient';

        $pdfContent = match (strtolower($reportKey)) {
            'all' => $this->renderCombinedUsechhAllPdfContent($baseViewData, $legacyContext, $request, $declaration),
            'usechh 4' => $this->renderPdfFromView(
                'report.surveillance_summaryReport',
                $baseViewData,
                [
                    'declaration_id' => $declarationId,
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'surveillance_id' => $surveillanceId,
                ]
            ),
            'usechh 5i' => $this->renderPdfFromView(
                'report.surveillance_removalReport',
                $baseViewData,
                [
                    'declaration_id' => $declarationId,
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'surveillance_id' => $surveillanceId,
                ]
            ),
            'usechh 5ii' => $this->renderPdfFromView(
                'report.suveillance_abnormalReport',
                $baseViewData,
                [
                    'declaration_id' => $declarationId,
                    'employee_id' => $employeeId,
                    'company_id' => $companyId,
                    'surveillance_id' => $surveillanceId,
                ],
                'landscape'
            ),
            default => $this->renderCombinedUsechhAllPdfContent($baseViewData, $legacyContext, $request, $declaration),
        };

        $attachmentSuffix = match (strtolower($reportKey)) {
            'all' => 'Combined',
            'usechh 4' => 'USECHH4',
            'usechh 5i' => 'USECHH5I',
            'usechh 5ii' => 'USECHH5II',
            default => strtoupper(str_replace(' ', '', $reportKey)),
        };

        return [
            'report_key' => $reportKey,
            'declaration_id' => $declarationId,
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'surveillance_id' => $surveillanceId,
            'recipient_email' => trim((string) ($employee->employee_email ?? '')),
            'attachment_name' => strtolower($reportKey) === 'all'
                ? 'Medical Surveillance Report_'.$attachmentPatientName.'.pdf'
                : 'Medical Surveillance Report_'.$attachmentSuffix.'_'.$attachmentPatientName.'.pdf',
            'pdf_content' => $pdfContent,
            'mail_view_data' => [
                'patient_name' => $workerName !== '' ? $workerName : '-',
                'identity_no' => $identityNo !== '' ? $identityNo : '-',
                'exam_date' => $examDate,
                'company_name' => (string) ($company->company_name ?? '-'),
            ],
        ];
    }

    protected function renderCombinedUsechhAllPdfContent(
        array $baseViewData,
        \App\Support\LegacyClinicContext $legacyContext,
        Request $request,
        ?object $declaration
    ): string {
        $queryParams = [
            'declaration_id' => (int) ($declaration->declaration_id ?? 0),
            'employee_id' => (int) ($declaration->employee_id ?? 0),
            'company_id' => (int) ($declaration->company_id ?? 0),
            'surveillance_id' => (int) ($declaration->surveillance_id ?? 0),
        ];

        return $this->withReportQueryContext($request, $queryParams, function () use ($baseViewData, $legacyContext, $request, $declaration): string {
            $combinedSections = $this->buildCombinedUsechhAllSections($baseViewData, $legacyContext, $request, $declaration);
            $normalizedSections = $this->normalizeCombinedSectionsForPdf($combinedSections);

            return Pdf::loadView('report.PDF_USECHH_ALL_PDF', array_merge($baseViewData, [
                'combinedSections' => $normalizedSections,
            ]))
                ->setPaper('a4', 'portrait')
                ->output();
        });
    }

    protected function renderPdfFromView(string $viewName, array $viewData, array $queryParams = [], string $orientation = 'portrait'): string
    {
        return $this->withReportQueryContext(request(), $queryParams, function () use ($viewName, $viewData, $orientation): string {
            return Pdf::loadView($viewName, $viewData)
                ->setPaper('a4', $orientation)
                ->output();
        });
    }

    protected function withReportQueryContext(Request $request, array $queryParams, callable $callback)
    {
        $queryBag = $request->query;
        $original = $queryBag->all();
        $queryBag->add(array_filter($queryParams, static fn ($value) => (int) $value > 0));

        try {
            return $callback();
        } finally {
            $queryBag->replace($original);
        }
    }

    protected function normalizeCombinedSectionsForPdf(array $sections): array
    {
        return array_map(function (array $section): array {
            $selector = trim((string) ($section['selector'] ?? ''));
            $html = (string) ($section['html'] ?? '');

            libxml_use_internal_errors(true);
            $document = new DOMDocument('1.0', 'UTF-8');
            $document->loadHTML('<?xml encoding="utf-8" ?>' . $html);
            $xpath = new DOMXPath($document);

            $styles = '';
            foreach ($xpath->query('//style') ?: [] as $styleNode) {
                $styles .= $document->saveHTML($styleNode);
            }

            $contentHtml = '';
            if ($selector !== '') {
                $selectorParts = explode('.', ltrim($selector, '.'));
                $className = trim((string) ($selectorParts[0] ?? ''));
                if ($className !== '') {
                    $nodes = $xpath->query(sprintf(
                        "//*[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
                        $className
                    ));
                    if ($nodes && $nodes->length > 0) {
                        $contentHtml = $document->saveHTML($nodes->item(0)) ?: '';
                    }
                }
            }

            if ($contentHtml === '') {
                $bodyNodes = $xpath->query('//body');
                if ($bodyNodes && $bodyNodes->length > 0) {
                    foreach ($bodyNodes->item(0)->childNodes as $childNode) {
                        $contentHtml .= $document->saveHTML($childNode);
                    }
                }
            }

            libxml_clear_errors();

            return [
                'title' => $section['title'] ?? '',
                'styles' => $styles,
                'content_html' => $contentHtml,
                'page_class' => $section['page_class'] ?? '',
            ];
        }, $sections);
    }
}
