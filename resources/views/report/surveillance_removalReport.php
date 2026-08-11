<?php
declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

$esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$query = request();
$declarationId = (int) $query->query('declaration_id', 0);
$employeeId = (int) $query->query('employee_id', 0);
$companyId = (int) $query->query('company_id', 0);
$surveillanceId = (int) $query->query('surveillance_id', 0);
$folderDate = trim((string) $query->query('folder_date', ''));
$createMode = (bool) $query->query('create_mode', false);
$viewMode = (bool) $query->query('view', false);
$printMode = (bool) $query->query('print', false);
$pdfDownloadMode = (bool) ($pdfDownloadMode ?? false);

$candidatePatientRows = collect();
if ($companyId > 0 && $folderDate !== '' && DB::getSchemaBuilder()->hasTable('declaration') && DB::getSchemaBuilder()->hasTable('chemical_information')) {
    $candidatePatientRows = DB::table('declaration')
        ->join('employee', 'employee.employee_id', '=', 'declaration.employee_id')
        ->join('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
        ->leftJoin('recommendation', 'recommendation.surveillance_id', '=', 'declaration.surveillance_id')
        ->where('declaration.company_id', $companyId)
        ->where('chemical_information.examination_date', $folderDate)
        ->select([
            'declaration.declaration_id',
            'declaration.employee_id',
            'declaration.company_id',
            'declaration.surveillance_id',
            'employee.employee_firstName',
            'employee.employee_lastName',
            'chemical_information.chemicals',
            'chemical_information.examination_date',
            'recommendation.recommencation_type',
        ])
        ->orderBy('employee.employee_firstName')
        ->orderBy('employee.employee_lastName')
        ->get();
}

$selectedPatientOption = $candidatePatientRows->first(function ($row) use ($declarationId, $employeeId, $surveillanceId) {
    return ((int) ($row->declaration_id ?? 0) === $declarationId && $declarationId > 0)
        || ((int) ($row->employee_id ?? 0) === $employeeId && $employeeId > 0)
        || ((int) ($row->surveillance_id ?? 0) === $surveillanceId && $surveillanceId > 0);
});

$declaration = $declarationId > 0 && DB::getSchemaBuilder()->hasTable('declaration')
    ? DB::table('declaration')->where('declaration_id', $declarationId)->first()
    : null;
if (! $declaration && DB::getSchemaBuilder()->hasTable('declaration')) {
    $declaration = DB::table('declaration')
        ->when($employeeId > 0, fn ($builder) => $builder->where('employee_id', $employeeId))
        ->when($companyId > 0, fn ($builder) => $builder->where('company_id', $companyId))
        ->when($surveillanceId > 0, fn ($builder) => $builder->where('surveillance_id', $surveillanceId))
        ->orderByDesc('declaration_id')
        ->first();
}

$surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
$employeeId = (int) ($declaration->employee_id ?? $employeeId);
$companyId = (int) ($declaration->company_id ?? $companyId);

$employee = $employeeId > 0 ? DB::table('employee')->where('employee_id', $employeeId)->first() : null;
$company = $companyId > 0 ? DB::table('company')->where('company_id', $companyId)->first() : null;
$activeClinicId = (int) request()->session()->get('active_clinic_id', 0);
$clinicRecord = $activeClinicId > 0 && DB::getSchemaBuilder()->hasTable('clinic')
    ? DB::table('clinic')->where('clinic_id', $activeClinicId)->first()
    : null;
$chemical = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('chemical_information')
    ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
    : null;
$recommendation = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('recommendation')
    ? DB::table('recommendation')->where('surveillance_id', $surveillanceId)->first()
    : null;
$summary = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('summary_report')
    ? DB::table('summary_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$findings = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('ms_findings')
    ? DB::table('ms_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$clinicalFindings = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('clinical_findings')
    ? DB::table('clinical_findings')->where('surveillance_id', $surveillanceId)->first()
    : null;
$targetOrgan = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('target_organ')
    ? DB::table('target_organ')->where('surveillance_id', $surveillanceId)->first()
    : null;
$biological = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('biological_monitoring')
    ? DB::table('biological_monitoring')->where('surveillance_id', $surveillanceId)->first()
    : null;
$removal = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('removal_report')
    ? DB::table('removal_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$doctor = ! empty($declaration->doctor_id) && DB::getSchemaBuilder()->hasTable('doctor')
    ? DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first()
    : null;

$occupationalRows = $employeeId > 0 && DB::getSchemaBuilder()->hasTable('occupational_history')
    ? DB::table('occupational_history')->where('employee_id', $employeeId)->orderBy('occupHistory_id')->get()
    : collect();
$occupationalCurrent = $occupationalRows->first(function ($row) use ($company) {
    return strcasecmp(trim((string) ($row->company_name ?? '')), trim((string) ($company->company_name ?? ''))) === 0;
}) ?: $occupationalRows->first();

$showValue = static function ($value, string $fallback = '-'): string {
    $value = trim((string) ($value ?? ''));
    return $value !== '' ? $value : $fallback;
};

$parseReportDate = static function ($value): ?Carbon {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return null;
    }

    foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
        try {
            return Carbon::createFromFormat($format, $value);
        } catch (\Throwable) {
        }
    }

    try {
        return Carbon::parse($value);
    } catch (\Throwable) {
        return null;
    }
};

$formatDate = static function ($value) use ($parseReportDate): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '-';
    }

    $parsedDate = $parseReportDate($value);
    return $parsedDate ? $parsedDate->format('d/m/Y') : $value;
};
$formatInputDate = static function ($value) use ($parseReportDate): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '';
    }

    $parsedDate = $parseReportDate($value);
    return $parsedDate ? $parsedDate->format('Y-m-d') : '';
};
$formatRoundedDuration = static function ($value, string $fallback = '-'): string {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return $fallback;
    }

    if (is_numeric($value)) {
        $numericValue = (float) $value;
        $wholeMonths = (int) floor($numericValue);
        $days = (int) round(($numericValue - $wholeMonths) * 30);

        if ($days === 30) {
            $wholeMonths++;
            $days = 0;
        }

        $parts = [];
        $parts[] = $wholeMonths . ' month' . ($wholeMonths === 1 ? '' : 's');
        if ($days > 0) {
            $parts[] = $days . ' day' . ($days === 1 ? '' : 's');
        }

        return implode(', ', $parts);
    }

    if (preg_match('/^\d+\.\d+\s*months?$/i', $value) === 1) {
        return $formatRoundedDuration((string) preg_replace('/\s*months?$/i', '', $value), $fallback);
    }

    return $value;
};

$companyAddressLineOne = trim((string) ($company->company_address ?? ''));
$companyAddressLineTwo = collect([
    trim((string) ($company->company_postcode ?? '')),
    trim((string) ($company->company_district ?? '')),
])->filter(static fn ($value) => $value !== '')->implode(', ');
$companyAddressLineThree = collect([
    trim((string) ($company->company_state ?? '')),
    'Malaysia',
])->filter(static fn ($value) => $value !== '')->implode(', ');
$companyAddress = collect([
    trim((string) ($company->company_name ?? '')),
    $companyAddressLineOne,
    $companyAddressLineTwo,
    $companyAddressLineThree,
])->filter(static fn ($value) => $value !== '')->implode("\n");

