<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class SystemSampleDataBuilder
{
    private const SIGNATURE_PIXEL = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9p8VXl8AAAAASUVORK5CYII=';

    /**
     * @return array{clinics: array<int, array{name: string, company: string, employees: array<int, string>}>, surveillance_records: int, audiometry_records: int, doctor_username: string, warnings: array<int, string>}
     */
    public function seed(int $totalRecords = 4): array
    {
        $requiredTables = [
            'users',
            'doctor',
            'clinic',
            'company',
            'employee',
            'chemical_information',
            'declaration',
            'audiometry_test',
            'audio_comments',
            'baseline_audiograph',
            'annual_audiograph',
            'audiometry_pastmedical',
        ];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table [{$table}] is missing.");
            }
        }

        $warnings = [];
        foreach (['company.clinic_id', 'employee.clinic_id', 'employee.company_id'] as $columnReference) {
            [$table, $column] = explode('.', $columnReference, 2);
            if (! Schema::hasColumn($table, $column)) {
                $warnings[] = "Column [{$columnReference}] is missing, so clinic scoping will be limited until the latest migration is run.";
            }
        }

        $recordsPerClinic = max(2, (int) ceil(max(4, $totalRecords) / 2));
        $doctor = $this->ensureDoctor();

        $blueprints = [
            [
                'clinic' => [
                    'name' => 'Sample Clinic A',
                    'email' => 'clinic-a@medisshams.test',
                    'username' => 'sample_clinic_a',
                    'telephone' => '+60311110001',
                    'fax' => '+60311110002',
                    'address' => 'Lot 11, Jalan Medis A, Kuala Lumpur',
                    'postcode' => '50450',
                    'district' => 'Kuala Lumpur',
                    'state' => 'Wilayah Persekutuan',
                    'registration' => 'CLINIC-A-001',
                ],
                'company' => [
                    'name' => 'Apex Solvents Sdn Bhd',
                    'registration' => 'MYKPP-A-001',
                    'telephone' => '+60370010001',
                    'email' => 'apex.solvents@medisshams.test',
                    'fax' => '+60370010002',
                    'address' => 'No. 8, Jalan Industri A, Kuala Lumpur',
                    'postcode' => '52200',
                    'district' => 'Kuala Lumpur',
                    'state' => 'Wilayah Persekutuan',
                ],
                'employees' => [
                    [
                        'first_name' => 'Aina',
                        'last_name' => 'Sample',
                        'email' => 'aina.sample@medisshams.test',
                        'passport' => 'SP-A-001',
                        'gender' => 'Female',
                    ],
                    [
                        'first_name' => 'Firdaus',
                        'last_name' => 'Sample',
                        'email' => 'firdaus.sample@medisshams.test',
                        'passport' => 'SP-A-002',
                        'gender' => 'Male',
                    ],
                ],
            ],
            [
                'clinic' => [
                    'name' => 'Sample Clinic B',
                    'email' => 'clinic-b@medisshams.test',
                    'username' => 'sample_clinic_b',
                    'telephone' => '+60322220001',
                    'fax' => '+60322220002',
                    'address' => 'Lot 17, Jalan Medis B, Shah Alam',
                    'postcode' => '40150',
                    'district' => 'Shah Alam',
                    'state' => 'Selangor',
                    'registration' => 'CLINIC-B-001',
                ],
                'company' => [
                    'name' => 'Beacon Noise Works Sdn Bhd',
                    'registration' => 'MYKPP-B-001',
                    'telephone' => '+60380020001',
                    'email' => 'beacon.noise@medisshams.test',
                    'fax' => '+60380020002',
                    'address' => 'No. 17, Jalan Industri B, Shah Alam',
                    'postcode' => '40150',
                    'district' => 'Shah Alam',
                    'state' => 'Selangor',
                ],
                'employees' => [
                    [
                        'first_name' => 'Mei',
                        'last_name' => 'Sample',
                        'email' => 'mei.sample@medisshams.test',
                        'passport' => 'SP-B-001',
                        'gender' => 'Female',
                    ],
                    [
                        'first_name' => 'Ravi',
                        'last_name' => 'Sample',
                        'email' => 'ravi.sample@medisshams.test',
                        'passport' => 'SP-B-002',
                        'gender' => 'Male',
                    ],
                ],
            ],
        ];

        $summary = DB::transaction(function () use ($blueprints, $doctor, $recordsPerClinic): array {
            $created = [
                'clinics' => [],
                'surveillance_records' => 0,
                'audiometry_records' => 0,
                'doctor_username' => (string) ($doctor->doctor_username ?? 'doctor'),
            ];

            foreach ($blueprints as $clinicIndex => $blueprint) {
                $clinicId = $this->ensureClinic($blueprint['clinic']);
                $companyId = $this->ensureCompany($blueprint['company'], $clinicId);

                $employeeNames = [];
                $employees = array_slice($blueprint['employees'], 0, $recordsPerClinic);
                foreach ($employees as $employeeIndex => $employeeBlueprint) {
                    $employeeId = $this->ensureEmployee($employeeBlueprint, $clinicId, $companyId, $blueprint['company']['name']);
                    $this->ensureSurveillanceRecord($clinicIndex, $employeeIndex, $companyId, $employeeId, $doctor->doctor_id, $blueprint['company']['name']);
                    $this->ensureAudiometryRecord($clinicIndex, $employeeIndex, $companyId, $employeeId, $doctor->doctor_id);

                    $created['surveillance_records']++;
                    $created['audiometry_records']++;
                    $employeeNames[] = $employeeBlueprint['first_name'] . ' ' . $employeeBlueprint['last_name'];
                }

                $created['clinics'][] = [
                    'name' => $blueprint['clinic']['name'],
                    'company' => $blueprint['company']['name'],
                    'employees' => $employeeNames,
                ];
            }

            return $created;
        });

        $summary['warnings'] = $warnings;

        return $summary;
    }

    /**
     * @return array{employee_id:int, surveillance_id:int|null, declaration_id:int|null, company_id:int, company_name:string, doctor_username:string}
     */
    public function seedSurveillanceForEmployee(int $employeeId): array
    {
        foreach (['users', 'doctor', 'employee', 'company', 'chemical_information', 'declaration'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Required table [{$table}] is missing.");
            }
        }

        return DB::transaction(function () use ($employeeId): array {
            $employee = DB::table('employee')->where('employee_id', $employeeId)->first();
            if (! $employee) {
                throw new RuntimeException("Employee [{$employeeId}] was not found.");
            }

            $doctor = $this->ensureDoctor();

            $companyId = null;
            $companyName = '';

            if (Schema::hasColumn('employee', 'company_id')) {
                $companyId = (int) ($employee->company_id ?? 0);
            }

            if ($companyId > 0) {
                $company = DB::table('company')->where('company_id', $companyId)->first();
                $companyName = trim((string) ($company->company_name ?? ''));
            }

            if ($companyName === '' && Schema::hasTable('occupational_history')) {
                $history = DB::table('occupational_history')
                    ->where('employee_id', $employeeId)
                    ->orderBy('occupHistory_id')
                    ->first();

                $companyName = trim((string) ($history->company_name ?? ''));

                if ($companyId <= 0 && $companyName !== '') {
                    $companyQuery = DB::table('company')->where('company_name', $companyName);
                    if (Schema::hasColumn('company', 'clinic_id') && Schema::hasColumn('employee', 'clinic_id')) {
                        $clinicId = (int) ($employee->clinic_id ?? 0);
                        if ($clinicId > 0) {
                            $companyQuery->where('clinic_id', $clinicId);
                        }
                    }

                    $companyId = (int) ($companyQuery->value('company_id') ?? 0);
                }
            }

            if ($companyId <= 0 || $companyName === '') {
                throw new RuntimeException("Employee [{$employeeId}] is not linked to a current company yet.");
            }

            if (Schema::hasColumn('employee', 'company_id') && (int) ($employee->company_id ?? 0) !== $companyId) {
                DB::table('employee')
                    ->where('employee_id', $employeeId)
                    ->update(['company_id' => $companyId]);
            }

            $this->ensureSurveillanceRecord(0, 0, $companyId, $employeeId, (int) $doctor->doctor_id, $companyName);

            $surveillanceId = DB::table('chemical_information')
                ->where('employee_id', $employeeId)
                ->where('company_id', $companyId)
                ->orderByDesc('surveillance_id')
                ->value('surveillance_id');

            $declarationId = DB::table('declaration')
                ->where('employee_id', $employeeId)
                ->where('company_id', $companyId)
                ->orderByDesc('declaration_id')
                ->value('declaration_id');

            return [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId !== null ? (int) $surveillanceId : null,
                'declaration_id' => $declarationId !== null ? (int) $declarationId : null,
                'company_id' => $companyId,
                'company_name' => $companyName,
                'doctor_username' => (string) ($doctor->doctor_username ?? 'doctor'),
            ];
        });
    }

    protected function ensureDoctor(): object
    {
        $doctorUser = DB::table('users')
            ->where('role', 'Doctor')
            ->orderBy('user_id')
            ->first();

        if ($doctorUser) {
            DB::table('users')
                ->where('user_id', $doctorUser->user_id)
                ->update([
                    'username' => 'doctor',
                    'email' => 'doctor@medis.com.my',
                    'password' => Hash::make('doctor123'),
                    'role' => 'Doctor',
                ]);

            $doctorUser = DB::table('users')->where('user_id', $doctorUser->user_id)->first();
        } else {
            $userId = $this->upsertRow('users', 'user_id', [
                'email' => 'doctor@medis.com.my',
            ], [
                'username' => 'doctor',
                'email' => 'doctor@medis.com.my',
                'password' => Hash::make('doctor123'),
                'role' => 'Doctor',
            ]);

            $doctorUser = DB::table('users')->where('user_id', $userId)->first();
        }

        $doctorRecord = DB::table('doctor')
            ->where('doctor_email', (string) $doctorUser->email)
            ->orWhere('doctor_username', (string) $doctorUser->username)
            ->first();

        if ($doctorRecord) {
            DB::table('doctor')
                ->where('doctor_id', $doctorRecord->doctor_id)
                ->update([
                    'doctor_email' => 'doctor@medis.com.my',
                    'doctor_username' => 'doctor',
                    'doctor_password' => Hash::make('doctor123'),
                ]);

            return DB::table('doctor')->where('doctor_id', $doctorRecord->doctor_id)->first();
        }

        $payload = [
            'doctor_firstName' => 'Sample',
            'doctor_lastName' => 'Doctor',
            'doctor_email' => (string) $doctorUser->email,
            'doctor_username' => (string) $doctorUser->username,
            'doctor_password' => Hash::make('doctor123'),
            'doctor_telephone' => '+60330030003',
            'doctor_fax' => '+60330030004',
            'doctor_address' => 'Level 5, Medis Tower',
            'doctor_postcode' => '50480',
            'doctor_district' => 'Kuala Lumpur',
            'doctor_state' => 'Wilayah Persekutuan',
            'MMC_no' => 'MMC-SAMPLE-001',
            'OHD_registrationNo' => 'OHD-SAMPLE-001',
        ];

        if (Schema::hasColumn('doctor', 'doctor_sign')) {
            $payload['doctor_sign'] = '';
        }
        if (Schema::hasColumn('doctor', 'doctor_picture')) {
            $payload['doctor_picture'] = '';
        }
        if (Schema::hasColumn('doctor', 'doctor_status')) {
            $payload['doctor_status'] = 'active';
        }

        $doctorId = $this->upsertRow('doctor', 'doctor_id', [
            'doctor_email' => (string) $doctorUser->email,
        ], $payload);

        return DB::table('doctor')->where('doctor_id', $doctorId)->first();
    }

    protected function ensureClinic(array $data): int
    {
        $payload = [
            'clinic_name' => $data['name'],
            'clinic_address' => $data['address'],
            'clinic_postcode' => $data['postcode'],
            'clinic_district' => $data['district'],
            'clinic_state' => $data['state'],
            'clinic_telephone' => $data['telephone'],
            'clinic_fax' => $data['fax'],
            'clinic_email' => $data['email'],
            'clinic_username' => $data['username'],
            'clinic_password' => Hash::make('clinic123'),
        ];

        if (Schema::hasColumn('clinic', 'clinic_registration')) {
            $payload['clinic_registration'] = $data['registration'];
        }
        if (Schema::hasColumn('clinic', 'clinic_header_path')) {
            $payload['clinic_header_path'] = 'images/logos/medis-logo-left-right.png';
        }
        if (Schema::hasColumn('clinic', 'clinic_status')) {
            $payload['clinic_status'] = 'active';
        }

        return $this->upsertRow('clinic', 'clinic_id', [
            'clinic_name' => $data['name'],
        ], $payload);
    }

    protected function ensureCompany(array $data, int $clinicId): int
    {
        $payload = [
            'company_name' => $data['name'],
            'mykpp_registration_no' => $data['registration'],
            'company_address' => $data['address'],
            'company_postcode' => $data['postcode'],
            'company_district' => $data['district'],
            'company_state' => $data['state'],
            'company_telephone' => $data['telephone'],
            'company_email' => $data['email'],
            'company_fax' => $data['fax'],
            'total_workers' => 2,
        ];

        if (Schema::hasColumn('company', 'clinic_id')) {
            $payload['clinic_id'] = $clinicId;
        }

        return $this->upsertRow('company', 'company_id', [
            'company_name' => $data['name'],
        ], $payload);
    }

    protected function ensureEmployee(array $data, int $clinicId, int $companyId, string $companyName): int
    {
        $payload = [
            'employee_firstName' => $data['first_name'],
            'employee_lastName' => $data['last_name'],
            'employee_passportNo' => $data['passport'],
            'employee_DOB' => '1991-01-15',
            'employee_gender' => $data['gender'],
            'employee_address' => 'Sample Employee Address',
            'employee_postcode' => '50000',
            'employee_district' => 'Kuala Lumpur',
            'employee_state' => 'Wilayah Persekutuan',
            'employee_telephone' => '+60123456789',
            'employee_email' => $data['email'],
            'employee_ethnicity' => 'Malay',
            'employee_citizenship' => 'Malaysian Citizen',
            'employee_martialStatus' => 'Single',
            'no_of_children' => 0,
            'years_married' => 0,
            'employee_sign' => self::SIGNATURE_PIXEL,
        ];

        if (Schema::hasColumn('employee', 'clinic_id')) {
            $payload['clinic_id'] = $clinicId;
        }
        if (Schema::hasColumn('employee', 'company_id')) {
            $payload['company_id'] = $companyId;
        }

        $employeeId = $this->upsertRow('employee', 'employee_id', [
            'employee_email' => $data['email'],
        ], $payload);

        if (Schema::hasTable('occupational_history')) {
            $this->upsertRow('occupational_history', 'occupHistory_id', [
                'employee_id' => $employeeId,
                'job_title' => 'Chemical Operator',
                'company_name' => $companyName,
            ], [
                'employment_duration' => '2 years',
                'chemical_exposure_duration' => '2 years',
                'chemical_exposure_incidents' => 'None reported during sample seed.',
            ]);

            $this->upsertRow('occupational_history', 'occupHistory_id', [
                'employee_id' => $employeeId,
                'job_title' => 'Warehouse Assistant',
                'company_name' => 'Legacy Sample Logistics',
            ], [
                'employment_duration' => '1 year',
                'chemical_exposure_duration' => '6 months',
                'chemical_exposure_incidents' => 'Minor solvent splash with full PPE applied.',
            ]);
        }

        return $employeeId;
    }

    protected function ensureSurveillanceRecord(int $clinicIndex, int $employeeIndex, int $companyId, int $employeeId, int $doctorId, string $companyName): void
    {
        $examDate = now()->startOfDay()->subDays(($clinicIndex * 3) + $employeeIndex)->toDateString();
        $chemicalName = $clinicIndex === 0 ? 'Toluene' : 'Noise and Solvent Mix';

        $surveillanceId = $this->upsertRow('chemical_information', 'surveillance_id', [
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'examination_date' => $examDate,
            'chemicals' => $chemicalName,
        ], [
            'doctor_id' => $doctorId,
            'company_name' => $companyName,
            'examination_type' => $employeeIndex % 2 === 0 ? 'Periodic' : 'Pre-Placement',
        ]);

        $employee = DB::table('employee')->where('employee_id', $employeeId)->first();

        $declarationId = $this->upsertRow('declaration', 'declaration_id', [
            'surveillance_id' => $surveillanceId,
        ], [
            'employee_signature' => self::SIGNATURE_PIXEL,
            'employee_date' => $examDate,
            'doctor_signature' => self::SIGNATURE_PIXEL,
            'doctor_date' => $examDate,
            'company_name' => $companyName,
            'employee_firstName' => (string) ($employee->employee_firstName ?? ''),
            'employee_lastName' => (string) ($employee->employee_lastName ?? ''),
            'doctor_id' => $doctorId,
            'company_id' => $companyId,
            'employee_id' => $employeeId,
        ]);

        if (Schema::hasTable('medical_history')) {
            $this->upsertRow('medical_history', 'medHistory_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'diagnosed_history' => 'No diagnosed occupational illness.',
                'medication_history' => 'No long-term medication.',
                'admitted_history' => 'No recent admissions.',
                'family_history' => 'No relevant family history.',
                'others_history' => 'Sample seeded record.',
            ]);
        }

        if (Schema::hasTable('personal_social_history')) {
            $this->upsertRow('personal_social_history', 'perSocHistory_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'smoking_history' => 'Non-smoker',
                'years_of_smoking' => 0,
                'no_of_cigarettes' => 0,
                'vaping_history' => 'No',
                'years_of_vaping' => 0,
                'hobby' => 'Reading and light exercise',
            ]);
        }

        if (Schema::hasTable('training_history')) {
            $this->upsertRow('training_history', 'trainingHistory_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'handling_of_chemical' => 'Yes',
                'chemical_comments' => 'Understands chemical handling SOP.',
                'sign_symptoms' => 'Yes',
                'sign_comments' => 'Aware of symptoms and escalation steps.',
                'chemical_poisoning' => 'Yes',
                'poisoning_comments' => 'Knows emergency protocol.',
                'proper_PPE' => 'Yes',
                'proper_comments' => 'Uses correct PPE for task.',
                'PPE_usage' => 'Yes',
                'usage_comments' => 'Consistent PPE compliance.',
            ]);
        }

        if (Schema::hasTable('physical_examination')) {
            $this->upsertRow('physical_examination', 'pexamHistory_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'weight' => 68.5,
                'height' => 170.0,
                'BMI' => 23.7,
                'bp_systolic' => 118,
                'bp_distolic' => 74,
                'pulse_rate' => 72,
                'respiratory_rate' => 16,
                'general_appearances' => 'Clinically well.',
                's1_s2' => 'Yes',
                'murmur' => 'No',
                'ear_nose_throat' => 'Normal',
                'visual_acuity_right' => '6/6',
                'visual_acuity_left' => '6/6',
                'colour_blindness' => 'No',
                'gas_tenderness' => 'No',
                'abdominal_mass' => 'No',
                'lymph_nodes' => 'Non-palpable',
                'splenomegaly' => 'No',
                'kidney_tenderness' => 'No',
                'ballotable' => 'No',
                'jaundice' => 'No',
                'hepatomegaly' => 'No',
                'muscle_tone' => '5',
                'muscle_tenderness' => 'No',
                'power' => '5',
                'sensation' => 'Normal',
                'sound' => 'Clear',
                'air_entry' => 'Normal',
                'reproductive' => 'Normal',
                'skin' => 'Normal',
                'others' => 'Sample seeded examination record.',
            ]);
        }

        if (Schema::hasTable('recommendation')) {
            $this->upsertRow('recommendation', 'recommendation_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'recommencation_type' => 'Continue surveillance',
                'MRPdate_start' => $examDate,
                'MRPdate_end' => now()->startOfDay()->addMonths(12)->toDateString(),
                'nextReview_date' => now()->startOfDay()->addMonths(12)->toDateString(),
                'notes' => 'Repeat review in 12 months.',
            ]);
        }

        $fitnessReportId = null;
        if (Schema::hasTable('fitness_report')) {
            $fitnessReportId = $this->upsertRow('fitness_report', 'fitnessReport_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'result' => $employeeIndex % 2 === 0 ? 'Fit' : 'Fit with review',
                'remarks' => 'Sample seeded fitness outcome.',
                'company_id' => $companyId,
                'doctor_id' => $doctorId,
            ]);
        }

        if (Schema::hasTable('summary_report')) {
            $this->upsertRow('summary_report', 'summaryReport_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'chemical_name' => $chemicalName,
                'name_of_workUnit' => $clinicIndex === 0 ? 'Blending' : 'Packing',
                'no_exposedWorkers' => 2,
                'totalNo_examined' => 2,
                'employee_id' => $employeeId,
                'company_id' => $companyId,
                'doctor_id' => $doctorId,
                'decision' => 'Continue MS',
                'recommendation' => 'Continue with current surveillance interval.',
                'date_of_implementation' => $examDate,
            ]);
        }

        if (Schema::hasTable('removal_report')) {
            $this->upsertRow('removal_report', 'removalReport_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'removal_type' => 'Temporary',
                'reasons_recommendations' => 'Sample seeded follow-up record.',
                'fitnessReport_id' => $fitnessReportId,
                'doctor_id' => $doctorId,
                'company_id' => $companyId,
            ]);
        }

        if (Schema::hasTable('clinical_findings')) {
            $this->upsertRow('clinical_findings', 'chHistory_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'result_clinical_findings' => 'No',
                'elaboration' => 'No active clinical findings.',
            ]);
        }

        if (Schema::hasTable('biological_monitoring')) {
            $this->upsertRow('biological_monitoring', 'bioMonitor_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'biological_exposure' => 'No',
                'baseline_results' => "Lead::12\nMercury::3",
                'baseline_annual' => "Lead::10\nMercury::2",
            ]);
        }

        if (Schema::hasTable('target_organ')) {
            $this->upsertRow('target_organ', 'target_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'blood_count' => 'Normal',
                'blood_comments' => 'Within normal range.',
                'renal_function' => 'Normal',
                'renal_comments' => 'No renal impairment.',
                'liver_function' => 'Normal',
                'liver_comments' => 'No liver enzyme elevation.',
                'chest_xray' => 'Normal',
                'chest_comments' => 'No active chest findings.',
                'spirometry_FEV1' => 3.4,
                'spirometry_FVC' => 4.1,
                'spirometry_FEV_FVC' => 82.9,
                'spirometry_comments' => 'Normal spirometry result.',
            ]);
        }

        if (Schema::hasTable('ms_findings')) {
            $this->upsertRow('ms_findings', 'msFindings_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'history_of_health' => 'No',
                'clinical_findings' => 'No',
                'CF_work_related' => 'No',
                'target_organ' => 'No',
                'TO_work_related' => 'No',
                'biological_monitoring' => 'No',
                'BM_work_related' => 'No',
                'pregnancy_breastFeding' => 'No',
                'conclusion_fitness' => 'Fit',
            ]);
        }

        if (Schema::hasTable('fitness_respirator')) {
            $this->upsertRow('fitness_respirator', 'fitness_id', [
                'employee_id' => $employeeId,
                'surveillance_id' => $surveillanceId,
            ], [
                'fitness_result' => 'Fit',
                'fitness_justification' => 'Sample seeded respirator fitness assessment.',
            ]);
        }
    }

    protected function ensureAudiometryRecord(int $clinicIndex, int $employeeIndex, int $companyId, int $employeeId, int $doctorId): void
    {
        $examDate = now()->startOfDay()->subDays(($clinicIndex * 4) + $employeeIndex)->toDateString();

        $audiometryId = $this->upsertRow('audiometry_test', 'audiometry_id', [
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'audioTest_date' => $examDate,
        ], [
            'total_years_working' => 5 + $employeeIndex,
            'noYears_working' => 2 + $employeeIndex,
            'audiometer' => 1,
            'calibration_date' => $examDate,
        ]);

        $baselineId = $this->upsertRow('baseline_audiograph', 'baselineAudio_id', [
            'employee_id' => $employeeId,
            'audiometry_id' => $audiometryId,
        ], array_merge($this->audiographPayload(10), [
            'company_id' => $companyId,
        ]));

        $annualId = $this->upsertRow('annual_audiograph', 'annualAudio_id', [
            'employee_id' => $employeeId,
            'audiometry_id' => $audiometryId,
        ], array_merge($this->audiographPayload(15), [
            'company_id' => $companyId,
            'baselineAudio_id' => $baselineId,
        ]));

        $this->upsertRow('audiometry_pastmedical', 'audioPastMedical_id', [
            'employee_id' => $employeeId,
            'audiometry_id' => $audiometryId,
        ], [
            'ear_infections' => 0,
            'head_injury' => 0,
            'ototoxic_drugs' => 0,
            'prev_earSurgery' => 0,
            'pre_noiseExposure' => 'Yes',
            'significant_hobbies' => 'No',
            'seg' => 1,
            'otoscopy' => 1,
            'audio_rinneRight' => 'Positive',
            'audio_rinneLeft' => 'Positive',
            'audio_weber' => 'Center',
            'type_audiogram' => 'Annual',
            'exposure_lex' => 85.5,
            'peakExposure_Lpeak' => 110.0,
            'maxExposure_Lmax' => 92.0,
            'company_id' => $companyId,
        ]);

        $this->upsertRow('audio_comments', $this->audioCommentsPrimaryKey(), [
            'employee_id' => $employeeId,
            'audiometry_id' => $audiometryId,
        ], [
            'STS_right' => 'No',
            'STS_left' => 'No',
            'average1_right' => 15,
            'average2_right' => 15,
            'average1_left' => 15,
            'average2_left' => 15,
            'standard_analysis' => 'No significant threshold shift detected in sample record.',
            'audio_recommendation' => 'Continue annual audiometry review and hearing conservation.',
            'remarks' => 'Sample seeded audiometry record.',
            'doctor_id' => $doctorId,
            'company_id' => $companyId,
            'annualAudio_id' => $annualId,
            'baselineAudio_id' => $baselineId,
        ]);
    }

    protected function audiographPayload(int $value): array
    {
        return [
            'R_250' => $value,
            'L_250' => $value,
            'bone_R250' => $value - 5,
            'bone_L250' => $value - 5,
            'R_500' => $value,
            'L_500' => $value,
            'bone_R500' => $value - 5,
            'bone_L500' => $value - 5,
            'R_1k' => $value,
            'L_1k' => $value,
            'bone_R1k' => $value - 5,
            'bone_L1k' => $value - 5,
            'R_2k' => $value,
            'L_2k' => $value,
            'bone_R2k' => $value - 5,
            'bone_L2k' => $value - 5,
            'R_3k' => $value,
            'L_3k' => $value,
            'bone_R3k' => $value - 5,
            'bone_L3k' => $value - 5,
            'R_4k' => $value,
            'L_4k' => $value,
            'bone_R4k' => $value - 5,
            'bone_L4k' => $value - 5,
            'R_6k' => $value,
            'L_6k' => $value,
            'bone_R6k' => $value - 5,
            'bone_L6k' => $value - 5,
            'R_8k' => $value,
            'L_8k' => $value,
            'bone_R8k' => $value - 5,
            'bone_L8k' => $value - 5,
        ];
    }

    protected function upsertRow(string $table, string $primaryKey, array $identity, array $values): int
    {
        $query = DB::table($table);
        foreach ($identity as $column => $value) {
            $query->where($column, $value);
        }

        $existing = $query->first();
        if ($existing) {
            DB::table($table)
                ->where($primaryKey, $existing->{$primaryKey})
                ->update($values);

            return (int) $existing->{$primaryKey};
        }

        return (int) DB::table($table)->insertGetId(array_merge($identity, $values));
    }

    protected function audioCommentsPrimaryKey(): string
    {
        foreach (['audioComments_id', 'audioComment_id', 'audio_comment_id', 'id'] as $column) {
            if (Schema::hasColumn('audio_comments', $column)) {
                return $column;
            }
        }

        return 'audioComments_id';
    }
}
