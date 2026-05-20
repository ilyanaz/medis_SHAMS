<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
                ->withErrors(['employee' => 'The selected employee could not be found.']);
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

        $context = [
            'chemicalInfo' => $surveillanceId > 0 && Schema::hasTable('chemical_information') ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first() : null,
            'historyOfHealth' => $surveillanceId > 0 && Schema::hasTable('history_of_health') ? DB::table('history_of_health')->where('surveillance_id', $surveillanceId)->first() : null,
            'clinicalFindings' => $surveillanceId > 0 && Schema::hasTable('clinical_findings') ? DB::table('clinical_findings')->where('surveillance_id', $surveillanceId)->first() : null,
            'physicalExam' => $surveillanceId > 0 && Schema::hasTable('physical_examination') ? DB::table('physical_examination')->where('surveillance_id', $surveillanceId)->first() : null,
            'targetOrgan' => $surveillanceId > 0 && Schema::hasTable('target_organ') ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first() : null,
            'biologicalMonitoring' => $surveillanceId > 0 && Schema::hasTable('biological_monitoring') ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first() : null,
            'fitnessRespirator' => $surveillanceId > 0 && Schema::hasTable('fitness_respirator') ? DB::table('fitness_respirator')->where('surveillance_id', $surveillanceId)->first() : null,
            'msFindings' => $surveillanceId > 0 && Schema::hasTable('ms_findings') ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first() : null,
            'recommendationData' => $surveillanceId > 0 && Schema::hasTable('recommendation') ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first() : null,
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
                'pageMode' => $this->isRecommendationFinalized($context['recommendationData'] ?? null) ? 'view' : ($surveillanceId > 0 ? 'edit' : 'create'),
                'readOnly' => $this->isRecommendationFinalized($context['recommendationData'] ?? null),
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
            return redirect()->back()->withErrors(['employee' => 'The selected employee could not be found.'])->withInput();
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

        $params = array_filter([
            'company_id' => (int) $validated['company_id'],
            'employee_id' => (int) $validated['employee_id'],
            'declaration_id' => $declarationId,
            'surveillance_id' => $surveillanceId > 0 ? $surveillanceId : null,
        ], static fn ($value) => $value !== null && $value !== '');

        return redirect()
            ->route('surveillance.examination', $params)
            ->with('status', 'Declaration saved successfully. You can continue with the examination.');
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
            $file = $request->file('header_upload');
            $filename = 'clinic-header-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/clinic-headers');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $headerPath = 'uploads/clinic-headers/' . $filename;
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
            $file = $request->file('header_upload');
            $filename = 'clinic-header-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/clinic-headers');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $headerPath = 'uploads/clinic-headers/' . $filename;
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
            $file = $uploadedSignatureFile;
            $filename = 'doctor-sign-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/doctor-signatures');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $signaturePath = 'uploads/doctor-signatures/' . $filename;
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
            $file = $request->file('doctor_picture');
            $filename = 'doctor-picture-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/doctor-pictures');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $picturePath = 'uploads/doctor-pictures/' . $filename;
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
            $file = $uploadedSignatureFile;
            $filename = 'doctor-sign-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/doctor-signatures');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $signaturePath = 'uploads/doctor-signatures/' . $filename;
        }

        if ($signaturePath === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'doctor_sign_upload' => 'Please provide the doctor signature by upload or eSign.',
            ]);
        }

        $picturePath = (string) ($record->doctor_picture ?? '');
        if ($request->hasFile('doctor_picture')) {
            $this->deletePublicFile($picturePath);
            $file = $request->file('doctor_picture');
            $filename = 'doctor-picture-' . Str::uuid() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/doctor-pictures');
            if (! is_dir($destination)) {
                mkdir($destination, 0777, true);
            }
            $file->move($destination, $filename);
            $picturePath = 'uploads/doctor-pictures/' . $filename;
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
            'total_workers' => ['nullable', 'integer', 'min:0'],
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
            'total_workers' => isset($validated['total_workers']) ? (int) $validated['total_workers'] : 0,
        ];

        if (Schema::hasColumn('company', 'clinic_id')) {
            $payload['clinic_id'] = $clinicId;
        }

        if (Schema::hasColumn('company', 'company_module')) {
            $payload['company_module'] = $validated['company_module'];
        }

        DB::table('company')->insert($payload);

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
            'total_workers' => ['nullable', 'integer', 'min:0'],
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
            'total_workers' => isset($validated['total_workers']) ? (int) $validated['total_workers'] : 0,
        ];

        if (Schema::hasColumn('company', 'company_module')) {
            $payload['company_module'] = $validated['company_module'];
        }

        DB::table('company')
            ->where('company_id', $record->company_id)
            ->update($payload);

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
                'clinic' => 'Select a clinic first before adding an employee.',
            ]);
        }

        $validated = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'employee_firstName' => ['required', 'string', 'max:100'],
            'employee_lastName' => ['required', 'string', 'max:100'],
            'employee_NRIC' => ['nullable', 'string', 'max:20', 'required_without:employee_passportNo'],
            'employee_passportNo' => ['nullable', 'string', 'max:30', 'required_without:employee_NRIC'],
            'employee_DOB' => ['nullable', 'date'],
            'employee_gender' => ['nullable', 'in:Male,Female'],
            'employee_address' => ['nullable', 'string', 'max:255'],
            'employee_postcode' => ['nullable', 'string', 'max:10'],
            'employee_district' => ['nullable', 'string', 'max:100'],
            'employee_state' => ['nullable', 'string', 'max:100'],
            'employee_phone_code' => ['required', 'string', 'max:10'],
            'employee_telephone' => ['required', 'string', 'max:20'],
            'employee_email' => ['nullable', 'email', 'max:150'],
            'employee_ethnicity' => ['nullable', 'string', 'max:50'],
            'employee_citizenship' => ['nullable', 'string', 'max:50'],
            'employee_martialStatus' => ['nullable', 'string', 'max:50'],
            'no_of_children' => ['nullable', 'integer', 'min:0'],
            'years_married' => ['nullable', 'integer', 'min:0'],
            'diagnosed_history' => ['nullable', 'string'],
            'medication_history' => ['nullable', 'string'],
            'admitted_history' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'others_history' => ['nullable', 'string'],
            'current_job_title' => ['nullable', 'string', 'max:150'],
            'current_company_name' => ['nullable', 'string', 'max:150'],
            'current_employment_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_incidents' => ['nullable', 'string'],
            'occup_job_title' => ['array'],
            'occup_job_title.*' => ['nullable', 'string', 'max:150'],
            'occup_company_name' => ['array'],
            'occup_company_name.*' => ['nullable', 'string', 'max:150'],
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

        if (Schema::hasColumn('employee', 'clinic_id')) {
            $employeePayload['clinic_id'] = $clinicId;
        }
        if ($selectedCompany && Schema::hasColumn('employee', 'company_id')) {
            $employeePayload['company_id'] = (int) $selectedCompany->company_id;
        }

        $employeeId = DB::table('employee')->insertGetId($employeePayload);

        DB::table('medical_history')->insert([
            'diagnosed_history' => trim((string) ($validated['diagnosed_history'] ?? '')) ?: null,
            'medication_history' => trim((string) ($validated['medication_history'] ?? '')) ?: null,
            'admitted_history' => trim((string) ($validated['admitted_history'] ?? '')) ?: null,
            'family_history' => trim((string) ($validated['family_history'] ?? '')) ?: null,
            'others_history' => trim((string) ($validated['others_history'] ?? '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => null,
        ]);

        DB::table('occupational_history')->insert([
            'job_title' => trim((string) ($validated['current_job_title'] ?? '')) ?: null,
            'company_name' => trim((string) ($selectedCompany->company_name ?? ($validated['current_company_name'] ?? ''))) ?: null,
            'employment_duration' => trim((string) ($validated['current_employment_duration'] ?? '')) ?: null,
            'chemical_exposure_duration' => trim((string) ($validated['current_chemical_exposure_duration'] ?? '')) ?: null,
            'chemical_exposure_incidents' => trim((string) ($validated['current_chemical_exposure_incidents'] ?? '')) ?: null,
            'employee_id' => $employeeId,
            'surveillance_id' => null,
        ]);

        $jobTitles = $validated['occup_job_title'] ?? [];
        $companyNames = $validated['occup_company_name'] ?? [];
        $employmentDurations = $validated['employment_duration'] ?? [];
        $exposureDurations = $validated['chemical_exposure_duration'] ?? [];
        $exposureIncidents = $validated['chemical_exposure_incidents'] ?? [];

        $rowCount = max(
            count($jobTitles),
            count($companyNames),
            count($employmentDurations),
            count($exposureDurations),
            count($exposureIncidents)
        );

        for ($index = 0; $index < $rowCount; $index++) {
            $payload = [
                'job_title' => trim((string) ($jobTitles[$index] ?? '')),
                'company_name' => trim((string) ($companyNames[$index] ?? '')),
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
                ? 'Employee saved successfully for the selected company in the active clinic.'
                : 'Employee saved successfully for the active clinic.');
    }

    public function surveillanceRecordView(Request $request, int $declaration): View|RedirectResponse
    {
        return $this->renderSurveillanceRecordPage($request, $declaration, true);
    }

    public function surveillanceRecordEdit(Request $request, int $declaration): View|RedirectResponse
    {
        return $this->renderSurveillanceRecordPage($request, $declaration, false);
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
        return ! empty($recommendationData) && ! empty($recommendationData->is_final);
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
                ['error' => 'Employee and company are required to save the surveillance examination.']
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

        $existingDeclaration = $declarationId > 0
            ? DB::table('declaration')->where('declaration_id', $declarationId)->first()
            : DB::table('declaration')->where('surveillance_id', $surveillanceId)->orderByDesc('declaration_id')->first();

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

        $targetColumns = [
            'blood_count', 'blood_comments', 'renal_function', 'renal_comments', 'liver_function', 'liver_comments',
            'chest_xray', 'chest_comments', 'spirometry_FEV1', 'spirometry_FVC', 'spirometry_FEV_FVC', 'spirometry_comments',
        ];
        $targetPayload = ['employee_id' => $employeeId, 'surveillance_id' => $surveillanceId];
        foreach ($targetColumns as $column) {
            $value = $request->input($column);
            $targetPayload[$column] = is_string($value) ? (trim($value) !== '' ? trim($value) : null) : $value;
        }
        $this->upsertSurveillanceChildRow('target_organ', 'target_id', $surveillanceId, $employeeId, $targetPayload);

        $baselineResults = trim((string) $request->input('baseline_results', ''));
        $baselineAnnual = trim((string) $request->input('baseline_annual', ''));
        $existingBloodResultFiles = [];
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

        $newBloodResultFiles = [];
        foreach ((array) $request->file('blood_result_files', []) as $uploadedFile) {
            if (! $uploadedFile || ! $uploadedFile->isValid()) {
                continue;
            }

            $newBloodResultFiles[] = $uploadedFile->store('surveillance/blood-results', 'public');
        }

        $mergedBloodResultFiles = array_values(array_merge($existingBloodResultFiles, $newBloodResultFiles));
        $biologicalPayload = [
            'biological_exposure' => ($baselineResults !== '' || $baselineAnnual !== '' || ! empty($mergedBloodResultFiles)) ? 'Yes' : null,
            'baseline_results' => $baselineResults !== '' ? $baselineResults : null,
            'baseline_annual' => $baselineAnnual !== '' ? $baselineAnnual : null,
            'employee_id' => $employeeId,
            'surveillance_id' => $surveillanceId,
        ];
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
        $existingRecommendation = $surveillanceId > 0 && Schema::hasTable('recommendation')
            ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first()
            : null;
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
                'readOnly' => $isFinalSave || $this->isRecommendationFinalized($existingRecommendation),
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
        ]);

        if (! Schema::hasTable('fitness_report')) {
            return redirect()->back()->with('status', 'Fitness report table is not available.');
        }

        $surveillanceId = (int) $validated['surveillance_id'];
        $remarks = trim((string) ($validated['remarks'] ?? ''));

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
                ]);
        } else {
            DB::table('fitness_report')->insert([
                'result' => 'Pending review',
                'remarks' => $remarks,
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
            ->with('status', 'USECHH 3 remarks saved successfully.');
    }

    public function saveSurveillanceSummaryReport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'surveillance_id' => ['required', 'integer', 'min:1'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'company_id' => ['nullable', 'integer', 'min:1'],
            'declaration_id' => ['nullable', 'integer', 'min:1'],
            'totalNo_workplace' => ['nullable', 'integer', 'min:0'],
            'name_of_workUnit' => ['nullable', 'string'],
            'no_exposedWorkers' => ['nullable', 'integer', 'min:0'],
            'totalNo_examined' => ['nullable', 'integer', 'min:0'],
            'CHRA_reportNo' => ['nullable', 'string'],
            'indication_CHRAreport' => ['nullable', 'string'],
            'name_of_laboratoy' => ['nullable', 'string'],
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

        $payload = [
            'employee_id' => $employeeId ?? ($record->employee_id ?? null),
            'company_id' => $companyId ?? ($record->company_id ?? null),
            'surveillance_id' => $surveillanceId,
            'totalNo_workplace' => $validated['totalNo_workplace'] ?? ($record->totalNo_workplace ?? null),
            'name_of_workUnit' => trim((string) ($validated['name_of_workUnit'] ?? ($record->name_of_workUnit ?? ''))),
            'no_exposedWorkers' => $validated['no_exposedWorkers'] ?? ($record->no_exposedWorkers ?? null),
            'totalNo_examined' => $validated['totalNo_examined'] ?? ($record->totalNo_examined ?? null),
            'CHRA_reportNo' => trim((string) ($validated['CHRA_reportNo'] ?? ($record->CHRA_reportNo ?? ''))),
            'indication_CHRAreport' => trim((string) ($validated['indication_CHRAreport'] ?? ($record->indication_CHRAreport ?? ''))),
            'name_of_laboratoy' => trim((string) ($validated['name_of_laboratoy'] ?? ($record->name_of_laboratoy ?? ''))),
            'recommendation' => trim((string) ($validated['recommendation'] ?? ($record->recommendation ?? ''))),
            'decision' => trim((string) ($validated['decision'] ?? ($record->decision ?? ''))),
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

        $params = array_filter([
            'declaration_id' => $validated['declaration_id'] ?? null,
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'surveillance_id' => $surveillanceId,
        ], static fn ($value) => $value !== null && $value !== '');

        return redirect()
            ->route('surveillance.report.summary', $params)
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
        $fitnessRecord = Schema::hasTable('fitness_report')
            ? DB::table('fitness_report')->where('surveillance_id', $surveillanceId)->first()
            : null;

        $payload = [
            'employee_id' => $employeeId ?? ($record->employee_id ?? null),
            'company_id' => $companyId ?? ($record->company_id ?? null),
            'surveillance_id' => $surveillanceId,
            'removal_type' => trim((string) ($validated['removal_type'] ?? ($record->removal_type ?? ''))),
            'reasons_recommendations' => trim((string) ($record->reasons_recommendations ?? '')),
            'doctor_id' => $record->doctor_id ?? ($declaration->doctor_id ?? null),
            'fitnessReport_id' => $record->fitnessReport_id ?? ($fitnessRecord->fitnessReport_id ?? null),
        ];

        if ($record) {
            DB::table('removal_report')
                ->where('removalReport_id', $record->removalReport_id)
                ->update($payload);
        } else {
            DB::table('removal_report')->insert($payload);
        }

        $params = array_filter([
            'declaration_id' => $validated['declaration_id'] ?? null,
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'surveillance_id' => $surveillanceId,
        ], static fn ($value) => $value !== null && $value !== '');

        return redirect()
            ->route('surveillance.report.removal', $params)
            ->with('status', 'USECHH 5i details saved successfully.');
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
            $query->where('clinic_id', $clinicId);
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
            $query->where('clinic_id', $clinicId);
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

        $occupationalRows = collect();
        if (Schema::hasTable('occupational_history')) {
            $occupationalQuery = DB::table('occupational_history')
                ->where('employee_id', $employeeId);

            if (Schema::hasColumn('occupational_history', 'surveillance_id')) {
                $occupationalQuery->whereNull('surveillance_id');
            }

            if (Schema::hasColumn('occupational_history', 'occupHistory_id')) {
                $occupationalQuery->orderBy('occupHistory_id');
            }

            $occupationalRows = $occupationalQuery->get();
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
            $medicalHistoryQuery = DB::table('medical_history')->where('employee_id', $employeeId);
            if (Schema::hasColumn('medical_history', 'surveillance_id')) {
                $medicalHistoryQuery->whereNull('surveillance_id');
            }
            if (Schema::hasColumn('medical_history', 'medHistory_id')) {
                $medicalHistoryQuery->orderByDesc('medHistory_id');
            }
            $medicalHistory = $medicalHistoryQuery->first();
        }

        $personalHistory = null;
        if (Schema::hasTable('personal_social_history')) {
            $personalHistoryQuery = DB::table('personal_social_history')->where('employee_id', $employeeId);
            if (Schema::hasColumn('personal_social_history', 'surveillance_id')) {
                $personalHistoryQuery->whereNull('surveillance_id');
            }
            if (Schema::hasColumn('personal_social_history', 'perSocHistory_id')) {
                $personalHistoryQuery->orderByDesc('perSocHistory_id');
            }
            $personalHistory = $personalHistoryQuery->first();
        }

        $trainingHistory = null;
        if (Schema::hasTable('training_history')) {
            $trainingHistoryQuery = DB::table('training_history')->where('employee_id', $employeeId);
            if (Schema::hasColumn('training_history', 'surveillance_id')) {
                $trainingHistoryQuery->whereNull('surveillance_id');
            }
            if (Schema::hasColumn('training_history', 'trainingHistory_id')) {
                $trainingHistoryQuery->orderByDesc('trainingHistory_id');
            }
            $trainingHistory = $trainingHistoryQuery->first();
        }

        return [
            'selectedCompany' => $selectedCompany,
            'patientRecord' => $patientRecord,
            'patientFormData' => $this->surveillancePatientFormDefaults($patientRecord, $selectedCompany, $medicalHistory, $currentOccupational, $pastOccupationalRows, $personalHistory, $trainingHistory),
            'returnTo' => $this->surveillancePatientReturnUrl($request, (int) ($selectedCompany->company_id ?? 0)),
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
            'employee_ethnicity_other' => '',
            'employee_citizenship_other' => '',
            'employee_martial_other' => '',
            'no_of_children' => (string) ($patient->no_of_children ?? ''),
            'years_married' => (string) ($patient->years_married ?? ''),
            'diagnosed_history' => (string) ($medicalHistory->diagnosed_history ?? ''),
            'medication_history' => (string) ($medicalHistory->medication_history ?? ''),
            'admitted_history' => (string) ($medicalHistory->admitted_history ?? ''),
            'family_history' => (string) ($medicalHistory->family_history ?? ''),
            'others_history' => (string) ($medicalHistory->others_history ?? ''),
            'current_job_title' => (string) ($currentOccupational->job_title ?? ''),
            'current_company_name' => (string) ($selectedCompany->company_name ?? ($currentOccupational->company_name ?? '')),
            'current_employment_duration' => (string) ($currentOccupational->employment_duration ?? ''),
            'current_chemical_exposure_duration' => (string) ($currentOccupational->chemical_exposure_duration ?? ''),
            'current_chemical_exposure_incidents' => (string) ($currentOccupational->chemical_exposure_incidents ?? ''),
            'occup_job_title' => $pastRows->pluck('job_title')->map(static fn ($value) => (string) $value)->all(),
            'occup_company_name' => $pastRows->pluck('company_name')->map(static fn ($value) => (string) $value)->all(),
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
            'employee_NRIC' => ['nullable', 'string', 'max:20', 'required_without:employee_passportNo'],
            'employee_passportNo' => ['nullable', 'string', 'max:30', 'required_without:employee_NRIC'],
            'employee_DOB' => ['nullable', 'date'],
            'employee_gender' => ['nullable', 'in:Male,Female'],
            'employee_address' => ['nullable', 'string', 'max:255'],
            'employee_postcode' => ['nullable', 'string', 'max:10'],
            'employee_district' => ['nullable', 'string', 'max:100'],
            'employee_state' => ['nullable', 'string', 'max:100'],
            'employee_phone_code' => ['required', 'string', 'max:10'],
            'employee_telephone' => ['required', 'string', 'max:20'],
            'employee_email' => ['nullable', 'email', 'max:150'],
            'employee_ethnicity' => ['nullable', 'string', 'max:50'],
            'employee_citizenship' => ['nullable', 'string', 'max:50'],
            'employee_martialStatus' => ['nullable', 'string', 'max:50'],
            'no_of_children' => ['nullable', 'integer', 'min:0'],
            'years_married' => ['nullable', 'integer', 'min:0'],
            'diagnosed_history' => ['nullable', 'string'],
            'medication_history' => ['nullable', 'string'],
            'admitted_history' => ['nullable', 'string'],
            'family_history' => ['nullable', 'string'],
            'others_history' => ['nullable', 'string'],
            'current_job_title' => ['nullable', 'string', 'max:150'],
            'current_company_name' => ['nullable', 'string', 'max:150'],
            'current_employment_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_duration' => ['nullable', 'string', 'max:100'],
            'current_chemical_exposure_incidents' => ['nullable', 'string'],
            'occup_job_title' => ['array'],
            'occup_job_title.*' => ['nullable', 'string', 'max:150'],
            'occup_company_name' => ['array'],
            'occup_company_name.*' => ['nullable', 'string', 'max:150'],
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

        if ($clinicId > 0 && Schema::hasColumn('employee', 'clinic_id')) {
            $payload['clinic_id'] = $clinicId;
        }

        if (Schema::hasColumn('employee', 'company_id')) {
            $payload['company_id'] = $selectedCompany ? (int) $selectedCompany->company_id : null;
        }

        return $payload;
    }

    protected function syncSurveillancePatientSupportingData(int $employeeId, array $validated, ?object $selectedCompany = null): void
    {
        if (Schema::hasTable('medical_history')) {
            DB::table('medical_history')
                ->where('employee_id', $employeeId)
                ->when(Schema::hasColumn('medical_history', 'surveillance_id'), fn ($query) => $query->whereNull('surveillance_id'))
                ->delete();

            DB::table('medical_history')->insert([
                'diagnosed_history' => $this->nullableTrim($validated['diagnosed_history'] ?? null),
                'medication_history' => $this->nullableTrim($validated['medication_history'] ?? null),
                'admitted_history' => $this->nullableTrim($validated['admitted_history'] ?? null),
                'family_history' => $this->nullableTrim($validated['family_history'] ?? null),
                'others_history' => $this->nullableTrim($validated['others_history'] ?? null),
                'employee_id' => $employeeId,
                'surveillance_id' => null,
            ]);
        }

        if (Schema::hasTable('occupational_history')) {
            DB::table('occupational_history')
                ->where('employee_id', $employeeId)
                ->when(Schema::hasColumn('occupational_history', 'surveillance_id'), fn ($query) => $query->whereNull('surveillance_id'))
                ->delete();

            $currentPayload = [
                'job_title' => $this->nullableTrim($validated['current_job_title'] ?? null),
                'company_name' => $this->nullableTrim($selectedCompany->company_name ?? ($validated['current_company_name'] ?? null)),
                'employment_duration' => $this->nullableTrim($validated['current_employment_duration'] ?? null),
                'chemical_exposure_duration' => $this->nullableTrim($validated['current_chemical_exposure_duration'] ?? null),
                'chemical_exposure_incidents' => $this->nullableTrim($validated['current_chemical_exposure_incidents'] ?? null),
                'employee_id' => $employeeId,
                'surveillance_id' => null,
            ];

            if (implode('', array_map(static fn ($value) => (string) ($value ?? ''), $currentPayload)) !== '') {
                DB::table('occupational_history')->insert($currentPayload);
            }

            $jobTitles = $validated['occup_job_title'] ?? [];
            $companyNames = $validated['occup_company_name'] ?? [];
            $employmentDurations = $validated['employment_duration'] ?? [];
            $exposureDurations = $validated['chemical_exposure_duration'] ?? [];
            $exposureIncidents = $validated['chemical_exposure_incidents'] ?? [];

            $rowCount = max(count($jobTitles), count($companyNames), count($employmentDurations), count($exposureDurations), count($exposureIncidents));

            for ($index = 0; $index < $rowCount; $index++) {
                $payload = [
                    'job_title' => $this->nullableTrim($jobTitles[$index] ?? null),
                    'company_name' => $this->nullableTrim($companyNames[$index] ?? null),
                    'employment_duration' => $this->nullableTrim($employmentDurations[$index] ?? null),
                    'chemical_exposure_duration' => $this->nullableTrim($exposureDurations[$index] ?? null),
                    'chemical_exposure_incidents' => $this->nullableTrim($exposureIncidents[$index] ?? null),
                ];

                if (implode('', array_map(static fn ($value) => (string) ($value ?? ''), $payload)) === '') {
                    continue;
                }

                DB::table('occupational_history')->insert($payload + [
                    'employee_id' => $employeeId,
                    'surveillance_id' => null,
                ]);
            }
        }

        if (Schema::hasTable('personal_social_history')) {
            DB::table('personal_social_history')
                ->where('employee_id', $employeeId)
                ->when(Schema::hasColumn('personal_social_history', 'surveillance_id'), fn ($query) => $query->whereNull('surveillance_id'))
                ->delete();

            DB::table('personal_social_history')->insert([
                'smoking_history' => $this->nullableTrim($validated['smoking_history'] ?? null),
                'years_of_smoking' => $validated['years_of_smoking'] ?? null,
                'no_of_cigarettes' => $validated['no_of_cigarettes'] ?? null,
                'vaping_history' => $this->nullableTrim($validated['vaping_history'] ?? null),
                'years_of_vaping' => $validated['years_of_vaping'] ?? null,
                'hobby' => $this->nullableTrim($validated['hobby'] ?? null),
                'employee_id' => $employeeId,
                'surveillance_id' => null,
            ]);
        }

        if (Schema::hasTable('training_history')) {
            DB::table('training_history')
                ->where('employee_id', $employeeId)
                ->when(Schema::hasColumn('training_history', 'surveillance_id'), fn ($query) => $query->whereNull('surveillance_id'))
                ->delete();

            DB::table('training_history')->insert([
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
                'surveillance_id' => null,
            ]);
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
        ];
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
        $destination = public_path($directory);

        if (! is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        file_put_contents($destination . DIRECTORY_SEPARATOR . $filename, $binary);

        return trim($directory, '/\\') . '/' . $filename;
    }

    protected function deletePublicFile(?string $path): void
    {
        $path = trim((string) $path);
        if ($path === '') {
            return;
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

        $surveillanceId = (int) ($declaration->surveillance_id ?? 0);
        $employeeId = (int) ($declaration->employee_id ?? 0);
        $companyId = (int) ($declaration->company_id ?? 0);

        $selectedEmployee = $employeeId > 0 ? DB::table('employee')->where('employee_id', $employeeId)->first() : null;
        $selectedCompany = $companyId > 0 ? DB::table('company')->where('company_id', $companyId)->first() : null;
        $doctor = ! empty($declaration->doctor_id) ? DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first() : $this->linkedDoctorRecord($user);

        $context = [
            'chemicalInfo' => $surveillanceId > 0 && Schema::hasTable('chemical_information') ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first() : null,
            'historyOfHealth' => $surveillanceId > 0 && Schema::hasTable('history_of_health') ? DB::table('history_of_health')->where('surveillance_id', $surveillanceId)->first() : null,
            'clinicalFindings' => $surveillanceId > 0 && Schema::hasTable('clinical_findings') ? DB::table('clinical_findings')->where('surveillance_id', $surveillanceId)->first() : null,
            'physicalExam' => $surveillanceId > 0 && Schema::hasTable('physical_examination') ? DB::table('physical_examination')->where('surveillance_id', $surveillanceId)->first() : null,
            'targetOrgan' => $surveillanceId > 0 && Schema::hasTable('target_organ') ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first() : null,
            'biologicalMonitoring' => $surveillanceId > 0 && Schema::hasTable('biological_monitoring') ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first() : null,
            'fitnessRespirator' => $surveillanceId > 0 && Schema::hasTable('fitness_respirator') ? DB::table('fitness_respirator')->where('surveillance_id', $surveillanceId)->first() : null,
            'msFindings' => $surveillanceId > 0 && Schema::hasTable('ms_findings') ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first() : null,
            'recommendationData' => $surveillanceId > 0 && Schema::hasTable('recommendation') ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first() : null,
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
                'pageMode' => ($readOnly || $this->isRecommendationFinalized($context['recommendationData'] ?? null)) ? 'view' : 'edit',
                'readOnly' => $readOnly || $this->isRecommendationFinalized($context['recommendationData'] ?? null),
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
                ->with('status', 'Surveillance examination saved and locked successfully.');
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

    protected function upsertSurveillanceChildRow(string $table, string $primaryKey, int $surveillanceId, int $employeeId, array $payload): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $record = DB::table($table)
            ->where('surveillance_id', $surveillanceId)
            ->where('employee_id', $employeeId)
            ->first();

        if ($record) {
            DB::table($table)->where($primaryKey, $record->{$primaryKey})->update($payload);
        } else {
            DB::table($table)->insert($payload);
        }
    }

    protected function nullableChoice($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }

    protected function surveillanceSectionStatusesFromRequest(Request $request): array
    {
        $baselineLines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim((string) $request->input('baseline_results', ''))) ?: [], static fn ($line) => trim((string) $line) !== ''));
        $annualLines = array_values(array_filter(preg_split('/\r\n|\r|\n/', trim((string) $request->input('baseline_annual', ''))) ?: [], static fn ($line) => trim((string) $line) !== ''));
        $biologicalDone = ! empty($baselineLines) && count($baselineLines) === count($annualLines);
        if (! $biologicalDone) {
            $bloodResultFiles = (array) $request->file('blood_result_files', []);
            $biologicalDone = count(array_filter($bloodResultFiles)) > 0;
        }

        return [
            'chemical' => trim((string) $request->input('company_name', '')) !== '' && trim((string) $request->input('chemicals', '')) !== '' && trim((string) $request->input('examination_type', '')) !== '' && trim((string) $request->input('examination_date', '')) !== '',
            'history' => trim((string) $request->input('breathing_difficulty', '')) !== '',
            'clinical' => trim((string) $request->input('result_clinical_findings', '')) !== '',
            'physical' => trim((string) $request->input('weight', '')) !== '' && trim((string) $request->input('height', '')) !== '' && trim((string) $request->input('BMI', '')) !== '',
            'target' => trim((string) $request->input('blood_count', '')) !== '' && trim((string) $request->input('renal_function', '')) !== '' && trim((string) $request->input('liver_function', '')) !== '' && trim((string) $request->input('chest_xray', '')) !== '',
            'biological' => $biologicalDone,
            'respirator' => trim((string) $request->input('fitness_result', '')) !== '',
            'findings' => trim((string) $request->input('history_of_health', '')) !== '' && trim((string) $request->input('conclusion_fitness', '')) !== '',
            'recommendation' => (! empty(array_filter((array) $request->input('recommendation_types', []))) || trim((string) $request->input('recommendation_type_other', '')) !== '') && trim((string) $request->input('MRPdate_start', '')) !== '' && trim((string) $request->input('MRPdate_end', '')) !== '' && trim((string) $request->input('nextReview_date', '')) !== '',
        ];
    }

    protected function surveillanceSectionStatusesFromModels(array $context): array
    {
        return [
            'chemical' => ! empty($context['chemicalInfo']) && trim((string) ($context['chemicalInfo']->chemicals ?? '')) !== '' && trim((string) ($context['chemicalInfo']->examination_type ?? '')) !== '' && trim((string) ($context['chemicalInfo']->examination_date ?? '')) !== '',
            'history' => ! empty($context['historyOfHealth']),
            'clinical' => ! empty($context['clinicalFindings']) && trim((string) ($context['clinicalFindings']->result_clinical_findings ?? '')) !== '',
            'physical' => ! empty($context['physicalExam']) && ($context['physicalExam']->weight ?? null) !== null && ($context['physicalExam']->height ?? null) !== null,
            'target' => ! empty($context['targetOrgan']) && trim((string) ($context['targetOrgan']->blood_count ?? '')) !== '',
            'biological' => ! empty($context['biologicalMonitoring']) && (trim((string) ($context['biologicalMonitoring']->baseline_results ?? '')) !== '' || trim((string) ($context['biologicalMonitoring']->baseline_annual ?? '')) !== '' || trim((string) ($context['biologicalMonitoring']->blood_result_files ?? '')) !== ''),
            'respirator' => ! empty($context['fitnessRespirator']) && trim((string) ($context['fitnessRespirator']->fitness_result ?? '')) !== '',
            'findings' => ! empty($context['msFindings']) && trim((string) ($context['msFindings']->history_of_health ?? '')) !== '',
            'recommendation' => ! empty($context['recommendationData'])
                && trim((string) ($context['recommendationData']->recommencation_type ?? '')) !== ''
                && trim((string) ($context['recommendationData']->employee_signature ?? '')) !== ''
                && trim((string) ($context['recommendationData']->employee_signature_date ?? '')) !== '',
        ];
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
            'target_organ',
            'physical_examination',
            'clinical_findings',
            'history_of_health',
            'chemical_information',
        ];
    }
}