$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorName = $doctorName !== '' ? $doctorName : trim((string) ($doctor->doctor_username ?? 'Doctor'));
$doctorAddress = collect([
    trim((string) ($doctor->doctor_address ?? '')),
    trim((string) ($doctor->doctor_postcode ?? '')),
    trim((string) ($doctor->doctor_district ?? '')),
    trim((string) ($doctor->doctor_state ?? '')),
])->filter(static fn ($value) => $value !== '')->implode(', ');
$clinicAddress = collect([
    trim((string) ($clinicRecord->clinic_name ?? '')),
    trim((string) ($clinicRecord->clinic_address ?? '')),
    collect([
        trim((string) ($clinicRecord->clinic_postcode ?? '')),
        trim((string) ($clinicRecord->clinic_district ?? '')),
    ])->filter(static fn ($value) => $value !== '')->implode(' '),
    trim((string) ($clinicRecord->clinic_state ?? '')),
])->filter(static fn ($value) => $value !== '')->implode(', ');

$workerName = trim((string) (($employee->employee_firstName ?? '') . ' ' . ($employee->employee_lastName ?? '')));
$identityNo = trim((string) (($employee->employee_NRIC ?? '') !== '' ? ($employee->employee_NRIC ?? '') : ($employee->employee_passportNo ?? '')));
$examDateRaw = trim((string) ($chemical->examination_date ?? $declaration->doctor_date ?? ''));
$chemicalName = trim((string) ($chemical->chemicals ?? ''));
$jobTitle = trim((string) ($occupationalCurrent->job_title ?? ''));
$workUnit = trim((string) ($summary->name_of_workUnit ?? ''));
$employmentDuration = trim((string) ($occupationalCurrent->employment_duration ?? ''));
$employmentDurationDisplay = $formatRoundedDuration($employmentDuration, 'Not recorded');
$reviewDateRaw = trim((string) ($recommendation->nextReview_date ?? $recommendation->MRPdate_end ?? ''));
$doctorSignature = trim((string) ($doctor->doctor_sign ?? $declaration->doctor_signature ?? ''));
$toSignatureDataUrl = static function ($value) use ($pdfDownloadMode) {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '';
    }
    if (str_starts_with($value, 'data:image') || str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
        if ($pdfDownloadMode && str_starts_with($value, '/')) {
            $localPath = public_path(ltrim($value, '/\\'));
            return is_file($localPath) ? $localPath : $value;
        }
        return $value;
    }

    if ($pdfDownloadMode) {
        $localPath = public_path(ltrim($value, '/\\'));
        return is_file($localPath) ? $localPath : $value;
    }

    return function_exists('asset') ? asset($value) : $value;
};
$statusMessage = (string) session('status', '');
$hasSelectedPatientRecord = $declarationId > 0 && $employeeId > 0 && $surveillanceId > 0;

$startEmployment = '-';
if ($employmentDuration !== '' && $examDateRaw !== '') {
    try {
        $examDate = $parseReportDate($examDateRaw);
        if (! $examDate) {
            throw new \RuntimeException('Unable to parse examination date.');
        }
        if (preg_match('/(\d+)\s*year/i', $employmentDuration, $match) === 1) {
            $startEmployment = $examDate->copy()->subYears((int) $match[1])->format('d/m/Y');
        } elseif (preg_match('/(\d+)\s*month/i', $employmentDuration, $match) === 1) {
            $startEmployment = $examDate->copy()->subMonths((int) $match[1])->format('d/m/Y');
        }
    } catch (\Throwable) {
        $startEmployment = '-';
    }
}

$mrpMonths = '-';
if (! empty($recommendation->MRPdate_start) && ! empty($recommendation->MRPdate_end)) {
    try {
        $mrpStartDate = $parseReportDate((string) $recommendation->MRPdate_start);
        $mrpEndDate = $parseReportDate((string) $recommendation->MRPdate_end);
        if (! $mrpStartDate || ! $mrpEndDate) {
            throw new \RuntimeException('Unable to parse MRP dates.');
        }
        $totalDays = $mrpStartDate->diffInDays($mrpEndDate);
        $wholeMonths = intdiv($totalDays, 30);
        $remainingDays = $totalDays % 30;
        $mrpDurationParts = [];
        $mrpDurationParts[] = $wholeMonths . ' month' . ($wholeMonths === 1 ? '' : 's');
        if ($remainingDays > 0) {
            $mrpDurationParts[] = $remainingDays . ' day' . ($remainingDays === 1 ? '' : 's');
        }
        $mrpMonths = implode(', ', $mrpDurationParts);
    } catch (\Throwable) {
        $mrpMonths = '-';
    }
}

$targetResultValue = static function (?object $targetOrganRecord, string $resultColumn): string {
    if (! $targetOrganRecord) {
        return '';
    }

    return trim((string) ($targetOrganRecord->{$resultColumn} ?? ''));
};
$targetOrganAbnormal = false;
$targetOrganNotes = [];
foreach ([
    ['result_column' => 'blood_count_result', 'label' => 'Full Blood Count'],
    ['result_column' => 'renal_function_result', 'label' => 'Renal Function Test'],
    ['result_column' => 'liver_function_result', 'label' => 'Liver Function Test'],
    ['result_column' => 'chest_xray_result', 'label' => 'Chest X-ray'],
] as $field) {
    if ($targetResultValue($targetOrgan, $field['result_column']) === 'Abnormal') {
        $targetOrganAbnormal = true;
        $targetOrganNotes[] = $field['label'];
    }
}
if (! empty($targetOrgan->spirometry_comments) || (! empty($targetOrgan->spirometry_FEV1) || ! empty($targetOrgan->spirometry_FVC) || ! empty($targetOrgan->spirometry_FEV_FVC))) {
    $targetOrganNotes[] = 'Spirometry';
}
$otherTargetTests = [];
if ($surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('target_organ_other_tests')) {
    $otherTargetTests = DB::table('target_organ_other_tests')
        ->where('surveillance_id', $surveillanceId)
        ->orderBy('sort_order')
        ->orderBy('other_target_test_id')
        ->get(['test_name', 'result', 'comments'])
        ->map(static fn ($row) => [
            'name' => trim((string) ($row->test_name ?? '')),
            'result' => trim((string) ($row->result ?? '')),
            'comments' => trim((string) ($row->comments ?? '')),
        ])
        ->filter(static fn ($row) => $row['name'] !== '' || $row['result'] !== '' || $row['comments'] !== '')
        ->values()
        ->all();
}
if ($otherTargetTests === [] && ! empty($targetOrgan->other_tests ?? null)) {
    $decodedOtherTargetTests = json_decode((string) $targetOrgan->other_tests, true);
    if (is_array($decodedOtherTargetTests)) {
        $otherTargetTests = $decodedOtherTargetTests;
    }
}
if ($otherTargetTests !== []) {
    foreach ($otherTargetTests as $otherTargetTest) {
        $testName = trim((string) ($otherTargetTest['name'] ?? ''));
        $testResult = trim((string) ($otherTargetTest['result'] ?? ''));
        if ($testName !== '' && $testResult === 'Abnormal') {
            $targetOrganAbnormal = true;
            $targetOrganNotes[] = $testName;
        }
    }
}

