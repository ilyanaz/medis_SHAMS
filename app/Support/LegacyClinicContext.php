<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LegacyClinicContext
{
    public function compose(string $viewName, array $existing, Request $request): array
    {
        $user = $this->panelUser($request);
        $activeClinic = $this->activeClinic($request);
        $activeClinicId = $this->activeClinicId($request);
        $doctor = $this->linkedDoctor($user);

        $payload = [
            'panelUser' => $user,
            'activeClinic' => $activeClinic,
            'clinicName' => $existing['clinicName'] ?? ($activeClinic->clinic_name ?? 'Medis SHAMS'),
            'clinicLogoUrl' => $existing['clinicLogoUrl'] ?? $this->assetUrl($activeClinic->clinic_header_path ?? null),
            'clinicHeaderUrl' => $existing['clinicHeaderUrl'] ?? $this->assetUrl($activeClinic->clinic_header_path ?? null),
            'username' => $existing['username'] ?? $this->displayName($user),
            'doctor' => $existing['doctor'] ?? $doctor,
            'doctorSignatureUrl' => $existing['doctorSignatureUrl'] ?? $this->assetUrl($doctor->doctor_sign ?? null),
            'doctorSignatureName' => $existing['doctorSignatureName'] ?? $this->doctorDisplayName($doctor),
        ];

        $companyModule = $this->companyModuleForView($viewName);
        $companies = $this->needsCompanyContext($viewName)
            ? $this->companies($activeClinicId, $companyModule)
            : collect();
        $selectedCompany = $this->selectedCompany($request, $activeClinicId, $companies, $companyModule);

        if ($this->needsCompanyContext($viewName)) {
            $payload['companies'] = $existing['companies'] ?? $companies;
            $payload['companyTotal'] = $existing['companyTotal'] ?? $companies->count();
            $payload['selectedCompany'] = $existing['selectedCompany'] ?? $selectedCompany;
        }

        if ($this->needsEmployeeContext($viewName)) {
            $employees = $this->employees($activeClinicId, $selectedCompany);
            $selectedEmployee = $this->selectedEmployee($request, $activeClinicId, $selectedCompany, $employees);

            $payload['employees'] = $existing['employees'] ?? $employees;
            $payload['employeeTotal'] = $existing['employeeTotal'] ?? $employees->count();
            $payload['selectedEmployee'] = $existing['selectedEmployee'] ?? $selectedEmployee;
            $payload['records'] = $existing['records'] ?? $this->surveillanceRecords($selectedCompany, $selectedEmployee);
            $payload['totalRecords'] = $existing['totalRecords'] ?? count($payload['records']);

            if ($this->needsAudiometryRecordContext($viewName)) {
                $questionnaireRows = $this->audiometryRows($selectedCompany, $selectedEmployee);
                $payload['questionnaireRows'] = $existing['questionnaireRows'] ?? $questionnaireRows;
                $payload['questionnaireTotal'] = $existing['questionnaireTotal'] ?? count($questionnaireRows);

                if ($viewName === 'audiometry.audiometry_list') {
                    $payload['records'] = $existing['records'] ?? $questionnaireRows;
                    $payload['recordTotal'] = $existing['recordTotal'] ?? count($questionnaireRows);
                    $payload['totalRecords'] = $existing['totalRecords'] ?? count($questionnaireRows);
                }
            }

            if ($this->needsAudiometryDataContext($viewName) && $selectedEmployee) {
                $payload = array_merge(
                    $payload,
                    $existing,
                    $this->audiometryDataContext($activeClinicId, $selectedCompany, $selectedEmployee, $request)
                );
            }

            if ($this->needsSurveillanceEmployeeReportContext($viewName) && $selectedEmployee) {
                $occupationalHistoryRows = DB::table('occupational_history')
                    ->where('employee_id', $selectedEmployee->employee_id)
                    ->orderBy('occupHistory_id')
                    ->get();

                $payload = array_merge($payload, [
                    'employeeData' => $existing['employeeData'] ?? $selectedEmployee,
                    'medicalHistoryData' => $existing['medicalHistoryData'] ?? DB::table('medical_history')->where('employee_id', $selectedEmployee->employee_id)->orderByDesc('medHistory_id')->first(),
                    'currentOccupationalData' => $existing['currentOccupationalData'] ?? $occupationalHistoryRows->first(),
                    'pastOccupationalHistoryRows' => $existing['pastOccupationalHistoryRows'] ?? $occupationalHistoryRows->slice(1)->values(),
                    'personalSocialHistoryData' => $existing['personalSocialHistoryData'] ?? DB::table('personal_social_history')->where('employee_id', $selectedEmployee->employee_id)->orderByDesc('perSocHistory_id')->first(),
                    'trainingHistoryData' => $existing['trainingHistoryData'] ?? DB::table('training_history')->where('employee_id', $selectedEmployee->employee_id)->orderByDesc('trainingHistory_id')->first(),
                ]);
            }
        }

        if ($this->needsGeneralReportContext($viewName)) {
            $payload['surveillanceReportRows'] = $existing['surveillanceReportRows'] ?? $this->surveillanceReportRows($activeClinicId);
            $payload['audiometryReportRows'] = $existing['audiometryReportRows'] ?? $this->audiometryReportRows($activeClinicId);
        }

        return $payload;
    }

    protected function activeClinicId(Request $request): ?int
    {
        $clinicId = (int) $request->session()->get('active_clinic_id', 0);

        return $clinicId > 0 ? $clinicId : null;
    }

    protected function activeClinic(Request $request): ?object
    {
        $clinicId = $this->activeClinicId($request);
        if ($clinicId === null || ! Schema::hasTable('clinic')) {
            return null;
        }

        $query = DB::table('clinic')->where('clinic_id', $clinicId);

        $columns = ['clinic_id', 'clinic_name'];
        if (Schema::hasColumn('clinic', 'clinic_header_path')) {
            $columns[] = 'clinic_header_path';
        }

        return $query->first($columns);
    }

    protected function panelUser(Request $request): ?User
    {
        $userId = $request->session()->get('panel_user_id');

        return $userId ? User::query()->find($userId) : null;
    }

    protected function linkedDoctor(?User $user): ?object
    {
        if (! $user || ! Schema::hasTable('doctor')) {
            return null;
        }

        return DB::table('doctor')
            ->where('doctor_email', (string) ($user->email ?? ''))
            ->orWhere('doctor_username', (string) ($user->username ?? ''))
            ->first();
    }

    protected function companies(?int $clinicId, ?string $companyModule = null)
    {
        if (! Schema::hasTable('company')) {
            return collect();
        }

        $query = DB::table('company')
            ->select([
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
                Schema::hasColumn('company', 'company_module') ? 'company_module' : DB::raw('NULL as company_module'),
            ])
            ->orderBy('company_name')
            ->orderBy('company_id');

        if ($clinicId !== null && Schema::hasColumn('company', 'clinic_id')) {
            $query->where(function ($scoped) use ($clinicId): void {
                $scoped->where('clinic_id', $clinicId)
                    ->orWhereNull('clinic_id');
            });
        }

        if ($companyModule !== null && Schema::hasColumn('company', 'company_module')) {
            $query->where(function ($scoped) use ($companyModule): void {
                $scoped->where('company_module', $companyModule)
                    ->orWhereNull('company_module')
                    ->orWhere('company_module', '');
            });
        }

        return $query->get();
    }

    protected function selectedCompany(Request $request, ?int $clinicId, $companies, ?string $companyModule = null): ?object
    {
        $companyId = (int) $request->query('company_id', 0);
        if ($companyId <= 0) {
            return null;
        }

        $selected = $companies->firstWhere('company_id', $companyId);
        if ($selected) {
            return $selected;
        }

        $query = DB::table('company')->where('company_id', $companyId);
        if ($clinicId !== null && Schema::hasColumn('company', 'clinic_id')) {
            $query->where(function ($scoped) use ($clinicId): void {
                $scoped->where('clinic_id', $clinicId)
                    ->orWhereNull('clinic_id');
            });
        }

        if ($companyModule !== null && Schema::hasColumn('company', 'company_module')) {
            $query->where(function ($scoped) use ($companyModule): void {
                $scoped->where('company_module', $companyModule)
                    ->orWhereNull('company_module')
                    ->orWhere('company_module', '');
            });
        }

        return $query->first();
    }

    protected function companyModuleForView(string $viewName): ?string
    {
        if (
            str_starts_with($viewName, 'surveillance.')
            || $viewName === 'company.surveillance_company'
        ) {
            return 'surveillance';
        }

        if (
            str_starts_with($viewName, 'audiometry.')
            || $viewName === 'company.audiometry_company'
        ) {
            return 'audiometry';
        }

        return null;
    }

    protected function employees(?int $clinicId, ?object $selectedCompany)
    {
        if (! Schema::hasTable('employee')) {
            return collect();
        }

        $query = DB::table('employee')
            ->select([
                'employee_id',
                'employee_firstName',
                'employee_lastName',
                'employee_NRIC',
                'employee_passportNo',
                'employee_DOB',
                'employee_gender',
                'employee_address',
                'employee_postcode',
                'employee_district',
                'employee_state',
                'employee_telephone',
                'employee_email',
                'employee_ethnicity',
                'employee_citizenship',
                'employee_martialStatus',
                'no_of_children',
                'years_married',
            ])
            ->orderBy('employee_firstName')
            ->orderBy('employee_lastName')
            ->orderBy('employee_id');

        if ($clinicId !== null && Schema::hasColumn('employee', 'clinic_id')) {
            $query->where(function ($scoped) use ($clinicId): void {
                $scoped->where('clinic_id', $clinicId)
                    ->orWhereNull('clinic_id');
            });
        }

        if ($selectedCompany) {
            if (Schema::hasColumn('employee', 'company_id')) {
                $query->where(function ($scoped) use ($selectedCompany): void {
                    $scoped->where('company_id', $selectedCompany->company_id);

                    if (Schema::hasTable('occupational_history')) {
                        $scoped->orWhereExists(function ($subQuery) use ($selectedCompany): void {
                            $subQuery->select(DB::raw(1))
                                ->from('occupational_history')
                                ->whereColumn('occupational_history.employee_id', 'employee.employee_id')
                                ->where('occupational_history.company_name', (string) $selectedCompany->company_name);
                        });
                    }
                });
            } elseif (Schema::hasTable('occupational_history')) {
                $query->whereExists(function ($subQuery) use ($selectedCompany): void {
                    $subQuery->select(DB::raw(1))
                        ->from('occupational_history')
                        ->whereColumn('occupational_history.employee_id', 'employee.employee_id')
                        ->where('occupational_history.company_name', (string) $selectedCompany->company_name);
                });
            }
        }

        return $query->get();
    }

    protected function selectedEmployee(Request $request, ?int $clinicId, ?object $selectedCompany, $employees): ?object
    {
        $employeeId = (int) $request->query('employee_id', 0);
        if ($employeeId <= 0) {
            return null;
        }

        $selected = $employees->firstWhere('employee_id', $employeeId);
        if ($selected) {
            return $selected;
        }

        $query = DB::table('employee')->where('employee_id', $employeeId);
        if ($clinicId !== null && Schema::hasColumn('employee', 'clinic_id')) {
            $query->where(function ($scoped) use ($clinicId): void {
                $scoped->where('clinic_id', $clinicId)
                    ->orWhereNull('clinic_id');
            });
        }
        if ($selectedCompany) {
            if (Schema::hasColumn('employee', 'company_id')) {
                $query->where(function ($scoped) use ($selectedCompany): void {
                    $scoped->where('company_id', $selectedCompany->company_id);

                    if (Schema::hasTable('occupational_history')) {
                        $scoped->orWhereExists(function ($subQuery) use ($selectedCompany): void {
                            $subQuery->select(DB::raw(1))
                                ->from('occupational_history')
                                ->whereColumn('occupational_history.employee_id', 'employee.employee_id')
                                ->where('occupational_history.company_name', (string) $selectedCompany->company_name);
                        });
                    }
                });
            } elseif (Schema::hasTable('occupational_history')) {
                $query->whereExists(function ($subQuery) use ($selectedCompany): void {
                    $subQuery->select(DB::raw(1))
                        ->from('occupational_history')
                        ->whereColumn('occupational_history.employee_id', 'employee.employee_id')
                        ->where('occupational_history.company_name', (string) $selectedCompany->company_name);
                });
            }
        }

        return $query->first();
    }

    protected function surveillanceRecords(?object $selectedCompany, ?object $selectedEmployee): array
    {
        if (! Schema::hasTable('declaration')) {
            return [];
        }

        $query = DB::table('declaration');

        $joins = [
            ['employee', 'employee.employee_id', 'declaration.employee_id'],
            ['company', 'company.company_id', 'declaration.company_id'],
            ['chemical_information', 'chemical_information.surveillance_id', 'declaration.surveillance_id'],
            ['history_of_health', 'history_of_health.surveillance_id', 'declaration.surveillance_id'],
            ['clinical_findings', 'clinical_findings.surveillance_id', 'declaration.surveillance_id'],
            ['physical_examination', 'physical_examination.surveillance_id', 'declaration.surveillance_id'],
            ['target_organ', 'target_organ.surveillance_id', 'declaration.surveillance_id'],
            ['biological_monitoring', 'biological_monitoring.surveillance_id', 'declaration.surveillance_id'],
            ['fitness_respirator', 'fitness_respirator.surveillance_id', 'declaration.surveillance_id'],
            ['ms_findings', 'ms_findings.surveillance_id', 'declaration.surveillance_id'],
            ['recommendation', 'recommendation.surveillance_id', 'declaration.surveillance_id'],
            ['summary_report', 'summary_report.surveillance_id', 'declaration.surveillance_id'],
        ];

        foreach ($joins as [$table, $left, $right]) {
            if (Schema::hasTable($table)) {
                $query->leftJoin($table, $left, '=', $right);
            }
        }

        $optionalSelect = static function (string $table, string $column, ?string $alias = null) {
            $selectAlias = $alias ?? $column;

            return Schema::hasTable($table) && Schema::hasColumn($table, $column)
                ? $table.'.'.$column.($alias ? ' as '.$alias : '')
                : DB::raw('NULL as '.$selectAlias);
        };

        $query->select([
            'declaration.*',
            $optionalSelect('employee', 'employee_firstName'),
            $optionalSelect('employee', 'employee_lastName'),
            $optionalSelect('employee', 'employee_NRIC'),
            $optionalSelect('employee', 'employee_passportNo'),
            $optionalSelect('employee', 'employee_telephone'),
            $optionalSelect('company', 'company_name'),
            $optionalSelect('chemical_information', 'chemicals'),
            $optionalSelect('summary_report', 'summaryReport_id'),
            $optionalSelect('summary_report', 'name_of_workUnit'),
            $optionalSelect('chemical_information', 'examination_type'),
            $optionalSelect('chemical_information', 'examination_date'),
            $optionalSelect('clinical_findings', 'result_clinical_findings'),
            $optionalSelect('physical_examination', 'weight'),
            $optionalSelect('physical_examination', 'height'),
            $optionalSelect('physical_examination', 'others'),
            $optionalSelect('target_organ', 'blood_count_result'),
            $optionalSelect('target_organ', 'renal_function_result'),
            $optionalSelect('target_organ', 'liver_function_result'),
            $optionalSelect('target_organ', 'chest_xray_result'),
            $optionalSelect('biological_monitoring', 'baseline_results'),
            $optionalSelect('biological_monitoring', 'baseline_annual'),
            $optionalSelect('biological_monitoring', 'blood_result_files'),
            $optionalSelect('biological_monitoring', 'manual_completed'),
            $optionalSelect('fitness_respirator', 'fitness_result'),
            $optionalSelect('ms_findings', 'history_of_health'),
            $optionalSelect('recommendation', 'recommencation_type'),
            $optionalSelect('recommendation', 'employee_signature', 'recommendation_employee_signature'),
            $optionalSelect('recommendation', 'employee_signature_date', 'recommendation_employee_signature_date'),
            $optionalSelect('recommendation', 'is_final', 'recommendation_is_final'),
        ])->orderByDesc('declaration.declaration_id');

        if ($selectedCompany) {
            $query->where('declaration.company_id', $selectedCompany->company_id);
        }

        if ($selectedEmployee) {
            $query->where('declaration.employee_id', $selectedEmployee->employee_id);
        }

        return $query->get()->map(function ($row) {
            $patientSectionData = $this->surveillancePatientCompletionData($row);
            $hasSpirometryResults = ! is_null($row->surveillance_id)
                && Schema::hasTable('target_organ')
                && DB::table('target_organ')
                    ->where('surveillance_id', $row->surveillance_id)
                    ->whereNotNull('spirometry_FEV1')
                    ->whereNotNull('spirometry_FVC')
                    ->whereNotNull('spirometry_FEV_FVC')
                    ->exists();
            $isCompleted = ! empty($row->recommendation_is_final);
            $sectionStatuses = [
                'patient' => $patientSectionData['patient'],
                'chemical' => trim((string) ($row->chemicals ?? '')) !== '' && trim((string) ($row->examination_type ?? '')) !== '' && trim((string) ($row->examination_date ?? '')) !== '',
                'history' => ! is_null($row->surveillance_id) && Schema::hasTable('history_of_health')
                    ? DB::table('history_of_health')->where('surveillance_id', $row->surveillance_id)->exists()
                    : false,
                'clinical' => trim((string) ($row->result_clinical_findings ?? '')) !== '',
                'physical' => ($row->weight ?? null) !== null && ($row->height ?? null) !== null,
                'target' => trim((string) ($row->blood_count_result ?? '')) !== ''
                    && trim((string) ($row->renal_function_result ?? '')) !== ''
                    && trim((string) ($row->liver_function_result ?? '')) !== ''
                    && $hasSpirometryResults,
                'biological' => trim((string) ($row->baseline_results ?? '')) !== ''
                    || trim((string) ($row->baseline_annual ?? '')) !== ''
                    || trim((string) ($row->blood_result_files ?? '')) !== ''
                    || (bool) ($row->manual_completed ?? false),
                'respirator' => trim((string) ($row->fitness_result ?? '')) !== '',
                'findings' => trim((string) ($row->history_of_health ?? '')) !== '',
                'recommendation' => trim((string) ($row->recommencation_type ?? '')) !== ''
                    && trim((string) ($row->recommendation_employee_signature ?? '')) !== ''
                    && trim((string) ($row->recommendation_employee_signature_date ?? '')) !== '',
            ];

            $row->is_completed = $isCompleted;
            $row->section_statuses = $sectionStatuses;

            return $row;
        })->all();
    }

    protected function surveillancePatientCompletionData(object $row): array
    {
        $employeeId = (int) ($row->employee_id ?? 0);
        $surveillanceId = (int) ($row->surveillance_id ?? 0);

        if ($employeeId <= 0) {
            return ['patient' => false];
        }

        $medicalHistory = null;
        if (Schema::hasTable('medical_history')) {
            $medicalHistoryQuery = DB::table('medical_history')->where('employee_id', $employeeId);
            if ($surveillanceId > 0 && Schema::hasColumn('medical_history', 'surveillance_id')) {
                $medicalHistory = DB::table('medical_history')
                    ->where('employee_id', $employeeId)
                    ->where('surveillance_id', $surveillanceId)
                    ->orderByDesc('medHistory_id')
                    ->first();
                if ($medicalHistory === null) {
                    $medicalHistoryQuery->whereNull('surveillance_id');
                } else {
                    $medicalHistoryQuery = null;
                }
            }
            if ($medicalHistory === null && $medicalHistoryQuery !== null) {
                $medicalHistory = $medicalHistoryQuery->orderByDesc('medHistory_id')->first();
            }
        }

        $occupationalHistory = null;
        if (Schema::hasTable('occupational_history')) {
            $occupationalQuery = DB::table('occupational_history')->where('employee_id', $employeeId);
            if ($surveillanceId > 0 && Schema::hasColumn('occupational_history', 'surveillance_id')) {
                $occupationalHistory = DB::table('occupational_history')
                    ->where('employee_id', $employeeId)
                    ->where('surveillance_id', $surveillanceId)
                    ->orderBy('occupHistory_id')
                    ->first();
                if ($occupationalHistory === null) {
                    $occupationalQuery->whereNull('surveillance_id');
                } else {
                    $occupationalQuery = null;
                }
            }
            if ($occupationalHistory === null && $occupationalQuery !== null) {
                $occupationalHistory = $occupationalQuery->orderBy('occupHistory_id')->first();
            }
        }

        $personalHistory = null;
        if (Schema::hasTable('personal_social_history')) {
            $personalQuery = DB::table('personal_social_history')->where('employee_id', $employeeId);
            if ($surveillanceId > 0 && Schema::hasColumn('personal_social_history', 'surveillance_id')) {
                $personalHistory = DB::table('personal_social_history')
                    ->where('employee_id', $employeeId)
                    ->where('surveillance_id', $surveillanceId)
                    ->orderByDesc('perSocHistory_id')
                    ->first();
                if ($personalHistory === null) {
                    $personalQuery->whereNull('surveillance_id');
                } else {
                    $personalQuery = null;
                }
            }
            if ($personalHistory === null && $personalQuery !== null) {
                $personalHistory = $personalQuery->orderByDesc('perSocHistory_id')->first();
            }
        }

        $trainingHistory = null;
        if (Schema::hasTable('training_history')) {
            $trainingQuery = DB::table('training_history')->where('employee_id', $employeeId);
            if ($surveillanceId > 0 && Schema::hasColumn('training_history', 'surveillance_id')) {
                $trainingHistory = DB::table('training_history')
                    ->where('employee_id', $employeeId)
                    ->where('surveillance_id', $surveillanceId)
                    ->orderByDesc('trainingHistory_id')
                    ->first();
                if ($trainingHistory === null) {
                    $trainingQuery->whereNull('surveillance_id');
                } else {
                    $trainingQuery = null;
                }
            }
            if ($trainingHistory === null && $trainingQuery !== null) {
                $trainingHistory = $trainingQuery->orderByDesc('trainingHistory_id')->first();
            }
        }

        $patientDone = ! empty(array_filter([
            trim((string) ($row->employee_firstName ?? '')),
            trim((string) ($row->employee_lastName ?? '')),
            trim((string) ($medicalHistory->diagnosed_history_result ?? '')),
            trim((string) ($medicalHistory->diagnosed_history ?? '')),
            trim((string) ($occupationalHistory->job_title ?? '')),
            trim((string) ($personalHistory->smoking_history ?? '')),
            trim((string) ($trainingHistory->handling_of_chemical ?? '')),
        ], static fn ($value) => $value !== ''));

        return ['patient' => $patientDone];
    }

    protected function hasCompletedOtherTargetTest(int $surveillanceId): bool
    {
        if ($surveillanceId <= 0 || ! Schema::hasTable('target_organ_other_tests')) {
            return false;
        }

        return DB::table('target_organ_other_tests')
            ->where('surveillance_id', $surveillanceId)
            ->whereNotNull('test_name')
            ->where('test_name', '!=', '')
            ->whereNotNull('result')
            ->where('result', '!=', '')
            ->exists();
    }

    protected function audiometryRows(?object $selectedCompany, ?object $selectedEmployee): array
    {
        if (! Schema::hasTable('audiometry_test')) {
            return [];
        }

        $audioCommentStatusSelect = $this->audioCommentStatusSelect();

        $query = DB::table('audiometry_test')
            ->leftJoin('employee', 'employee.employee_id', '=', 'audiometry_test.employee_id')
            ->leftJoin('company', 'company.company_id', '=', 'audiometry_test.company_id')
            ->leftJoin('audio_comments', 'audio_comments.audiometry_id', '=', 'audiometry_test.audiometry_id')
            ->select([
                'audiometry_test.*',
                'employee.employee_firstName',
                'employee.employee_lastName',
                'company.company_name',
                'audio_comments.audio_recommendation',
                $audioCommentStatusSelect,
            ])
            ->orderByDesc('audiometry_test.audiometry_id');

        if ($selectedCompany) {
            $query->where('audiometry_test.company_id', $selectedCompany->company_id);
        }

        if ($selectedEmployee) {
            $query->where('audiometry_test.employee_id', $selectedEmployee->employee_id);
        }

        return $query->get()->all();
    }

    protected function surveillanceReportRows(?int $clinicId): array
    {
        if (! Schema::hasTable('declaration')) {
            return [];
        }

        $hasCompanyClinicId = Schema::hasColumn('company', 'clinic_id');
        $hasEmployeeClinicId = Schema::hasColumn('employee', 'clinic_id');
        $clinicScopeSelect = match (true) {
            $hasCompanyClinicId && $hasEmployeeClinicId => DB::raw('COALESCE(company.clinic_id, employee.clinic_id) as clinic_scope_id'),
            $hasCompanyClinicId => DB::raw('company.clinic_id as clinic_scope_id'),
            $hasEmployeeClinicId => DB::raw('employee.clinic_id as clinic_scope_id'),
            default => DB::raw('NULL as clinic_scope_id'),
        };

        $query = DB::table('declaration')
            ->leftJoin('employee', 'employee.employee_id', '=', 'declaration.employee_id')
            ->leftJoin('company', 'company.company_id', '=', 'declaration.company_id')
            ->leftJoin('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
            ->leftJoin('recommendation', 'recommendation.surveillance_id', '=', 'declaration.surveillance_id')
            ->leftJoin('summary_report', 'summary_report.surveillance_id', '=', 'declaration.surveillance_id')
              ->select([
                  'declaration.declaration_id',
                  'declaration.company_id',
                  'declaration.employee_id',
                  'declaration.surveillance_id',
                'declaration.employee_date',
                'declaration.doctor_date',
                'declaration.doctor_signature',
                'declaration.employee_signature',
                  'employee.employee_firstName',
                  'employee.employee_lastName',
                  'employee.employee_NRIC',
                  'employee.employee_passportNo',
                  'employee.employee_telephone',
                  'employee.employee_email',
                  'company.company_name',
                  'chemical_information.chemicals',
                  'summary_report.summaryReport_id',
                  'summary_report.name_of_workUnit',
                  'recommendation.is_final as recommendation_is_final',
                  $clinicScopeSelect,
              ])
            ->orderByDesc('declaration.declaration_id');

        if ($clinicId !== null && ($hasCompanyClinicId || $hasEmployeeClinicId)) {
            $query->where(function ($scoped) use ($clinicId): void {
                if (Schema::hasColumn('company', 'clinic_id')) {
                    $scoped->orWhere('company.clinic_id', $clinicId);
                }
                if (Schema::hasColumn('employee', 'clinic_id')) {
                    $scoped->orWhere('employee.clinic_id', $clinicId);
                }
            });
        }

        if (Schema::hasTable('recommendation') && Schema::hasColumn('recommendation', 'is_final')) {
            $query->where('recommendation.is_final', 1);
        }

        $reportRows = $query->get()->flatMap(function ($row): array {
            $employeeName = trim((string) (($row->employee_firstName ?? '') . ' ' . ($row->employee_lastName ?? '')));
            $identityNo = trim((string) (($row->employee_NRIC ?? '') !== '' ? ($row->employee_NRIC ?? '') : ($row->employee_passportNo ?? '')));
            $isCompleted = ! empty($row->recommendation_is_final);
            $findings = null;
            if (! empty($row->surveillance_id) && Schema::hasTable('ms_findings')) {
                $findings = DB::table('ms_findings')
                    ->where('surveillance_id', $row->surveillance_id)
                    ->first();
            }
            $isAbnormal = false;
            if ($findings) {
                $isAbnormal = in_array((string) ($findings->conclusion_fitness ?? ''), ['Not Fit', 'Abnormal'], true)
                    || in_array((string) ($findings->history_of_health ?? ''), ['Yes', 'Abnormal'], true)
                    || in_array((string) ($findings->clinical_findings ?? ''), ['Yes', 'Abnormal'], true)
                    || in_array((string) ($findings->target_organ ?? ''), ['Yes', 'Abnormal'], true)
                    || in_array((string) ($findings->biological_monitoring ?? ''), ['Yes', 'Abnormal'], true)
                    || in_array((string) ($findings->CF_work_related ?? ''), ['Yes', 'Abnormal'], true)
                    || in_array((string) ($findings->TO_work_related ?? ''), ['Yes', 'Abnormal'], true)
                    || in_array((string) ($findings->BM_work_related ?? ''), ['Yes', 'Abnormal'], true)
                    || in_array((string) ($findings->pregnancy_breastFeding ?? ''), ['Yes', 'Abnormal'], true);
            }
            $routeParams = array_filter([
                'declaration_id' => $row->declaration_id,
                'employee_id' => $row->employee_id,
                'company_id' => $row->company_id,
                'surveillance_id' => $row->surveillance_id,
            ]);
            $base = [
                'module' => 'surveillance',
                'declaration_id' => (int) ($row->declaration_id ?? 0),
                'employee_id' => (int) ($row->employee_id ?? 0),
                'company_id' => (int) ($row->company_id ?? 0),
                'surveillance_id' => (int) ($row->surveillance_id ?? 0),
                  'employee_name' => $employeeName !== '' ? $employeeName : 'Not set',
                  'company' => (string) ($row->company_name ?? 'Not set'),
                  'phone_no' => (string) ($row->employee_telephone ?? '-'),
                  'employee_email' => (string) ($row->employee_email ?? ''),
                  'identity_no' => $identityNo !== '' ? $identityNo : '-',
                  'chemical_name' => (string) ($row->chemicals ?: 'Surveillance record'),
                  'work_unit' => (string) ($row->name_of_workUnit ?? ''),
                  'has_saved_summary' => ! empty($row->summaryReport_id),
                'status' => $isCompleted ? 'Completed' : 'Incomplete',
                'status_key' => $isCompleted ? 'completed' : 'incomplete',
                'date_examined' => (string) ($row->employee_date ?: $row->doctor_date ?: ''),
            ];

            $reports = [
                array_merge($base, [
                    'filter' => 'usechh 1',
                    'href' => route('surveillance.report.usechh1', $routeParams),
                    'pdf_href' => route('pdf.usechh1', $routeParams),
                ]),
                array_merge($base, [
                    'filter' => 'usechh 2',
                    'href' => route('surveillance.report.summary-employee', $routeParams),
                    'pdf_href' => route('pdf.usechh2', $routeParams),
                ]),
                array_merge($base, [
                    'filter' => 'usechh 3',
                    'href' => route('surveillance.report.fitness', $routeParams),
                    'pdf_href' => route('pdf.usechh3', $routeParams),
                ]),
                array_merge($base, [
                    'filter' => 'usechh 4',
                    'href' => route('surveillance.report.summary', $routeParams),
                    'pdf_href' => route('pdf.usechh4.download', $routeParams),
                ]),
            ];

            $hasSavedRemoval = ! empty($row->surveillance_id)
                && Schema::hasTable('removal_report')
                && DB::table('removal_report')->where('surveillance_id', $row->surveillance_id)->exists();

            if ($isAbnormal || $hasSavedRemoval) {
                $reports[] = array_merge($base, [
                    'filter' => 'usechh 5i',
                    'href' => route('surveillance.report.removal', $routeParams),
                    'pdf_href' => route('pdf.usechh5i.download', $routeParams),
                    'has_saved_removal' => $hasSavedRemoval,
                ]);
            }

            if ($hasSavedRemoval) {
                $reports[] = array_merge($base, [
                    'filter' => 'usechh 5ii',
                    'href' => route('surveillance.report.abnormal', $routeParams),
                    'pdf_href' => route('pdf.usechh5ii.download', $routeParams),
                ]);
            }

            return $reports;
        });

        $regularRows = [];
        $groupedAbnormalRows = [];

        foreach ($reportRows as $reportRow) {
            if (strtolower(trim((string) ($reportRow['filter'] ?? ''))) !== 'usechh 5ii') {
                $regularRows[] = $reportRow;
                continue;
            }

            $groupKey = implode('|', [
                (string) ($reportRow['company_id'] ?? 0),
                trim((string) ($reportRow['chemical_name'] ?? '')),
                trim((string) ($reportRow['date_examined'] ?? '')),
            ]);

            if (! isset($groupedAbnormalRows[$groupKey])) {
                $routeParams = array_filter([
                    'declaration_id' => $reportRow['declaration_id'] ?? null,
                    'employee_id' => $reportRow['employee_id'] ?? null,
                    'company_id' => $reportRow['company_id'] ?? null,
                    'surveillance_id' => $reportRow['surveillance_id'] ?? null,
                    'group_chemical' => $reportRow['chemical_name'] ?? null,
                    'group_date' => $reportRow['date_examined'] ?? null,
                ], static fn ($value) => $value !== null && $value !== '');

                $reportRow['href'] = route('surveillance.report.abnormal', $routeParams);
                $reportRow['pdf_href'] = route('pdf.usechh5ii.download', $routeParams);
                $reportRow['group_count'] = 1;
                $reportRow['group_chemical'] = (string) ($reportRow['chemical_name'] ?? '');
                $reportRow['employee_name'] = (string) ($reportRow['chemical_name'] ?? 'Chemical');
                $reportRow['chemical_name'] = '1 patient';
                $groupedAbnormalRows[$groupKey] = $reportRow;
                continue;
            }

            $groupedAbnormalRows[$groupKey]['group_count'] += 1;
            $groupedAbnormalRows[$groupKey]['employee_name'] = (string) ($groupedAbnormalRows[$groupKey]['group_chemical'] ?? $groupedAbnormalRows[$groupKey]['employee_name'] ?? 'Chemical');
            $groupedAbnormalRows[$groupKey]['chemical_name'] = $groupedAbnormalRows[$groupKey]['group_count'].' patient'.($groupedAbnormalRows[$groupKey]['group_count'] === 1 ? '' : 's');
        }

        return array_values(array_merge($regularRows, array_values($groupedAbnormalRows)));
    }

    protected function audiometryReportRows(?int $clinicId): array
    {
        if (! Schema::hasTable('audiometry_test')) {
            return [];
        }

        $audioCommentStatusSelect = $this->audioCommentStatusSelect();

        $query = DB::table('audiometry_test')
            ->leftJoin('employee', 'employee.employee_id', '=', 'audiometry_test.employee_id')
            ->leftJoin('company', 'company.company_id', '=', 'audiometry_test.company_id')
            ->leftJoin('audio_comments', 'audio_comments.audiometry_id', '=', 'audiometry_test.audiometry_id')
            ->select([
                'audiometry_test.audiometry_id',
                'audiometry_test.company_id',
                'audiometry_test.employee_id',
                'audiometry_test.audioTest_date',
                'employee.employee_firstName',
                'employee.employee_lastName',
                'company.company_name',
                'audio_comments.audio_recommendation',
                $audioCommentStatusSelect,
            ])
            ->orderByDesc('audiometry_test.audiometry_id');

        if ($clinicId !== null && Schema::hasColumn('company', 'clinic_id')) {
            $query->where('company.clinic_id', $clinicId);
        }

        return $query->get()->map(function ($row): array {
            $employeeName = trim((string) (($row->employee_firstName ?? '') . ' ' . ($row->employee_lastName ?? '')));
            $statusKey = ($row->status ?? '') === 'completed' ? 'completed' : 'pending';

            return [
                'module' => 'audiometry',
                'filter' => !empty($row->audio_recommendation) ? 'report' : 'questionnaire',
                'employee_name' => $employeeName !== '' ? $employeeName : 'Not set',
                'company' => (string) ($row->company_name ?? 'Not set'),
                'chemical_name' => 'Noise exposure',
                'status' => $statusKey === 'completed' ? 'Completed' : 'Pending',
                'status_key' => $statusKey,
                'date_examined' => (string) ($row->audioTest_date ?? ''),
                'href' => route('audiometry.report', array_filter([
                    'audiometry_id' => $row->audiometry_id,
                    'employee_id' => $row->employee_id,
                    'company_id' => $row->company_id,
                ])),
                'pdf_href' => route('pdf.audio-report', array_filter([
                    'audiometry_id' => $row->audiometry_id,
                    'employee_id' => $row->employee_id,
                    'company_id' => $row->company_id,
                ])),
            ];
        })->all();
    }

    protected function audiometryDataContext(?int $clinicId, ?object $selectedCompany, ?object $selectedEmployee, Request $request): array
    {
        if (! Schema::hasTable('audiometry_test') || ! $selectedEmployee) {
            return [];
        }

        $audiometryId = (int) $request->query('audiometry_id', 0);
        $query = DB::table('audiometry_test')
            ->where('employee_id', $selectedEmployee->employee_id);

        if ($selectedCompany) {
            $query->where('company_id', $selectedCompany->company_id);
        }

        if ($audiometryId > 0) {
            $query->where('audiometry_id', $audiometryId);
        } else {
            $query->orderByDesc('audiometry_id');
        }

        $audiometryTest = $query->first();
        if (! $audiometryTest) {
            return [
                'audiometryId' => null,
                'audiometryTest' => null,
                'pastMedical' => null,
                'baselineAudiograph' => null,
                'annualAudiograph' => null,
                'audioComments' => null,
                'currentOccupationalHistory' => $this->currentOccupationalHistory($selectedEmployee, $selectedCompany),
                'summaryRightRows' => [],
                'summaryLeftRows' => [],
                'calculatedAverages' => [],
                'isAudiometryAbnormal' => false,
            ];
        }

        $audiometryId = (int) $audiometryTest->audiometry_id;
        $pastMedical = Schema::hasTable('audiometry_pastmedical')
            ? DB::table('audiometry_pastmedical')
                ->where('audiometry_id', $audiometryId)
                ->where('employee_id', $selectedEmployee->employee_id)
                ->first()
            : null;
        $baselineAudiograph = Schema::hasTable('baseline_audiograph')
            ? DB::table('baseline_audiograph')
                ->where('audiometry_id', $audiometryId)
                ->where('employee_id', $selectedEmployee->employee_id)
                ->first()
            : null;
        $annualAudiograph = Schema::hasTable('annual_audiograph')
            ? DB::table('annual_audiograph')
                ->where('audiometry_id', $audiometryId)
                ->where('employee_id', $selectedEmployee->employee_id)
                ->first()
            : null;
        $audioComments = Schema::hasTable('audio_comments')
            ? DB::table('audio_comments')
                ->where('audiometry_id', $audiometryId)
                ->where('employee_id', $selectedEmployee->employee_id)
                ->first()
            : null;

        $calculatedAverages = $this->calculateAudiometryAverages($baselineAudiograph, $annualAudiograph, $audioComments);

        return [
            'audiometryId' => $audiometryId,
            'audiometryTest' => $audiometryTest,
            'pastMedical' => $pastMedical,
            'baselineAudiograph' => $baselineAudiograph,
            'annualAudiograph' => $annualAudiograph,
            'audioComments' => $audioComments,
            'currentOccupationalHistory' => $this->currentOccupationalHistory($selectedEmployee, $selectedCompany),
            'summaryRightRows' => $this->audiometrySummaryRows('right', $baselineAudiograph, $annualAudiograph, $audiometryTest),
            'summaryLeftRows' => $this->audiometrySummaryRows('left', $baselineAudiograph, $annualAudiograph, $audiometryTest),
            'calculatedAverages' => $calculatedAverages,
            'isAudiometryAbnormal' => $this->isAudiometryAbnormal($audioComments, $calculatedAverages),
        ];
    }

    protected function currentOccupationalHistory(?object $selectedEmployee, ?object $selectedCompany): ?object
    {
        if (! $selectedEmployee || ! Schema::hasTable('occupational_history')) {
            return null;
        }

        $query = DB::table('occupational_history')
            ->where('employee_id', $selectedEmployee->employee_id)
            ->orderBy('occupHistory_id');

        if ($selectedCompany && Schema::hasColumn('occupational_history', 'company_name')) {
            $companyMatch = DB::table('occupational_history')
                ->where('employee_id', $selectedEmployee->employee_id)
                ->where('company_name', (string) $selectedCompany->company_name)
                ->orderBy('occupHistory_id')
                ->first();
            if ($companyMatch) {
                return $companyMatch;
            }
        }

        return $query->first();
    }

    protected function audiometrySummaryRows(string $side, ?object $baselineAudiograph, ?object $annualAudiograph, ?object $audiometryTest): array
    {
        $prefix = strtolower($side) === 'right' ? 'R_' : 'L_';
        $rows = [];
        foreach ([
            ['tone' => 'Baseline', 'date' => (string) ($audiometryTest->audioTest_date ?? ''), 'row' => $baselineAudiograph],
            ['tone' => 'Annual', 'date' => (string) ($audiometryTest->audioTest_date ?? ''), 'row' => $annualAudiograph],
        ] as $meta) {
            if (! $meta['row']) {
                continue;
            }

            $rows[] = [
                'tone' => $meta['tone'],
                'date' => $meta['date'],
                'values' => [
                    '500' => $meta['row']->{$prefix . '500'} ?? '',
                    '1000' => $meta['row']->{$prefix . '1k'} ?? '',
                    '2000' => $meta['row']->{$prefix . '2k'} ?? '',
                    '3000' => $meta['row']->{$prefix . '3k'} ?? '',
                    '4000' => $meta['row']->{$prefix . '4k'} ?? '',
                    '6000' => $meta['row']->{$prefix . '6k'} ?? '',
                    '8000' => $meta['row']->{$prefix . '8k'} ?? '',
                ],
            ];
        }

        return $rows;
    }

    protected function calculateAudiometryAverages(?object $baselineAudiograph, ?object $annualAudiograph, ?object $audioComments): array
    {
        $averages = [
            'average1_right' => $audioComments->average1_right ?? null,
            'average1_left' => $audioComments->average1_left ?? null,
            'average2_right' => $audioComments->average2_right ?? null,
            'average2_left' => $audioComments->average2_left ?? null,
            'STS_right' => (string) ($audioComments->STS_right ?? ''),
            'STS_left' => (string) ($audioComments->STS_left ?? ''),
        ];

        foreach (['right' => 'R_', 'left' => 'L_'] as $side => $prefix) {
            if ($annualAudiograph) {
                $average1 = $this->numericAverage([
                    $annualAudiograph->{$prefix . '2k'} ?? null,
                    $annualAudiograph->{$prefix . '3k'} ?? null,
                    $annualAudiograph->{$prefix . '4k'} ?? null,
                ]);
                $average2 = $this->numericAverage([
                    $annualAudiograph->{$prefix . '500'} ?? null,
                    $annualAudiograph->{$prefix . '1k'} ?? null,
                    $annualAudiograph->{$prefix . '2k'} ?? null,
                    $annualAudiograph->{$prefix . '3k'} ?? null,
                ]);

                if ($averages['average1_' . $side] === null) {
                    $averages['average1_' . $side] = $average1;
                }
                if ($averages['average2_' . $side] === null) {
                    $averages['average2_' . $side] = $average2;
                }
            }

            if ($averages['STS_' . $side] === '' && $baselineAudiograph && $annualAudiograph) {
                $baselineAverage = $this->numericAverage([
                    $baselineAudiograph->{$prefix . '2k'} ?? null,
                    $baselineAudiograph->{$prefix . '3k'} ?? null,
                    $baselineAudiograph->{$prefix . '4k'} ?? null,
                ]);
                $annualAverage = $this->numericAverage([
                    $annualAudiograph->{$prefix . '2k'} ?? null,
                    $annualAudiograph->{$prefix . '3k'} ?? null,
                    $annualAudiograph->{$prefix . '4k'} ?? null,
                ]);

                if ($baselineAverage !== null && $annualAverage !== null) {
                    $averages['STS_' . $side] = ($annualAverage - $baselineAverage) >= 10 ? 'Yes' : 'No';
                }
            }
        }

        return $averages;
    }

    protected function numericAverage(array $values): ?float
    {
        $numeric = array_values(array_filter($values, static fn ($value) => $value !== null && $value !== '' && is_numeric($value)));
        if ($numeric === []) {
            return null;
        }

        return round(array_sum(array_map('floatval', $numeric)) / count($numeric), 2);
    }

    protected function isAudiometryAbnormal(?object $audioComments, array $calculatedAverages): bool
    {
        if (! $audioComments) {
            return false;
        }

        if (in_array((string) ($audioComments->STS_right ?? ''), ['Yes', 'Abnormal'], true)
            || in_array((string) ($audioComments->STS_left ?? ''), ['Yes', 'Abnormal'], true)) {
            return true;
        }

        foreach (['average1_right', 'average1_left', 'average2_right', 'average2_left'] as $key) {
            if (isset($calculatedAverages[$key]) && is_numeric($calculatedAverages[$key]) && (float) $calculatedAverages[$key] >= 25) {
                return true;
            }
        }

        $analysis = strtolower(trim((string) ($audioComments->standard_analysis ?? '')));
        $recommendation = strtolower(trim((string) ($audioComments->audio_recommendation ?? '')));

        return str_contains($analysis, 'abnormal')
            || str_contains($analysis, 'shift')
            || str_contains($recommendation, 'referral')
            || str_contains($recommendation, 'noise-induced')
            || str_contains($recommendation, 'hearing loss');
    }

    protected function assetUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        return $path !== '' ? asset($path) : null;
    }

    protected function displayName(?User $user): string
    {
        if (! $user) {
            return 'User';
        }

        $username = trim((string) ($user->username ?? ''));

        return $username !== '' ? $username : 'User';
    }

    protected function doctorDisplayName(?object $doctor): string
    {
        if (! $doctor) {
            return 'Doctor';
        }

        $name = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));

        return $name !== '' ? $name : 'Doctor';
    }

    protected function needsCompanyContext(string $viewName): bool
    {
        return str_starts_with($viewName, 'company.')
            || str_starts_with($viewName, 'employee.')
            || str_starts_with($viewName, 'surveillance.')
            || str_starts_with($viewName, 'audiometry.')
            || str_starts_with($viewName, 'report.');
    }

    protected function needsEmployeeContext(string $viewName): bool
    {
        return str_starts_with($viewName, 'employee.')
            || str_starts_with($viewName, 'surveillance.')
            || str_starts_with($viewName, 'audiometry.')
            || str_starts_with($viewName, 'report.');
    }

    protected function needsSurveillanceEmployeeReportContext(string $viewName): bool
    {
        return in_array($viewName, [
            'report.surveillance_usechh1Report',
            'report.PDF_USECHH1',
            'report.PDF_employee',
            'report.PDF_USECHH2',
            'report.surveillance_fitnessReport.summaryEmpReport',
        ], true);
    }

    protected function needsAudiometryRecordContext(string $viewName): bool
    {
        return in_array($viewName, [
            'audiometry.audiometry_questionnaire',
            'audiometry.audiometry_list',
        ], true);
    }

    protected function needsAudiometryDataContext(string $viewName): bool
    {
        return in_array($viewName, [
            'audiometry.audiometry_examination',
            'audiometry.audiometry_report',
            'report.PDF_audioReport',
            'report.PDF_questionnaire',
            'report.audiometry_questionnaire_report',
        ], true);
    }

    protected function needsGeneralReportContext(string $viewName): bool
    {
        return $viewName === 'report.general_report';
    }

    protected function audioCommentStatusSelect(): \Illuminate\Contracts\Database\Query\Expression
    {
        $statusColumn = null;

        foreach (['audioComment_id', 'audio_comment_id', 'id'] as $candidate) {
            if (Schema::hasColumn('audio_comments', $candidate)) {
                $statusColumn = 'audio_comments.' . $candidate;
                break;
            }
        }

        if ($statusColumn !== null) {
            return DB::raw("CASE WHEN {$statusColumn} IS NULL THEN 'pending' ELSE 'completed' END as status");
        }

        return DB::raw("CASE WHEN audio_comments.audio_recommendation IS NULL OR audio_comments.audio_recommendation = '' THEN 'pending' ELSE 'completed' END as status");
    }
}