$recommendationReasons = [];
if (($findings->pregnancy_breastFeding ?? null) === 'Yes') {
    $recommendationReasons[] = ['label' => 'Pregnancy / Breastfeeding concern', 'detail' => 'Recorded during MS findings.'];
}
if (($findings->biological_monitoring ?? null) === 'Yes') {
    $recommendationReasons[] = ['label' => 'Abnormal BM/BEM result', 'detail' => $showValue($biological->baseline_annual ?? $biological->baseline_results ?? null)];
}
if (($findings->clinical_findings ?? null) === 'Yes' || ($clinicalFindings->result_clinical_findings ?? null) === 'Yes') {
    $recommendationReasons[] = ['label' => 'Adverse health effects based on clinical findings', 'detail' => $showValue($clinicalFindings->elaboration ?? null, 'Recorded in examination findings')];
}
if (($findings->target_organ ?? null) === 'Yes' || $targetOrganAbnormal) {
    $recommendationReasons[] = ['label' => 'Target organ function test abnormality', 'detail' => $targetOrganNotes !== [] ? implode(', ', $targetOrganNotes) : 'Recorded in target organ assessment'];
}
$otherReason = trim((string) ($recommendation->notes ?? $removal->reasons_recommendations ?? ''));
if ($otherReason !== '') {
    $recommendationReasons[] = ['label' => 'Other follow-up note', 'detail' => $otherReason];
}

$removalType = trim((string) ($removal->removal_type ?? ''));
$screenRemovalType = old('removal_type', $removalType);
$allowedReasonKeys = [
    'pregnancy',
    'breastfeeding',
    'abnormal_bm_bem',
    'adverse_clinical_findings',
    'target_organ_abnormality',
    'other',
];
$removalReasonOptions = [
    'pregnancy' => 'Pregnancy',
    'breastfeeding' => 'Breastfeeding',
    'abnormal_bm_bem' => 'Abnormal BM/BEM result',
    'adverse_clinical_findings' => 'Adverse health effects based on clinical findings',
    'target_organ_abnormality' => 'Target organ function test abnormality',
    'other' => 'Specify others',
];
$storedRemovalReasonSelection = [];
$storedRemovalReasonOther = '';
if (! empty($removal->reasons_recommendations)) {
    $decodedStoredReasons = json_decode((string) $removal->reasons_recommendations, true);
    if (is_array($decodedStoredReasons)) {
        $storedRemovalReasonSelection = array_values(array_filter(
            array_map(static fn ($value) => trim((string) $value), (array) ($decodedStoredReasons['selected'] ?? [])),
            static fn ($value) => $value !== '' && in_array($value, $allowedReasonKeys, true)
        ));
        $storedRemovalReasonOther = trim((string) ($decodedStoredReasons['other'] ?? ''));
    }
}
$screenSelectedReasons = old('recommendation_reasons', $storedRemovalReasonSelection);
$screenSelectedReasons = array_values(array_filter(
    array_map(static fn ($value) => trim((string) $value), (array) $screenSelectedReasons),
    static fn ($value) => $value !== '' && in_array($value, $allowedReasonKeys, true)
));
$screenReasonOther = old('recommendation_reason_other', $storedRemovalReasonOther);
$storedRemovalIdentityNo = trim((string) ($removal->worker_identity_no ?? ''));
$storedRemovalDateOfBirth = trim((string) ($removal->worker_date_of_birth ?? ''));
$storedRemovalSex = trim((string) ($removal->worker_sex ?? ''));
$storedRemovalCompanyAddress = trim((string) ($removal->company_name_address ?? ''));
$storedRemovalEmploymentStartDate = trim((string) ($removal->employment_start_date ?? ''));
$storedRemovalEmploymentDuration = trim((string) ($removal->employment_duration_text ?? ''));
$storedRemovalHealthHazard = trim((string) ($removal->health_hazard_present ?? ''));
$storedRemovalWorkUnitDepartment = trim((string) ($removal->work_unit_department ?? ''));
$storedRemovalDoctorAddress = trim((string) ($removal->doctor_practice_address ?? ''));
$storedRemovalDoctorEmail = trim((string) ($removal->doctor_email_address ?? ''));
$storedRemovalDoctorTelephone = trim((string) ($removal->doctor_telephone ?? ''));
$storedRemovalDoctorFax = trim((string) ($removal->doctor_fax ?? ''));

$editableIdentityNo = old('worker_identity_no', $storedRemovalIdentityNo !== '' ? $storedRemovalIdentityNo : $identityNo);
$editableDateOfBirth = old('worker_date_of_birth', $formatInputDate($storedRemovalDateOfBirth !== '' ? $storedRemovalDateOfBirth : (string) ($employee->employee_DOB ?? '')));
$editableSex = old('worker_sex', $storedRemovalSex !== '' ? $storedRemovalSex : (string) ($employee->employee_gender ?? ''));
$editableCompanyAddress = old('company_name_address', $storedRemovalCompanyAddress !== '' ? $storedRemovalCompanyAddress : $companyAddress);
$editableEmploymentStartDate = old('employment_start_date', $formatInputDate($storedRemovalEmploymentStartDate !== '' ? $storedRemovalEmploymentStartDate : $startEmployment));
$editableEmploymentDuration = old('employment_duration_text', $storedRemovalEmploymentDuration !== '' ? $storedRemovalEmploymentDuration : $employmentDurationDisplay);
$editableHealthHazard = old('health_hazard_present', $storedRemovalHealthHazard !== '' ? $storedRemovalHealthHazard : $chemicalName);
$editableWorkUnitDepartment = old('work_unit_department', $storedRemovalWorkUnitDepartment !== '' ? $storedRemovalWorkUnitDepartment : $workUnit);
$editableDoctorAddress = old('doctor_practice_address', $storedRemovalDoctorAddress !== '' ? $storedRemovalDoctorAddress : ($clinicAddress !== '' ? $clinicAddress : $doctorAddress));
$editableDoctorEmail = old('doctor_email_address', $storedRemovalDoctorEmail !== '' ? $storedRemovalDoctorEmail : (string) ($doctor->doctor_email ?? ''));
$editableDoctorTelephone = old('doctor_telephone', $storedRemovalDoctorTelephone !== '' ? $storedRemovalDoctorTelephone : (string) ($doctor->doctor_telephone ?? ''));
$editableDoctorFax = old('doctor_fax', $storedRemovalDoctorFax !== '' ? $storedRemovalDoctorFax : (string) ($doctor->doctor_fax ?? ''));
$editableCompanyDisplayName = trim((string) preg_split('/\r\n|\r|\n/', $editableCompanyAddress)[0]);
$editableCompanyAddressLines = array_values(array_filter(
    array_map(static fn ($line) => trim((string) $line), preg_split('/\r\n|\r|\n/', $editableCompanyAddress) ?: []),
    static fn ($line) => $line !== ''
));
$printCompanyName = $editableCompanyDisplayName !== '' ? $editableCompanyDisplayName : trim((string) ($company->company_name ?? ''));
$fallbackCompanyAddress = collect([
    trim((string) ($company->company_address ?? '')),
    collect([
        trim((string) ($company->company_postcode ?? '')),
        trim((string) ($company->company_district ?? '')),
    ])->filter(static fn ($value) => $value !== '')->implode(' '),
    collect([
        trim((string) ($company->company_state ?? '')),
        'Malaysia',
    ])->filter(static fn ($value) => $value !== '')->implode(', '),
])->filter(static fn ($value) => $value !== '')->implode(', ');
$printCompanyAddress = trim(implode(', ', array_slice($editableCompanyAddressLines, 1)));
if ($printCompanyAddress === '' || count($editableCompanyAddressLines) <= 2) {
    $printCompanyAddress = $fallbackCompanyAddress;
}
$doctorPracticeDate = Carbon::now()->format('d/m/Y');
$fieldDisabled = $viewMode ? ' disabled' : '';
$doctorSignatureSrc = $toSignatureDataUrl($doctorSignature);
$printDoctorAddress = trim($editableDoctorAddress) !== '' ? $editableDoctorAddress : ($doctorAddress !== '' ? $doctorAddress : $clinicAddress);
$printDoctorEmail = trim($editableDoctorEmail) !== '' ? $editableDoctorEmail : trim((string) (($doctor->doctor_email ?? '') !== '' ? $doctor->doctor_email : ($clinicRecord->clinic_email ?? '')));
$printDoctorTelephone = trim($editableDoctorTelephone) !== '' ? $editableDoctorTelephone : trim((string) (($doctor->doctor_telephone ?? '') !== '' ? $doctor->doctor_telephone : ($clinicRecord->clinic_telephone ?? '')));
$printDoctorFax = trim($editableDoctorFax) !== '' ? $editableDoctorFax : trim((string) (($doctor->doctor_fax ?? '') !== '' ? $doctor->doctor_fax : ($clinicRecord->clinic_fax ?? '')));
$selectedReasonLabels = array_values(array_map(static fn ($key) => $removalReasonOptions[$key] ?? $key, $screenSelectedReasons));
$selectedReasonLabels = array_values(array_filter($selectedReasonLabels, static fn ($value) => trim((string) $value) !== ''));

if ($createMode && ! session()->hasOldInput() && $declarationId <= 0) {
    $screenRemovalType = '';
    $screenSelectedReasons = [];
    $screenReasonOther = '';
    $editableIdentityNo = '';
    $editableDateOfBirth = '';
    $editableSex = '';
    $editableCompanyAddress = '';
    $editableEmploymentStartDate = '';
    $editableEmploymentDuration = '';
    $editableHealthHazard = '';
    $editableWorkUnitDepartment = '';
    $editableDoctorAddress = $clinicAddress !== '' ? $clinicAddress : $doctorAddress;
    $editableDoctorEmail = '';
    $editableDoctorTelephone = '';
    $editableDoctorFax = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $esc('USECHH5i - ' . $showValue($workerName, 'Patient')); ?></title>
</head>
<body>
<style>
@page{size:A4 portrait;margin:12mm 15mm}
body{margin:0;padding:0;background:#fff;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.4;overflow-x:hidden}
.sheet{display:grid;gap:12px}
.a4-page{width:100%;max-width:100%;box-sizing:border-box;overflow-x:hidden}
.avoid-break{break-inside:avoid;page-break-inside:avoid}
.page-break{break-before:page;page-break-before:always}
.no-print{display:block}
.clinic-report-header{padding:0 0 14px;width:100%}
.clinic-report-header img{display:block;width:100%;max-width:none;max-height:none;height:auto;object-fit:fill}
.report-card{background:#fff;overflow:hidden;max-width:100%}
.report-head{padding:10px 0 24px;border-bottom:0}
.report-head-top{position:relative;display:block;text-align:center}
.report-code{position:absolute;right:0;top:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.report-head-act{font-size:14px;font-weight:700;line-height:1.35}
.report-head-regulation{margin-top:4px;font-size:15px;font-weight:700;line-height:1.35}
.report-title{margin:14px 0 0;text-align:center;font-size:20px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
.section{padding:14px 0;border-top:1px solid #edf2ee}
.section:first-of-type{border-top:0}
.section.print-only{border-top:0}
.toggle-form{display:grid;gap:18px;min-width:0}
.toggle-label{font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64756b}
.selector-shell{display:grid;gap:12px;padding:0}
.selector-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:end}
.selector-field{display:grid;gap:8px}
.selector-field label{font-size:.92rem;font-weight:600;color:#0f172a}
.selector-select{display:block;width:100%;box-sizing:border-box;border:1px solid #d1d5db;border-radius:10px;padding:12px 14px;font:inherit;background:#fff;color:#0f172a}
.selector-submit{display:inline-flex;align-items:center;justify-content:center;height:46px;padding:0 18px;border:1px solid #2f9e44;border-radius:10px;background:#2f9e44;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.selector-note{font-size:.88rem;line-height:1.65;color:#64756b}
.editor-shell{display:grid;gap:16px;min-width:0;width:100%;max-width:100%;padding:0 24px 24px;box-sizing:border-box}
.digital-form{padding:0;display:grid;gap:22px;min-width:0;width:100%;max-width:100%}
.digital-form-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:0}
.digital-form-title{margin:0;font-size:22px;font-weight:700;letter-spacing:.02em;color:#0f172a}
.section-divider{padding-top:8px}
.protection-choice{display:flex;align-items:center;justify-content:center;gap:20px;flex-wrap:wrap}
.protection-radio{display:inline-flex;align-items:center;gap:10px;font-size:.95rem;font-weight:500;color:#1f2937;cursor:pointer}
.protection-radio input[type="radio"]{width:18px;height:18px;accent-color:#389B5B;flex-shrink:0}
.digital-panel{padding:0;min-width:0;width:100%;max-width:100%}
.digital-panel-title{margin:0 0 12px;font-size:1rem;font-weight:700;color:#0f172a}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px}
.form-grid.three{grid-template-columns:repeat(3,minmax(0,1fr))}
.form-field{display:grid;gap:8px;min-width:0}
.form-field.full{grid-column:1 / -1}
.form-label{font-size:.92rem;font-weight:600;color:#0f172a}
.form-value{display:block;width:100%;box-sizing:border-box;min-height:42px;padding:10px 14px;border:1px solid #d1d5db;border-radius:12px;background:#fff;font-size:.96rem;line-height:1.45;color:#0f172a}
.form-value.multiline{min-height:72px;white-space:pre-line}
.form-textarea{display:block;width:100%;max-width:100%;box-sizing:border-box;min-height:88px;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;font:inherit;resize:vertical;background:#fff;color:#0f172a}
.statement-copy{display:grid;gap:14px;font-size:1rem;line-height:1.8;color:#0f172a}
.statement-copy p{margin:0}
.statement-inline-input{display:inline-block;box-sizing:border-box;min-width:220px;max-width:100%;padding:8px 10px;border:1px solid #d1d5db;border-radius:10px;background:#fff;font:inherit;color:#0f172a;vertical-align:middle}
.statement-highlight{font-weight:600}
.reason-title{font-size:15px;font-weight:600}
.reason-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 24px}
.reason-option{display:flex;align-items:flex-start;gap:10px;padding:10px 12px;border:1px solid #dfe5ea;border-radius:14px;background:#fff}
.reason-option input[type="checkbox"]{width:18px;height:18px;margin-top:2px;accent-color:#389B5B;flex-shrink:0}
.reason-option span{font-size:.9rem;line-height:1.45;color:#1f2937}
.print-layout{display:grid;gap:14px}
.print-divider{height:1px;background:#355b66;margin:0}
.print-info-grid{display:grid;gap:12px}
.print-info-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px 42px}
.print-line{display:grid;grid-template-columns:190px 14px minmax(0,1fr);align-items:start;gap:10px;font-size:11px;line-height:1.45}
.print-line.full{grid-template-columns:190px 14px minmax(0,1fr)}
.print-line-label{font-weight:700}
.print-line-value{white-space:pre-line}
.print-copy{display:grid;gap:10px;font-size:11px;line-height:1.55}
.print-copy p{margin:0}
.print-section-title{font-size:15px;font-weight:700;letter-spacing:0;color:#111827}
.print-reasons-list{margin:0;padding-left:22px;font-size:11px;line-height:1.45}
.print-reasons-list li{margin:0 0 2px}
.print-signature-wrap{display:grid;justify-content:end;gap:8px;margin-top:2px}
.print-signature-wrap img{max-width:170px;max-height:48px;object-fit:contain;justify-self:end}
.print-meta{display:grid;gap:3px;justify-items:end;text-align:right;font-size:11px;line-height:1.35}
.print-meta strong{font-weight:700}
.print-note-bottom{margin-top:0;font-size:11px;line-height:1.35;color:#334155;text-align:center}
.print-footer-block{display:grid;gap:16px}
.print-note-footer{margin-top:auto;padding-top:10px}
.print-section-spacer{height:10px}
.pdf-download-render{font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.35;color:#111827}
.pdf-download-details{width:100%;border-collapse:collapse;margin-top:2px}
.pdf-download-details td{padding:4px 0;vertical-align:top;font-size:11px}
.pdf-download-label{width:165px;font-weight:700}
.pdf-download-sep{width:14px}
.pdf-download-value{width:auto}
.pdf-download-right-label{width:155px;font-weight:700;padding-left:28px}
.pdf-download-full-label{width:165px;font-weight:700}
.pdf-download-copy{margin-top:10px;font-size:11px;line-height:1.35}
.pdf-download-copy p{margin:0 0 6px}
.pdf-download-heading{margin:10px 0 5px;font-size:11px;font-weight:700;color:#111827}
.pdf-download-reasons{margin:0;padding-left:18px;font-size:11px;line-height:1.3}
.pdf-download-signature{margin-top:16px;text-align:right}
.pdf-download-signature img{max-width:185px;max-height:56px;object-fit:contain}
.pdf-download-signature-meta{margin-top:5px;font-size:11px;line-height:1.25}
.pdf-download-signature-meta strong{font-weight:700}
.pdf-download-note{margin-top:18px;font-size:8.8px;line-height:1.18;color:#334155;text-align:center}
.signature-row{display:grid;grid-template-columns:1fr 220px;gap:36px;align-items:end;padding-top:18px}
.signature-line{border-top:1.4px solid #334155;padding-top:6px;text-align:center;font-size:14px}
.digital-note{font-size:12.5px;line-height:1.55;color:#334155;text-align:center}
.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px 24px}
.detail-line{display:grid;grid-template-columns:170px 1fr;gap:12px;align-items:start;padding:6px 0}
.detail-line.full{grid-column:1 / -1}
.detail-label{font-size:.82rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71}
.detail-value{font-size:1rem;font-weight:500}
.narrative{line-height:1.8}
.narrative strong{font-weight:700}
.reason-table{width:100%;border-collapse:collapse}
.reason-table td{padding:8px 0;border-top:1px solid #edf2ee;vertical-align:top;font-size:1rem}
.reason-table tr:first-child td{border-top:0}
.reason-status{width:120px;font-weight:700;color:#1f5f35}
.reason-status.no{color:#64756b}
.signature-section{display:flex;justify-content:flex-end}
.signature-stack{display:grid;gap:12px;max-width:320px;text-align:right}
.sign-box{padding:0;min-height:0;text-align:right}
.sign-box img{display:block;max-width:220px;max-height:72px;object-fit:contain;margin:0 0 10px auto}
.doctor-meta{display:grid;gap:8px}
.meta-row{padding:2px 0}
.meta-row strong{display:block;font-size:.8rem;letter-spacing:.04em;text-transform:uppercase;color:#6b7d71;margin-bottom:4px}
.muted{color:#64756b}
.flash{margin:0 0 8px;padding:10px 14px;border:1px solid #cfe7d4;border-radius:12px;background:#f3fbf4;color:#1f5f35;font-size:.9rem}
.save-actions{display:flex;justify-content:flex-end;padding-top:4px}
.save-btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 18px;border:1px solid #2f9e44;border-radius:999px;background:#2f9e44;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.screen-only{display:block}
.print-only{display:none}
@media (max-width:900px){
.editor-shell{padding:0 16px 20px}
.selector-grid,
.form-grid,.form-grid.three,.reason-grid,.signature-row{grid-template-columns:1fr}
}
@media print{
html,
body{
width:210mm;
min-height:297mm;
margin:0;
padding:0;
}
body{
background:#fff;
font-family:Arial,Helvetica,sans-serif;
font-size:11px;
line-height:1.4;
-webkit-print-color-adjust:exact;
print-color-adjust:exact;
}
.sheet{gap:2px}
.a4-page{
width:100%;
box-sizing:border-box;
}
.avoid-break{
break-inside:avoid;
page-break-inside:avoid;
}
.page-break{
break-before:page;
page-break-before:always;
}
.no-print{
display:none!important;
}
.clinic-report-header{padding-bottom:18px;width:100%}
.clinic-report-header img{display:block;width:100%;max-width:none;max-height:none;height:auto;object-fit:fill}
.report-card{display:block;overflow:visible;break-inside:avoid}
.report-head{padding:4px 0 22px;border-bottom:0}
.report-code{font-size:11px}
.report-head-act{font-size:11px;line-height:1.35}
.report-head-regulation{margin-top:4px;font-size:11px;line-height:1.35}
.report-title{margin-top:16px;font-size:22px}
.section{padding:3px 0}
.section.print-only{border-top:0}
.print-layout{display:flex;flex-direction:column;min-height:232mm;gap:16px}
.print-info-grid{gap:14px}
.print-info-row{grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:18px 42px}
.print-line{grid-template-columns:150px 10px minmax(0,1fr);gap:8px;font-size:11px;line-height:1.42}
.print-line.full{grid-template-columns:150px 10px minmax(0,1fr)}
.print-copy{gap:10px;font-size:11px;line-height:1.45}
.print-section-title{font-size:14px;color:#111827}
.print-reasons-list{padding-left:22px;font-size:11px;line-height:1.4}
.print-reasons-list li{margin:0 0 2px}
.print-signature-wrap{gap:6px;margin-top:0}
.print-signature-wrap img{max-width:170px;max-height:46px}
.print-meta{gap:2px;font-size:11px;line-height:1.3}
.print-footer-block{margin-top:16px;gap:12px;padding-top:0}
.print-note-footer{margin-top:auto;padding-top:12px}
.print-note-bottom{margin-top:0;font-size:11px;line-height:1.32}
.print-section-spacer{height:10px}
.pdf-download-render{font-size:11px;line-height:1.35}
.pdf-download-details td{padding:3px 0;font-size:11px}
.pdf-download-label{width:155px}
.pdf-download-right-label{width:150px;padding-left:24px}
.pdf-download-full-label{width:155px}
.pdf-download-copy{margin-top:9px;font-size:11px;line-height:1.32}
.pdf-download-copy p{margin:0 0 5px}
.pdf-download-heading{margin:10px 0 5px;font-size:11px;color:#111827}
.pdf-download-reasons{font-size:10.8px;line-height:1.24}
.pdf-download-signature{margin-top:14px}
.pdf-download-signature img{max-width:180px;max-height:52px}
.pdf-download-signature-meta{font-size:10.5px;line-height:1.18}
.pdf-download-note{margin-top:16px;font-size:8.2px;line-height:1.1}
.detail-grid{gap:6px 16px}
.detail-line{grid-template-columns:160px 1fr;gap:8px;padding:2px 0}
.detail-label{font-size:.72rem}
.detail-value{font-size:.86rem}
.narrative{font-size:.86rem;line-height:1.45}
.reason-table td{padding:3px 0;font-size:.84rem}
.signature-grid{gap:14px}
.sign-box{min-height:96px}
.sign-box img{max-width:165px;max-height:48px;margin-bottom:6px}
.meta-row{padding:2px 0}
.meta-row strong{font-size:.72rem;margin-bottom:2px}
.screen-only{display:none!important}
.print-only{display:block}
    table{width:100%;font-size:9px;border-collapse:collapse}
    th{font-size:9px;font-weight:700}
}
</style>
<?php if ($printMode): ?>
<style>
    .screen-only{display:none!important}
    .print-only{display:block!important}
    body{padding:0;background:#fff}
</style>
<?php endif; ?>
<div class="sheet a4-page">
    <div class="print-only"><?php require __DIR__ . '/partials/clinic_header.php'; ?></div>

    <section class="report-card">
        <div class="report-head">
            <div class="report-head-top">
                <div class="report-code">USECHH 5i</div>
                <div class="report-head-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-head-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <h1 class="report-title">Medical Removal Protection</h1>
            </div>
        </div>

        <?php if ($statusMessage !== ''): ?>
            <div class="flash screen-only"><?php echo $esc($statusMessage); ?></div>
        <?php endif; ?>

        <div class="section screen-only no-print">
            <?php if ($createMode || $candidatePatientRows->isNotEmpty()): ?>
                <form id="usechh5iPatientSelectForm" method="get" action="<?php echo $esc(route('surveillance.report.removal')); ?>" style="display:none;">
                    <input type="hidden" name="create_mode" value="1">
                    <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
                    <input type="hidden" name="folder_date" value="<?php echo $esc($folderDate); ?>">
                    <input type="hidden" name="declaration_id" id="usechh5iDeclarationIdInput" value="<?php echo $esc((string) ($selectedPatientOption->declaration_id ?? '')); ?>">
                </form>
            <?php endif; ?>

            <form class="toggle-form" method="post" action="<?php echo $esc(route('surveillance.report.removal.save')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="declaration_id" value="<?php echo $esc((string) ($declaration->declaration_id ?? $declarationId)); ?>">
                <input type="hidden" name="employee_id" value="<?php echo $esc((string) $employeeId); ?>">
                <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
                <input type="hidden" name="surveillance_id" value="<?php echo $esc((string) $surveillanceId); ?>">
                <div class="editor-shell">
                    <div class="digital-form">
                        <div class="digital-form-head">
                            <div class="digital-form-title">Medical Removal Protection</div>
                        </div>

                        <div class="digital-panel">
                            <h3 class="digital-panel-title">Removal Protection Type</h3>
                            <div class="protection-choice">
                                <label class="protection-radio">
                                    <input type="radio" name="removal_type" value="Temporary" <?php echo $screenRemovalType === 'Temporary' ? 'checked' : ''; ?><?php echo $fieldDisabled; ?>>
                                    <span>Temporary</span>
                                </label>
                                <label class="protection-radio">
                                    <input type="radio" name="removal_type" value="Permanent" <?php echo $screenRemovalType === 'Permanent' ? 'checked' : ''; ?><?php echo $fieldDisabled; ?>>
                                    <span>Permanent</span>
                                </label>
                            </div>
                        </div>

                        <div class="digital-panel section-divider">
                            <h3 class="digital-panel-title">Patient and Workplace Details</h3>
                            <div class="form-grid">
                                <div class="form-field">
                                    <div class="form-label">Patient Name</div>
                                    <?php if ($createMode || $candidatePatientRows->isNotEmpty()): ?>
                                        <select class="selector-select" id="usechh5iPatientSelect"<?php echo $fieldDisabled; ?>>
                                            <option value="">Select patient name</option>
                                            <?php foreach ($candidatePatientRows as $candidatePatientRow): ?>
                                                <?php
                                                $candidateName = trim((string) (($candidatePatientRow->employee_firstName ?? '') . ' ' . ($candidatePatientRow->employee_lastName ?? '')));
                                                $candidateSelected = (int) ($candidatePatientRow->declaration_id ?? 0) === (int) ($selectedPatientOption->declaration_id ?? 0);
                                                ?>
                                                <option value="<?php echo $esc((string) ($candidatePatientRow->declaration_id ?? '')); ?>"<?php echo $candidateSelected ? ' selected' : ''; ?>>
                                                    <?php echo $esc($candidateName); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <div class="form-value"><?php echo $esc($showValue($workerName)); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="form-field">
                                    <div class="form-label">NRIC / Passport No.</div>
                                    <input class="selector-select" type="text" name="worker_identity_no" value="<?php echo $esc($editableIdentityNo); ?>" placeholder="Enter NRIC / passport no."<?php echo $fieldDisabled; ?>>
                                </div>
                                <div class="form-field">
                                    <div class="form-label">Date of Birth</div>
                                    <input class="selector-select" type="date" name="worker_date_of_birth" value="<?php echo $esc($editableDateOfBirth); ?>"<?php echo $fieldDisabled; ?>>
                                </div>
                                <div class="form-field">
                                    <div class="form-label">Sex</div>
                                    <input class="selector-select" type="text" name="worker_sex" value="<?php echo $esc($editableSex); ?>" placeholder="Enter sex"<?php echo $fieldDisabled; ?>>
                                </div>
                                <div class="form-field full">
                                    <div class="form-label">Company Name and Address</div>
                                    <textarea class="form-textarea" name="company_name_address" placeholder="Enter company name and full address"<?php echo $fieldDisabled; ?>><?php echo $esc($editableCompanyAddress); ?></textarea>
                                </div>
                                <div class="form-field">
                                    <div class="form-label">Date of Starting Employment</div>
                                    <input class="selector-select" type="date" name="employment_start_date" value="<?php echo $esc($editableEmploymentStartDate); ?>"<?php echo $fieldDisabled; ?>>
                                </div>
                                <div class="form-field">
                                    <div class="form-label">Duration of Employment</div>
                                    <input class="selector-select" type="text" name="employment_duration_text" value="<?php echo $esc($editableEmploymentDuration); ?>" placeholder="Enter duration of employment"<?php echo $fieldDisabled; ?>>
                                </div>
                                <div class="form-field full">
                                    <div class="form-label">Health Hazard Present</div>
                                    <textarea class="form-textarea" name="health_hazard_present" placeholder="Enter health hazard present"<?php echo $fieldDisabled; ?>><?php echo $esc($editableHealthHazard); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="digital-panel section-divider">
                            <h3 class="digital-panel-title">Medical Removal Statement</h3>
                            <div class="statement-copy">
                                <p>
                                    I certify that the above named person examined by me on
                                    <span class="statement-highlight"><?php echo $esc($showValue($formatDate($examDateRaw))); ?></span>
                                    should not continue to work as
                                    <span class="statement-highlight"><?php echo $esc($showValue($jobTitle, 'the current assigned role')); ?></span>
                                    in
                                    <input class="statement-inline-input" type="text" name="work_unit_department" value="<?php echo $esc($editableWorkUnitDepartment); ?>" placeholder="Enter work unit / department"<?php echo $fieldDisabled; ?>>
                                    for
                                    <span class="statement-highlight"><?php echo $esc($showValue($mrpMonths)); ?></span>,
                                    subject to a review on
                                    <span class="statement-highlight"><?php echo $esc($showValue($formatDate($reviewDateRaw))); ?></span>.
                                </p>
                                <p>
                                    In the meantime, the worker should be given alternative work in another department or section which does not expose the worker to
                                    <span class="statement-highlight"><?php echo $esc($showValue($editableHealthHazard, 'the identified chemical hazard')); ?></span>.
                                </p>
                            </div>
                        </div>

                        <div class="digital-panel section-divider">
                            <div class="reason-title">The reasons for my recommendations are as follows (Please tick):</div>
                            <div class="reason-grid" style="margin-top:12px;">
                                <?php foreach ($removalReasonOptions as $reasonKey => $reasonLabel): ?>
                                    <label class="reason-option">
                                        <input type="checkbox" name="recommendation_reasons[]" value="<?php echo $esc($reasonKey); ?>"<?php echo in_array($reasonKey, $screenSelectedReasons, true) ? ' checked' : ''; ?><?php echo $fieldDisabled; ?>>
                                        <span><?php echo $esc($reasonLabel); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <div style="margin-top:14px;">
                                <div class="form-label" style="margin-bottom:8px;">Specify others</div>
                                <textarea class="form-textarea" name="recommendation_reason_other" placeholder="Insert other recommendation reason here..."<?php echo $fieldDisabled; ?>><?php echo $esc($screenReasonOther); ?></textarea>
                            </div>
                        </div>

                        <div class="digital-panel section-divider">
                            <h3 class="digital-panel-title">Occupational Health Doctor Details</h3>
                            <div class="form-grid three">
                                <div class="form-field full">
                                    <div class="form-label">Name of OHD</div>
                                    <div class="form-value"><?php echo $esc($doctorName); ?></div>
                                </div>
                                <div class="form-field full">
                                    <div class="form-label">Address of Practice</div>
                                    <textarea class="form-textarea" name="doctor_practice_address" placeholder="Enter address of practice"<?php echo $fieldDisabled; ?>><?php echo $esc($editableDoctorAddress); ?></textarea>
                                </div>
                                <div class="form-field">
                                    <div class="form-label">Email Address</div>
                                    <input class="selector-select" type="text" name="doctor_email_address" value="<?php echo $esc($editableDoctorEmail); ?>" placeholder="Enter email address"<?php echo $fieldDisabled; ?>>
                                </div>
                                <div class="form-field">
                                    <div class="form-label">Telephone</div>
                                    <input class="selector-select" type="text" name="doctor_telephone" value="<?php echo $esc($editableDoctorTelephone); ?>" placeholder="Enter telephone"<?php echo $fieldDisabled; ?>>
                                </div>
                                <div class="form-field">
                                    <div class="form-label">Fax</div>
                                    <input class="selector-select" type="text" name="doctor_fax" value="<?php echo $esc($editableDoctorFax); ?>" placeholder="Enter fax"<?php echo $fieldDisabled; ?>>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (! $viewMode): ?>
                        <div class="save-actions">
                            <button class="save-btn" type="submit" <?php echo $hasSelectedPatientRecord ? '' : 'disabled style="opacity:.55;cursor:not-allowed;"'; ?>>Save USECHH 5i</button>
                        </div>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="section print-only avoid-break">
            <?php if ($pdfDownloadMode): ?>
                <div class="pdf-download-render">
                    <table class="pdf-download-details">
                        <tr>
                            <td class="pdf-download-label">Patient Name</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value"><?php echo $esc($showValue($workerName)); ?></td>
                            <td class="pdf-download-right-label">NRIC / Passport No.</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value"><?php echo $esc($showValue($editableIdentityNo)); ?></td>
                        </tr>
                        <tr>
                            <td class="pdf-download-label">Date of Birth</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value"><?php echo $esc($showValue($editableDateOfBirth !== '' ? $formatDate($editableDateOfBirth) : '')); ?></td>
                            <td class="pdf-download-right-label">Sex</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value"><?php echo $esc($showValue($editableSex)); ?></td>
                        </tr>
                        <tr>
                            <td class="pdf-download-full-label">Company Name</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value" colspan="4"><?php echo $esc($showValue($printCompanyName)); ?></td>
                        </tr>
                        <tr>
                            <td class="pdf-download-full-label">Address</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value" colspan="4"><?php echo $esc($showValue($printCompanyAddress)); ?></td>
                        </tr>
                        <tr>
                            <td class="pdf-download-label">Date of Starting Employment</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value"><?php echo $esc($showValue($editableEmploymentStartDate !== '' ? $formatDate($editableEmploymentStartDate) : '')); ?></td>
                            <td class="pdf-download-right-label">Duration of Employment</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value"><?php echo $esc($showValue($editableEmploymentDuration)); ?></td>
                        </tr>
                        <tr>
                            <td class="pdf-download-label">Health Hazard Present</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value"><?php echo $esc($showValue($editableHealthHazard)); ?></td>
                            <td class="pdf-download-right-label">Removal Protection Type</td>
                            <td class="pdf-download-sep">:</td>
                            <td class="pdf-download-value"><?php echo $esc($showValue($screenRemovalType)); ?></td>
                        </tr>
                    </table>

                    <div style="height:16px;"></div>
                    <div class="pdf-download-copy">
                        <p>
                            I certify that the above named person examined by me on
                            <strong><?php echo $esc($showValue($formatDate($examDateRaw))); ?></strong>
                            should not continue to work as
                            <strong><?php echo $esc($showValue($jobTitle, 'the current assigned role')); ?></strong>
                            in
                            <strong><?php echo $esc($showValue($editableWorkUnitDepartment)); ?></strong>
                            for
                            <strong><?php echo $esc($showValue($mrpMonths)); ?></strong>,
                            subject to a review on
                            <strong><?php echo $esc($showValue($formatDate($reviewDateRaw))); ?></strong>.
                        </p>
                        <p>
                            In the meantime, the worker should be given alternative work in another department or section which does not expose the worker to
                            <strong><?php echo $esc($showValue($editableHealthHazard, 'the identified chemical hazard')); ?></strong>.
                        </p>
                    </div>

                    <div style="height:16px;"></div>
                    <div class="pdf-download-heading" style="font-size:11px;font-weight:700;color:#111827;margin:0 0 6px;">Reasons for Medical Removal</div>
                    <ul class="pdf-download-reasons">
                        <?php foreach ($selectedReasonLabels as $selectedReasonLabel): ?>
                            <li><?php echo $esc($selectedReasonLabel); ?></li>
                        <?php endforeach; ?>
                        <?php if (trim($screenReasonOther) !== ''): ?>
                            <li><?php echo $esc($screenReasonOther); ?></li>
                        <?php endif; ?>
                    </ul>

                    <div class="pdf-download-signature" style="margin-top:24px;text-align:right;">
                        <?php if ($doctorSignatureSrc !== ''): ?>
                            <img src="<?php echo $esc($doctorSignatureSrc); ?>" alt="Doctor signature" style="max-width:190px;max-height:60px;object-fit:contain;">
                        <?php endif; ?>
                        <div class="pdf-download-signature-meta" style="margin-top:6px;font-size:11px;line-height:1.25;">
                            <div><strong>Name of OHD</strong> <?php echo $esc($doctorName); ?></div>
                            <div><strong>OHD Signature Date</strong> <?php echo $esc($doctorPracticeDate); ?></div>
                            <div><strong>Address of Practice</strong> <?php echo $esc($showValue($printDoctorAddress, '-')); ?></div>
                            <div><strong>Telephone</strong> <?php echo $esc($showValue($printDoctorTelephone)); ?></div>
                            <div><strong>Email</strong> <?php echo $esc($showValue($printDoctorEmail)); ?></div>
                            <div><strong>Fax</strong> <?php echo $esc($showValue($printDoctorFax)); ?></div>
                        </div>
                    </div>

                    <div class="pdf-download-note" style="margin-top:110px;font-size:8.8px;line-height:1.18;text-align:center;">
                        Note: This certificate should be completed in triplicate and the original copy forwarded to the director General, department of Occupational Safety and Health. Putrajaya and must include the actual results of the relevant examination/tests. The quantitative results (e.g. blood lead) the exact Diagrams and measurements units must be clearly stated. Also include a copy of qualitative results (e.g Chest X-ray). Incomplete form will not be accepted.
                    </div>
                </div>
            <?php else: ?>
                <div class="print-layout">
                    <div class="print-info-grid">
                        <div class="print-info-row">
                            <div class="print-line"><div class="print-line-label">Patient Name</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($workerName)); ?></div></div>
                            <div class="print-line"><div class="print-line-label">NRIC / Passport No.</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($editableIdentityNo)); ?></div></div>
                        </div>
                        <div class="print-info-row">
                            <div class="print-line"><div class="print-line-label">Date of Birth</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($editableDateOfBirth !== '' ? $formatDate($editableDateOfBirth) : '')); ?></div></div>
                            <div class="print-line"><div class="print-line-label">Sex</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($editableSex)); ?></div></div>
                        </div>
                        <div class="print-line full"><div class="print-line-label">Company Name</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($printCompanyName)); ?></div></div>
                        <div class="print-line full"><div class="print-line-label">Address</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($printCompanyAddress)); ?></div></div>
                        <div class="print-info-row">
                            <div class="print-line"><div class="print-line-label">Date of Starting Employment</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($editableEmploymentStartDate !== '' ? $formatDate($editableEmploymentStartDate) : '')); ?></div></div>
                            <div class="print-line"><div class="print-line-label">Duration of Employment</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($editableEmploymentDuration)); ?></div></div>
                        </div>
                        <div class="print-info-row">
                            <div class="print-line"><div class="print-line-label">Health Hazard Present</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($editableHealthHazard)); ?></div></div>
                            <div class="print-line"><div class="print-line-label">Removal Protection Type</div><div>:</div><div class="print-line-value"><?php echo $esc($showValue($screenRemovalType)); ?></div></div>
                        </div>
                    </div>
                    <div class="print-section-spacer"></div>
                    <div class="print-copy">
                        <p>
                            I certify that the above named person examined by me on
                            <strong><?php echo $esc($showValue($formatDate($examDateRaw))); ?></strong>
                            should not continue to work as
                            <strong><?php echo $esc($showValue($jobTitle, 'the current assigned role')); ?></strong>
                            in
                            <strong><?php echo $esc($showValue($editableWorkUnitDepartment)); ?></strong>
                            for
                            <strong><?php echo $esc($showValue($mrpMonths)); ?></strong>,
                            subject to a review on
                            <strong><?php echo $esc($showValue($formatDate($reviewDateRaw))); ?></strong>.
                        </p>
                        <p>
                            In the meantime, the worker should be given alternative work in another department or section which does not expose the worker to
                            <strong><?php echo $esc($showValue($editableHealthHazard, 'the identified chemical hazard')); ?></strong>.
                        </p>
                    </div>
                    <div class="print-section-spacer"></div>
                    <div class="print-section-title">Reasons for Medical Removal</div>
                    <ul class="print-reasons-list">
                        <?php foreach ($selectedReasonLabels as $selectedReasonLabel): ?>
                            <li><?php echo $esc($selectedReasonLabel); ?></li>
                        <?php endforeach; ?>
                        <?php if (trim($screenReasonOther) !== ''): ?>
                            <li><?php echo $esc($screenReasonOther); ?></li>
                        <?php endif; ?>
                    </ul>
                    <div class="print-footer-block">
                        <div class="print-signature-wrap">
                            <?php if ($doctorSignatureSrc !== ''): ?>
                                <img src="<?php echo $esc($doctorSignatureSrc); ?>" alt="Doctor signature">
                            <?php endif; ?>
                            <div class="print-meta">
                                <div><strong>Name of OHD</strong> <?php echo $esc($doctorName); ?></div>
                                <div><strong>OHD Signature Date</strong> <?php echo $esc($doctorPracticeDate); ?></div>
                                <div><strong>Address of Practice</strong> <?php echo $esc($showValue($printDoctorAddress, '-')); ?></div>
                                <div><strong>Telephone</strong> <?php echo $esc($showValue($printDoctorTelephone)); ?></div>
                                <div><strong>Email</strong> <?php echo $esc($showValue($printDoctorEmail)); ?></div>
                                <div><strong>Fax</strong> <?php echo $esc($showValue($printDoctorFax)); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="print-note-footer">
                        <div class="print-note-bottom">
                            Note: This certificate should be completed in triplicate and the original copy forwarded to the director General, department of Occupational Safety and Health. Putrajaya and must include the actual results of the relevant examination/tests. The quantitative results (e.g. blood lead) the exact Diagrams and measurements units must be clearly stated. Also include a copy of qualitative results (e.g Chest X-ray). Incomplete form will not be accepted.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>
<?php if (($createMode || $candidatePatientRows->isNotEmpty()) && ! $viewMode): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var patientSelect = document.getElementById('usechh5iPatientSelect');
    var declarationInput = document.getElementById('usechh5iDeclarationIdInput');
    var patientForm = document.getElementById('usechh5iPatientSelectForm');
    if (!patientSelect || !declarationInput || !patientForm) {
        return;
    }
    patientSelect.addEventListener('change', function () {
        declarationInput.value = patientSelect.value || '';
        patientForm.submit();
    });
});
</script>
<?php endif; ?>
<?php if ($printMode): ?>
<script>
window.addEventListener('load', function () {
    window.print();
});
</script>
<?php endif; ?>
</body>
</html>
