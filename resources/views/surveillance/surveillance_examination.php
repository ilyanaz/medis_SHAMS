<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Surveillance Examination</title></head>
<body>
<?php
require dirname(__DIR__) . '/panel/navigation.php';
$esc = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$declarationId = $declarationId ?? $declaration->declaration_id ?? request()->query('declaration_id') ?? '';
$stepBack = function_exists('route') ? route('surveillance.declaration', array_filter(['company_id' => $selectedCompany->company_id ?? $declaration->company_id ?? request()->query('company_id') ?? null, 'employee_id' => $selectedEmployee->employee_id ?? $declaration->employee_id ?? request()->query('employee_id') ?? null, 'declaration_id' => $declarationId ?: null])) : '#';
$saveExamUrl = function_exists('route') ? route('surveillance.examination.save') : '#';
$selectedCompanyId = $selectedCompany->company_id ?? $declaration->company_id ?? request()->query('company_id') ?? '';
$selectedEmployeeId = $selectedEmployee->employee_id ?? $declaration->employee_id ?? request()->query('employee_id') ?? '';
$steps = [
    ['label' => 'Company', 'url' => function_exists('route') ? route('surveillance.company') : '#'],
    ['label' => 'Patient', 'url' => function_exists('route') ? route('surveillance.patient', ['company_id' => $selectedCompanyId]) : '#'],
    ['label' => 'Surveillance List', 'url' => function_exists('route') ? route('surveillance.list', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#'],
    ['label' => 'Declaration', 'url' => function_exists('route') ? route('surveillance.declaration', array_filter(['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId, 'declaration_id' => $declarationId ?: null])) : '#'],
    ['label' => 'Examination', 'url' => function_exists('route') ? route('surveillance.examination', array_filter(['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId, 'declaration_id' => $declarationId ?: null])) : '#', 'active' => true],
    ['label' => 'Report', 'url' => function_exists('route') ? route('surveillance.report', ['company_id' => $selectedCompanyId, 'employee_id' => $selectedEmployeeId]) : '#'],
];
$sectionStatuses = $sectionStatuses ?? [];
$patientFormData = $patientFormData ?? [];
$patientInfoName = trim((string) (($selectedEmployee->employee_firstName ?? '') . ' ' . ($selectedEmployee->employee_lastName ?? '')));
$patientInfoPhoneRaw = trim((string) ($selectedEmployee->employee_telephone ?? ''));
$patientInfoPhone = $patientInfoPhoneRaw !== '' ? $patientInfoPhoneRaw : '-';
$pageMode = $pageMode ?? 'create';
$isReadOnly = !empty($readOnly) || $pageMode === 'view';
$patientFormValue = static fn ($key, $default = '') => old($key, $patientFormData[$key] ?? $default);
$occupJobTitles = old('occup_job_title', $patientFormData['occup_job_title'] ?? []);
$occupCompanyNames = old('occup_company_name', $patientFormData['occup_company_name'] ?? []);
$startEmploymentDates = old('start_employment_date', $patientFormData['start_employment_date'] ?? []);
$employmentDurations = old('employment_duration', $patientFormData['employment_duration'] ?? []);
$chemicalExposureDurations = old('chemical_exposure_duration', $patientFormData['chemical_exposure_duration'] ?? []);
$chemicalExposureIncidents = old('chemical_exposure_incidents', $patientFormData['chemical_exposure_incidents'] ?? []);
$occupationalPastRowCount = max(
    count((array) $occupJobTitles),
    count((array) $occupCompanyNames),
    count((array) $startEmploymentDates),
    count((array) $employmentDurations),
    count((array) $chemicalExposureDurations),
    count((array) $chemicalExposureIncidents)
);
$occupationalPastRowCount = $isReadOnly ? $occupationalPastRowCount : max(1, $occupationalPastRowCount);
$patientSectionComplete = true;
foreach ([
    'diagnosed_history_status',
    'medication_history_status',
    'admitted_history_status',
    'family_history_status',
    'others_history_status',
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
] as $patientRequiredField) {
    if (trim((string) $patientFormValue($patientRequiredField)) === '') {
        $patientSectionComplete = false;
        break;
    }
}
if ($patientSectionComplete) {
    $smokingHistoryValue = trim((string) $patientFormValue('smoking_history'));
    if (in_array($smokingHistoryValue, ['Current', 'Ex-smoker'], true)) {
        $patientSectionComplete = trim((string) $patientFormValue('years_of_smoking')) !== ''
            && trim((string) $patientFormValue('no_of_cigarettes')) !== '';
    }
}
if ($patientSectionComplete && trim((string) $patientFormValue('vaping_history')) === 'Yes') {
    $patientSectionComplete = trim((string) $patientFormValue('years_of_vaping')) !== '';
}
$medicalHistoryRows = [
    ['label' => 'Diagnosed History', 'name' => 'diagnosed_history', 'placeholder' => 'Insert diagnosed history details'],
    ['label' => 'Medication History', 'name' => 'medication_history', 'placeholder' => 'Insert medication history details'],
    ['label' => 'Admitted History', 'name' => 'admitted_history', 'placeholder' => 'Insert admitted history details'],
    ['label' => 'Family History', 'name' => 'family_history', 'placeholder' => 'Insert family history details'],
    ['label' => 'Other History', 'name' => 'others_history', 'placeholder' => 'Insert other history details'],
];
$sectionStatuses['patient'] = $patientSectionComplete;
$historyOfHealth = $historyOfHealth ?? null;
$chemicalInfo = $chemicalInfo ?? null;
$clinicalFindings = $clinicalFindings ?? null;
$physicalExam = $physicalExam ?? null;
$targetOrgan = $targetOrgan ?? null;
$biologicalMonitoring = $biologicalMonitoring ?? null;
$fitnessRespirator = $fitnessRespirator ?? null;
$msFindings = $msFindings ?? null;
$recommendationData = $recommendationData ?? null;
$showRecordTabs = !empty($showRecordTabs);
$recordTabActive = $recordTabActive ?? 'examination';
$recordDeclarationUrl = !empty($declarationId) && function_exists('route')
    ? route('surveillance.declaration', array_filter([
        'company_id' => $selectedCompanyId ?: null,
        'employee_id' => $selectedEmployeeId ?: null,
        'declaration_id' => $declarationId ?: null,
        'record_mode' => 1,
    ]))
    : '#';
$recordExaminationUrl = !empty($declarationId) && function_exists('route')
    ? route($isReadOnly ? 'surveillance.record.view' : 'surveillance.record.edit', ['declaration' => $declarationId])
    : '#';
$chemicalOptions = $chemicalOptions ?? [];
if (empty($chemicalOptions)) {
    $chemicalOptions = [
        'Lead (Inorganic & Organic)',
        'Organophosphate pesticides',
        'Benzene',
        'Carbon Disulphide',
        'n-Hexane',
        'Trichloroethylene',
        'Arsenic (inorganic)',
        'Cadmium',
        'Chromium VI',
        'Mercury',
        'Nickel',
        'Manganese',
        'Toluene',
        'Xylene',
    ];
}
$sectionItems = [
    ['key' => 'patient', 'label' => 'Patient Information'],
    ['key' => 'chemical', 'label' => 'Chemical Information'],
    ['key' => 'history', 'label' => 'Health Effect History'],
    ['key' => 'clinical', 'label' => 'Clinical Findings'],
    ['key' => 'physical', 'label' => 'Physical Examination'],
    ['key' => 'target', 'label' => 'Target Organ Test'],
    ['key' => 'biological', 'label' => 'Biological Monitoring'],
    ['key' => 'respirator', 'label' => 'Respirator Fitness'],
    ['key' => 'findings', 'label' => 'MS Findings'],
    ['key' => 'recommendation', 'label' => 'Recommendation'],
];
$requestedSectionKey = trim((string) request()->query('section', ''));
$initialSectionIndex = 0;
if ($requestedSectionKey !== '') {
    foreach ($sectionItems as $sectionIndex => $sectionItem) {
        if (($sectionItem['key'] ?? '') === $requestedSectionKey) {
            $initialSectionIndex = $sectionIndex;
            break;
        }
    }
}
$recommendationOptions = [
    'Fit for work with no restriction',
    'Fit for work with restriction',
    'Annual medical surveillance',
    'Temporary Medical Removal Protection',
    'Permanent Medical Removal Protection',
    'Follow up and review',
    'Reinforce PPE and hygiene practices like stop smoking, job rotation and training',
];
$storedRecommendationRaw = (string) old('recommencation_type', $recommendationData->recommencation_type ?? '');
$storedRecommendationLines = array_values(array_filter(preg_split('/\r\n|\r|\n/', $storedRecommendationRaw) ?: [], static fn ($line) => trim((string) $line) !== ''));
$selectedRecommendationTypes = old('recommendation_types', []);
if (! is_array($selectedRecommendationTypes) || $selectedRecommendationTypes === []) {
    $selectedRecommendationTypes = [];
    foreach ($storedRecommendationLines as $recommendationLine) {
        $trimmedRecommendationLine = trim((string) $recommendationLine);
        if (stripos($trimmedRecommendationLine, 'Other:') === 0) {
            continue;
        }
        if (in_array($trimmedRecommendationLine, $recommendationOptions, true)) {
            $selectedRecommendationTypes[] = $trimmedRecommendationLine;
        }
    }
}
$selectedRecommendationTypes = array_values(array_unique(array_map(static fn ($value) => trim((string) $value), $selectedRecommendationTypes)));
$recommendationOtherValue = (string) old('recommendation_type_other', '');
if ($recommendationOtherValue === '') {
    foreach ($storedRecommendationLines as $recommendationLine) {
        $trimmedRecommendationLine = trim((string) $recommendationLine);
        if (stripos($trimmedRecommendationLine, 'Other:') === 0) {
            $recommendationOtherValue = trim(substr($trimmedRecommendationLine, 6));
            break;
        }
    }
}
$hasRecommendationOther = $recommendationOtherValue !== '';
$toSignatureDataUrl = static function ($value) {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '';
    }
    if (strpos($value, 'data:image') === 0) {
        return $value;
    }
    return 'data:image/png;base64,' . base64_encode($value);
};
$formatDateDisplay = static function ($value) {
    $value = trim((string) ($value ?? ''));
    if ($value === '') {
        return '';
    }
    try {
        return \Carbon\Carbon::parse($value)->format('d/m/Y');
    } catch (\Throwable $e) {
        return $value;
    }
};
$historyGroups = [
    'Respiratory & Cardiovascular' => ['breathing_difficulty' => 'Breathing difficulty', 'cough' => 'Cough', 'sore_throat' => 'Sore throat', 'sneezing' => 'Sneezing', 'chest_pain' => 'Chest pain', 'palpitation' => 'Palpitation', 'limb_oedema' => 'Limb oedema'],
    'Nervous System' => ['drowsiness' => 'Drowsiness', 'dizziness' => 'Dizziness', 'headache' => 'Headache', 'confusion' => 'Confusion', 'lethargy' => 'Lethargy', 'nausea' => 'Nausea', 'vomiting' => 'Vomiting'],
    'Skin & Eyes' => ['eye_irritations' => 'Eye irritations', 'blurred_vision' => 'Blurred vision', 'blisters' => 'Blisters', 'burns' => 'Burns', 'itching' => 'Itching', 'rash' => 'Rash', 'redness' => 'Redness'],
    'Gastrointestinal & Genitourinary' => ['abdominal_pain' => 'Abdominal pain', 'history_abdominal_mass' => 'Abdominal mass', 'history_jaundice' => 'Jaundice', 'diarrhoea' => 'Diarrhoea', 'loss_of_weight' => 'Loss of weight', 'loss_of_appetite' => 'Loss of appetite', 'dysuria' => 'Dysuria', 'haematuria' => 'Haematuria'],
];
$historyFieldColumnMap = [
    'history_abdominal_mass' => 'abdominal_mass',
    'history_jaundice' => 'jaundice',
];
$physicalFields = [
    ['name' => 's1_s2', 'label' => 'S1 & S2', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'murmur', 'label' => 'Murmur', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'ear_nose_throat', 'label' => 'Ear, Nose & Throat', 'type' => 'select', 'options' => ['Normal','Abnormal']],
    ['name' => 'visual_acuity_right', 'label' => 'Visual Acuity Right', 'type' => 'text'],
    ['name' => 'visual_acuity_left', 'label' => 'Visual Acuity Left', 'type' => 'text'],
    ['name' => 'colour_blindness', 'label' => 'Colour Blindness', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'gas_tenderness', 'label' => 'Gastro Tenderness', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'abdominal_mass', 'label' => 'Abdominal Mass', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'lymph_nodes', 'label' => 'Lymph Nodes', 'type' => 'select', 'options' => ['Palpable','Non-palpable']],
    ['name' => 'splenomegaly', 'label' => 'Splenomegaly', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'kidney_tenderness', 'label' => 'Kidney Tenderness', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'ballotable', 'label' => 'Ballotable', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'jaundice', 'label' => 'Jaundice', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'hepatomegaly', 'label' => 'Hepatomegaly', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'muscle_tone', 'label' => 'Muscle Tone', 'type' => 'select', 'options' => ['1','2','3','4','5']],
    ['name' => 'muscle_tenderness', 'label' => 'Muscle Tenderness', 'type' => 'select', 'options' => ['Yes','No']],
    ['name' => 'power', 'label' => 'Power', 'type' => 'select', 'options' => ['1','2','3','4','5']],
    ['name' => 'sensation', 'label' => 'Sensation', 'type' => 'select', 'options' => ['Normal','Abnormal']],
    ['name' => 'sound', 'label' => 'Respiratory Sound', 'type' => 'select', 'options' => ['Clear','Rhonchi','Crepitus']],
    ['name' => 'air_entry', 'label' => 'Air Entry', 'type' => 'select', 'options' => ['Normal','Abnormal']],
    ['name' => 'reproductive', 'label' => 'Reproductive', 'type' => 'select', 'options' => ['Normal','Abnormal']],
    ['name' => 'skin', 'label' => 'Skin', 'type' => 'select', 'options' => ['Normal','Abnormal']],
];
$otherTargetTests = $otherTargetTests ?? [];
if ($otherTargetTests === [] && ! empty($targetOrgan->other_tests ?? null)) {
    $decodedOtherTargetTests = json_decode((string) $targetOrgan->other_tests, true);
    if (is_array($decodedOtherTargetTests)) {
        $otherTargetTests = array_values(array_filter($decodedOtherTargetTests, static function ($row): bool {
            if (! is_array($row)) {
                return false;
            }

            return trim((string) ($row['name'] ?? '')) !== ''
                || trim((string) ($row['result'] ?? '')) !== ''
                || trim((string) ($row['comments'] ?? '')) !== '';
        }));
    }
}
$otherTargetTestNames = old('other_target_test_name', array_map(static fn ($row) => (string) ($row['name'] ?? ''), $otherTargetTests));
$otherTargetTestResults = old('other_target_test_result', array_map(static fn ($row) => (string) ($row['result'] ?? ''), $otherTargetTests));
$otherTargetTestComments = old('other_target_test_comments', array_map(static fn ($row) => (string) ($row['comments'] ?? ''), $otherTargetTests));
$fixedTargetResultValue = static function (string $resultColumn) use ($targetOrgan) {
    return old($resultColumn, $targetOrgan->{$resultColumn} ?? '');
};
$otherTargetTestRowCount = max(count((array) $otherTargetTestNames), count((array) $otherTargetTestResults), count((array) $otherTargetTestComments));
$splitLines = static function ($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return [];
    }
    return preg_split('/\r\n|\r|\n/', $value) ?: [];
};
$storedBaseline = old('baseline_results', $biologicalMonitoring->baseline_results ?? '');
$storedAnnual = old('baseline_annual', $biologicalMonitoring->baseline_annual ?? '');
$baselineLines = $splitLines($storedBaseline);
$annualLines = $splitLines($storedAnnual);
$uploadedBloodResultFiles = [];
if (! empty($biologicalMonitoring->blood_result_files ?? null)) {
    $decodedBloodResultFiles = json_decode((string) $biologicalMonitoring->blood_result_files, true);
    if (is_array($decodedBloodResultFiles)) {
        $uploadedBloodResultFiles = array_values(array_filter($decodedBloodResultFiles, static fn ($path) => is_string($path) && trim($path) !== ''));
    }
}
$biologicalManualComplete = old('biological_monitoring_manual_complete', (string) (($biologicalMonitoring->manual_completed ?? 0) ? '1' : '0')) === '1';
$bioRows = [];
$bioCount = max(count($baselineLines), count($annualLines), 1);
for ($i = 0; $i < $bioCount; $i++) {
    $determinant = '';
    $baseline = '';
    if (isset($baselineLines[$i])) {
        $parts = explode('::', $baselineLines[$i], 2);
        $determinant = trim($parts[0] ?? '');
        $baseline = trim($parts[1] ?? '');
    }
    $bioRows[] = [
        'determinant' => $determinant,
        'baseline' => $baseline,
        'annual' => trim($annualLines[$i] ?? ''),
    ];
}
$recommendationEmployeeSignature = old(
    'recommendation_employee_signature',
    $toSignatureDataUrl($recommendationData->employee_signature ?? $declaration->employee_signature ?? '')
);
$recommendationAckDate = trim((string) old(
    'recommendation_ack_date',
    $recommendationData->employee_signature_date ?? $declaration->employee_date ?? date('Y-m-d')
));
$doctorNameDisplay = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorNameDisplay = $doctorNameDisplay !== '' ? $doctorNameDisplay : (string) ($username ?? 'Doctor');
$doctorRegistrationDisplay = trim((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? '-'));
$clinicDisplayName = trim((string) ($activeClinic->clinic_name ?? ($clinicName ?? 'Medis SHAMS')));
$clinicTelephoneDisplay = trim((string) ($activeClinic->clinic_telephone ?? '-'));
$clinicFaxDisplay = trim((string) ($activeClinic->clinic_fax ?? '-'));
$clinicEmailDisplay = trim((string) ($activeClinic->clinic_email ?? '-'));
medis_render_navigation_start(['clinicName'=>$clinicName ?? 'Medis SHAMS','clinicLogoUrl'=>$clinicLogoUrl ?? null,'username'=>$username ?? 'User','active'=>'surveillance','showSurveillanceSubnav' => true,'surveillanceSubActive' => 'examination']);
?>
<style>
.app-page,.app-card{overflow:auto}
.flow{min-height:calc(100dvh - 204px)}
.exam-shell{display:grid;grid-template-columns:minmax(0,1fr);gap:16px;align-items:start;min-width:0;min-height:auto;overflow:visible}
.side{position:sticky;top:0;z-index:8;display:flex;justify-content:flex-end;overflow:visible;padding:0;border:0;border-radius:0;background:transparent}
.status-menu{position:relative}
.status-toggle{display:inline-flex;align-items:center;gap:10px;color:#334155;cursor:pointer;padding:10px 14px;border:1px solid #dbe3ea;border-radius:999px;background:#fff;text-align:left;font:inherit;box-shadow:0 10px 24px rgba(15,23,42,.08)}
.status-list{position:absolute;top:calc(100% + 10px);right:0;display:grid;gap:8px;min-width:280px;max-width:320px;max-height:65vh;overflow:auto;padding:12px;border:1px solid #dbe3ea;border-radius:20px;background:#fff;box-shadow:0 20px 44px rgba(15,23,42,.14);opacity:0;pointer-events:none;transform:translateY(-6px);transition:opacity .18s ease,transform .18s ease}
.status-menu.is-open .status-list{opacity:1;pointer-events:auto;transform:translateY(0)}
.status-item{position:relative;display:grid;grid-template-columns:28px minmax(0,1fr);align-items:start;gap:8px;color:#334155;cursor:pointer;padding:8px 6px;border:0;background:transparent;text-align:left;width:100%;font:inherit}
.status-icon{width:28px;height:28px;border-radius:999px;border:2px solid #d1d5db;background:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.78rem;position:relative;z-index:1;flex-shrink:0}
.status-icon.ok{background:#dcfce7;border-color:#389B5B;color:#166534}
.status-icon.bad{background:#fee2e2;border-color:#ef4444;color:#b91c1c}.status-text{display:grid;gap:1px;min-width:0}.status-text strong{font-size:.84rem;line-height:1.15;display:block}.status-text span{font-size:.72rem;color:#64748b;line-height:1.1}
.status-text{display:grid;gap:1px;min-width:0}
.status-text strong{font-size:.9rem}
.status-text span{font-size:.76rem;color:#64748b}
.main{margin-top:0;min-height:auto;overflow:visible;display:flex;flex-direction:column;border:0;background:transparent;border-radius:0;padding:0 2px 28px}
.top h2{margin:0 0 12px;font-size:1.8rem}
.record-tabs{display:flex;gap:18px;align-items:center;padding:0 6px 8px;border-bottom:1px solid #edf0f2;flex-wrap:wrap;margin-bottom:12px}
.record-tab{appearance:none;border:0;background:transparent;padding:14px 0 12px;font:inherit;font-weight:600;color:#4b5563;cursor:pointer;position:relative;text-decoration:none}
.record-tab.is-active{color:#166534}
.record-tab.is-active::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:#389B5B;border-radius:999px}
.notice{margin-top:14px;padding:12px 14px;border-radius:12px}
.ok-msg{border:1px solid #bbf7d0;background:#f0fdf4;color:#166534}
.err-msg{border:1px solid #fecaca;background:#fef2f2;color:#991b1b}
.stack{display:grid;gap:28px;margin-top:22px;overflow:visible}
.card{display:none;padding:0 0 10px;border:0;border-radius:0;background:transparent;box-shadow:none}
.card.is-active{display:block}
.status-item.is-current .status-text strong{color:#166534}
.status-item.is-current{background:#eff8f1;border-radius:14px}.status-item.is-current .status-icon{box-shadow:0 0 0 3px rgba(56,155,91,.12)}
.status-item:hover .status-text strong{color:#166534}
.card-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;flex-wrap:wrap;margin-bottom:20px;padding:0 0 16px;border-bottom:1px solid #dfe5ea}
.card-head h3{margin:0;font-size:1.16rem;line-height:1.35}
.card-head p{margin:6px 0 0;color:#6b7280;font-size:.92rem}
.badge{display:inline-flex;align-items:center;border-radius:999px;padding:7px 12px;font-size:.82rem;font-weight:700}
.badge.done{background:#dcfce7;color:#166534}
.badge.missing{background:#fee2e2;color:#b91c1c}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.current-company-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.grid-3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.section-stack{display:grid;gap:18px}
.subpanel{padding:18px;border:1px solid #dfe5ea;border-radius:18px;background:#fff}
.subpanel .section-title{margin:0 0 16px;font-size:1rem;font-weight:700;color:#111827}
.repeat-list{display:grid;gap:14px}
.repeat-card{padding:16px;border:1px solid #e5e7eb;border-radius:16px;background:#fbfcfd}
.repeat-card-head{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.repeat-card-title{font-size:.92rem;font-weight:700;color:#111827}
.repeat-actions{display:flex;justify-content:flex-end;margin-top:12px}
.history-stack{display:grid;gap:14px}
.history-row{display:grid;grid-template-columns:minmax(300px,1.45fr) minmax(160px,0.9fr) minmax(160px,0.9fr);gap:14px;align-items:start}
.history-row.two-inputs{grid-template-columns:minmax(300px,1.45fr) minmax(220px,1fr)}
.history-choice{display:grid;gap:8px;padding-right:8px}
.history-choice-label{font-size:.9rem;font-weight:600;color:#111827}
.history-radio-group{display:flex;gap:22px;align-items:center;justify-content:flex-start;flex-wrap:wrap;min-height:34px;padding:0;border:none;border-radius:0}
.history-radio,.training-radio{display:inline-flex;align-items:center;gap:8px;font-size:.9rem;font-weight:500;color:#111827}
.history-radio input,.training-radio input{width:20px;height:20px;margin:0;accent-color:#389B5B;cursor:pointer;flex-shrink:0}
.training-table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border:1px solid #e5e7eb;border-radius:14px}
.training-table th,.training-table td{padding:12px;vertical-align:top;border-bottom:1px solid #e5e7eb}
.training-table tr:last-child th,.training-table tr:last-child td{border-bottom:none}
.training-table th{width:28%;background:#f8fafc;text-align:left;font-size:.92rem;color:#111827;font-weight:600}
.training-table td{background:#fff}
.training-table .training-radio-cell{width:180px}
.training-table .comment-field textarea{width:100%;min-height:78px;border:1px solid #d7dce7;border-radius:10px;padding:11px 12px;font:inherit;background:#fff;color:#1f2937;resize:vertical}
.training-table .comment-field textarea::placeholder{color:#6b7280;opacity:1}
.medical-history-table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border:1px solid #e5e7eb;border-radius:14px}
.medical-history-table th,.medical-history-table td{padding:12px;vertical-align:top;border-bottom:1px solid #e5e7eb}
.medical-history-table tr:last-child th,.medical-history-table tr:last-child td{border-bottom:none}
.medical-history-table th{width:28%;background:#f8fafc;text-align:left;font-size:.92rem;color:#111827;font-weight:600}
.medical-history-table td{background:#fff}
.medical-history-table .medical-radio-cell{width:180px}
.medical-history-table .medical-comment-cell textarea{width:100%;min-height:78px;border:1px solid #d7dce7;border-radius:10px;padding:11px 12px;font:inherit;background:#fff;color:#1f2937;resize:vertical}
.medical-history-table .medical-comment-cell textarea::placeholder{color:#6b7280;opacity:1}
.training-radio-group{display:flex;align-items:center;gap:22px;justify-content:flex-start}
.field{display:grid;gap:7px}
.field label{font-size:.9rem;font-weight:600}
.field input,.field select,.field textarea{border:1px solid #d1d5db;border-radius:12px;padding:11px 12px;font:inherit;background:#fff;width:100%}
.field textarea{min-height:110px;resize:vertical}
.full{grid-column:1/-1}
.chemical-field-group{position:relative}
.chemical-trigger{width:100%;display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid #d1d5db;border-radius:12px;padding:11px 12px;background:#fff;color:#111827;font:inherit;text-align:left;cursor:pointer;min-height:50px}
.chemical-trigger:hover{border-color:#cbd5e1}
.chemical-trigger span{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:inherit;line-height:1.4}
.chemical-trigger.is-placeholder span{color:#9ca3af;font-weight:400}
.chemical-trigger-icon{font-size:.9rem;color:#6b7280;flex-shrink:0}
.chemical-dropdown{position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:30;border:1px solid #d1d5db;border-radius:16px;background:#fff;box-shadow:0 16px 36px rgba(15,23,42,.16);display:none;overflow:hidden}
.chemical-dropdown.is-open{display:block}
.chemical-search-wrap{padding:10px;border-bottom:1px solid #e5e7eb;background:#fff}
.chemical-search-wrap input{width:100%;border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;font:inherit;background:#fff;color:#111827}
.chemical-search-wrap input::placeholder,.chemical-add-form input::placeholder{color:#9ca3af;font-weight:400}
.chemical-options{max-height:240px;overflow:auto}
.chemical-option{padding:10px 12px;border-bottom:1px solid #eef2f4;cursor:pointer;font-size:.92rem;color:#111827;background:#fff}
.chemical-option:last-child{border-bottom:none}
.chemical-option:hover{background:#f8fafc}
.chemical-empty{padding:10px 12px;color:#6b7280;font-size:.88rem}
.chemical-add{display:block;width:100%;border:0;border-top:1px solid #e5e7eb;background:#fff;padding:12px;text-align:left;font:inherit;color:#374151;cursor:pointer}
.chemical-add:hover{background:#f8fafc}
.chemical-add-form{display:grid;gap:10px;padding:12px;border-top:1px solid #e5e7eb;background:#fff}
.chemical-add-form input{width:100%;border:1px solid #d1d5db;border-radius:12px;padding:10px 12px;font:inherit;background:#fff;color:#111827}
.chemical-add-actions{display:flex;justify-content:flex-end;gap:8px}
.table-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.mini-table{border:1px solid #dfe5ea;border-radius:16px;overflow:hidden;background:#fcfdfd}
.mini-table h4{margin:0;padding:13px 14px;background:#f7faf8;border-bottom:1px solid #dfe5ea;font-size:.96rem}
.mini-table-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px 14px;background:#f7faf8;border-bottom:1px solid #dfe5ea}
.mini-table-head h4{padding:0;border:0;background:transparent}
.group-no-toggle{display:inline-flex;align-items:center;gap:8px;font-size:.84rem;font-weight:600;color:#475569;white-space:nowrap}
.group-no-toggle input[type="checkbox"]{width:16px;height:16px;accent-color:#389B5B;cursor:pointer}
.symptom-row{display:grid;grid-template-columns:minmax(0,1fr) 180px;gap:10px;align-items:center;padding:12px 14px;border-top:1px solid #eef2f4}
.symptom-row:first-of-type{border-top:0}
.radio-inline{display:flex;align-items:center;gap:22px;justify-content:flex-start}
.radio-inline label{display:inline-flex;align-items:center;gap:8px;font-size:.88rem;font-weight:500}
.radio-inline input[type="radio"]{width:20px;height:20px;accent-color:#389B5B;cursor:pointer;flex-shrink:0}
.visual-inline{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.visual-inline label{display:inline-flex;align-items:center;gap:8px;font-size:.88rem;font-weight:600}
.visual-inline input{width:120px}
.reading-note{margin-top:8px;font-size:.82rem;color:#64748b}
.reading-note.is-normal{color:#166534}
.reading-note.is-elevated{color:#92400e}
.reading-note.is-stage1{color:#9a3412}
.reading-note.is-stage2{color:#b42318}
.reading-note.is-crisis{color:#991b1b}
.reading-note.is-underweight{color:#1d4ed8}
.reading-note.is-overweight{color:#b45309}
.reading-note.is-obese{color:#b42318}
.system-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.exam-table-wrap{overflow:auto;border:1px solid #dfe5ea;border-radius:18px;background:#fff;max-width:100%}
.exam-table{width:100%;border-collapse:collapse;min-width:900px}
.exam-table th,.exam-table td{border:1px solid #dfe5ea;padding:12px 12px;vertical-align:middle}
.exam-table thead th{background:#eef7f0;font-size:.84rem;font-weight:700;color:#1f2937}
.exam-table tbody th{background:#f8fbf8;font-weight:600;text-align:left}
#section-findings .exam-table thead th:first-child,
#section-findings .exam-table tbody th{width:34%}
.choice-cell{width:76px;text-align:center}
.choice-cell input[type="radio"]{width:18px;height:18px;accent-color:#389B5B;cursor:pointer}
.text-cell input,.text-cell textarea{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 10px;font:inherit;background:#fff}
.text-cell textarea{min-height:76px;resize:vertical}
.target-test-label{display:flex;align-items:center;justify-content:space-between;gap:12px}
.target-test-label input[type="text"]{width:100%;border:1px solid #d1d5db;border-radius:10px;padding:10px 10px;font:inherit;background:#fff}
.target-test-name-input{font-weight:500;color:#111827}
.target-test-remove{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;border:1px solid #fecaca;border-radius:999px;background:#fff;color:#ef4444;font:inherit;cursor:pointer;flex-shrink:0}
.target-test-remove:hover{background:#fef2f2}
.table-actions{display:flex;justify-content:flex-end;margin-top:12px}
.small-btn{display:inline-flex;align-items:center;gap:8px;border:1px solid #389B5B;border-radius:12px;padding:9px 14px;background:#fff;color:#389B5B;font:inherit;cursor:pointer}
.small-btn:hover{background:#f0fdf4}
.bio-remove{border-color:#ef4444;color:#ef4444;background:#fff;border-radius:999px;padding:0;width:34px;height:34px;justify-content:center;font-size:1.1rem;font-weight:700;line-height:1}
.bio-remove:hover{background:#fef2f2}
.bio-remove svg{width:16px;height:16px;stroke:currentColor;fill:none;stroke-width:1.8}
.file-upload-panel{display:grid;gap:14px;margin-top:16px;padding:16px;border:1px solid #dfe5ea;border-radius:18px;background:#fff}
.file-upload-panel h4{margin:0;font-size:1rem}
.file-upload-list{display:grid;gap:12px}
.file-upload-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:center}
.file-input-shell,.file-link-shell{display:flex;align-items:center;min-height:44px;padding:0 10px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc}
.file-input-shell input[type="file"]{width:100%;font:inherit;background:transparent;border:0;padding:0}
.file-link-shell a{color:#334155;text-decoration:none;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.file-link-shell a:hover{color:#166534}
.file-upload-row.is-removed{display:none}
.file-row-delete{display:inline-flex;align-items:center;justify-content:center;min-width:78px;height:40px;border:1px solid #bbf7d0;border-radius:12px;background:#fff;color:#9ca3af;font:inherit;cursor:pointer}
.file-row-delete:hover{border-color:#86efac;color:#166534}
.file-upload-actions{display:flex;justify-content:flex-end}
.file-upload-add{display:inline-flex;align-items:center;justify-content:center;min-width:110px;height:40px;border:1px solid #86efac;border-radius:12px;background:#fff;color:#16a34a;font:inherit;cursor:pointer}
.file-upload-add:hover{background:#f0fdf4}
.na-cell{color:#94a3b8;font-size:.85rem;text-align:center;font-weight:600}
.fit-row{display:flex;align-items:center;gap:28px;flex-wrap:wrap;margin-top:16px;padding-top:14px;border-top:1px solid #edf0f2}
.fit-row label{display:inline-flex;align-items:center;gap:8px;font-weight:600}
.fit-row input[type="radio"]{width:20px;height:20px;accent-color:#389B5B}
.actions{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-top:30px;padding-top:22px;border-top:1px solid #e5e7eb}
.btn,.next{display:inline-flex;align-items:center;gap:8px;text-decoration:none;border:1px solid #d1d5db;border-radius:12px;padding:10px 14px;background:#fff;color:#374151;cursor:pointer}
.next{background:#389B5B;border-color:#389B5B;color:#fff}
.section-block{display:grid;gap:16px}
.recommendation-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
.recommendation-option{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border:1px solid #dfe5ea;border-radius:14px;background:#fff}
.recommendation-option input[type="checkbox"]{width:18px;height:18px;margin-top:2px;accent-color:#389B5B;flex-shrink:0}
.recommendation-option span{font-size:.9rem;line-height:1.45;color:#1f2937}
.acknowledgement-block{display:grid;gap:16px;margin-top:10px;padding:18px;border:1px solid #dfe5ea;border-radius:18px;background:#fff}
.acknowledgement-text{font-size:.95rem;color:#374151;line-height:1.6}
.acknowledgement-top{display:grid;grid-template-columns:minmax(0,520px);gap:20px;align-items:start;justify-content:start}
.signature-panel{padding:0;min-height:0;display:grid;align-content:start;gap:14px}
.signature-pad-inline{height:240px;border:1px dashed #cbd5e1;border-radius:12px;background:#fcfcfd}
.signature-pad-inline canvas{width:100%;height:100%;display:block}
.signature-inline-actions{display:flex;justify-content:flex-start}
.signature-placeholder{color:#94a3b8;font-size:.88rem}
.ack-line{display:grid;grid-template-columns:250px minmax(0,1fr);gap:12px;align-items:start}
.ack-label{font-size:.92rem;color:#374151}
.ack-value{min-height:24px;padding:0;color:#111827;font-size:.92rem;font-weight:500}
.ack-grid{display:grid;grid-template-columns:1fr;gap:8px}
.ack-date-text{font-weight:500;color:#111827}
@media(max-width:1200px){.table-grid,.system-grid{grid-template-columns:1fr}}
@media(max-width:1280px){.flow{min-height:auto}.exam-shell{grid-template-columns:1fr;min-height:auto}.side{position:sticky;top:0;max-height:none}.status-list{left:0;right:auto;min-width:min(320px,calc(100vw - 32px))}.main{min-height:auto}}
@media(max-width:900px){.recommendation-grid{grid-template-columns:1fr}.acknowledgement-top,.ack-line,.ack-grid{grid-template-columns:1fr}}
@media(max-width:860px){.history-row,.history-row.two-inputs{grid-template-columns:1fr}.training-table,.training-table tbody,.training-table tr,.training-table th,.training-table td,.medical-history-table,.medical-history-table tbody,.medical-history-table tr,.medical-history-table th,.medical-history-table td{display:block;width:100%}.training-table th,.medical-history-table th{border-bottom:none;padding-bottom:6px}.training-table td,.medical-history-table td{padding-top:0}}
@media(max-width:760px){.main{padding:0}.card{padding:0 0 8px}.symptom-row{grid-template-columns:1fr}.radio-inline{justify-content:flex-start;flex-wrap:wrap}.visual-inline{gap:12px}.repeat-card-head{align-items:flex-start;flex-direction:column}}
</style>
<div class="flow"><div class="exam-shell"><aside class="side"><div class="status-menu" id="examStatusMenu"><button class="status-toggle" type="button" id="examStatusToggle" aria-expanded="false"><span class="status-icon <?php echo !empty($sectionStatuses[$sectionItems[0]['key'] ?? '']) ? 'ok' : 'bad'; ?>" id="examStatusToggleIcon"><?php echo !empty($sectionStatuses[$sectionItems[0]['key'] ?? '']) ? '&#10003;' : '!'; ?></span><span class="status-text"><strong id="examStatusToggleLabel">Sections</strong><span id="examStatusToggleMeta">Choose a section</span></span></button><div class="status-list"><?php foreach($sectionItems as $index => $item): $done = !empty($sectionStatuses[$item['key']]); ?><button class="status-item" type="button" data-nav-index="<?php echo $esc($index); ?>"><span class="status-icon <?php echo $done ? 'ok' : 'bad'; ?>"><?php echo $done ? '&#10003;' : '!'; ?></span><span class="status-text"><strong><?php echo $esc($item['label']); ?></strong><span><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></span></button><?php endforeach; ?></div></div></aside><section class="main"><?php if ($showRecordTabs): ?><div class="record-tabs"><a class="record-tab <?php echo $recordTabActive === 'declaration' ? 'is-active' : ''; ?>" href="<?php echo $esc($recordDeclarationUrl); ?>">Declaration</a><a class="record-tab <?php echo $recordTabActive === 'examination' ? 'is-active' : ''; ?>" href="<?php echo $esc($recordExaminationUrl); ?>">Examination</a></div><?php endif; ?><div class="top" id="examPageTop"><h2 id="examPageTitle"><?php echo $esc($isReadOnly ? 'View Surveillance Examination' : ($pageMode === 'edit' ? 'Edit Surveillance Examination' : 'Surveillance Examination')); ?></h2></div><?php if(session('status')): ?><div class="notice ok-msg"><?php echo $esc(session('status')); ?></div><?php endif; ?><?php if(isset($errors) && $errors->any()): ?><div class="notice err-msg"><?php echo $esc($errors->first()); ?></div><?php endif; ?><form method="POST" action="<?php echo $esc($saveExamUrl); ?>" id="surveillanceExamForm" data-readonly="<?php echo $isReadOnly ? '1' : '0'; ?>" enctype="multipart/form-data"><input type="hidden" name="_token" value="<?php echo $esc(csrf_token()); ?>"><input type="hidden" name="employee_id" value="<?php echo $esc(old('employee_id', $selectedEmployee->employee_id ?? $declaration->employee_id ?? '')); ?>"><input type="hidden" name="doctor_id" value="<?php echo $esc($doctor->doctor_id ?? ''); ?>"><input type="hidden" name="surveillance_id" id="surveillanceIdInput" value="<?php echo $esc($surveillanceId ?? ''); ?>"><input type="hidden" name="declaration_id" id="declarationIdInput" value="<?php echo $esc($declarationId ?? $declaration->declaration_id ?? ''); ?>"><input type="hidden" name="company_id" value="<?php echo $esc(old('company_id', $selectedCompany->company_id ?? $declaration->company_id ?? '')); ?>"><input type="hidden" name="baseline_results" id="baselineResultsStore" value="<?php echo $esc(old('baseline_results', $biologicalMonitoring->baseline_results ?? '')); ?>"><input type="hidden" name="baseline_annual" id="baselineAnnualStore" value="<?php echo $esc(old('baseline_annual', $biologicalMonitoring->baseline_annual ?? '')); ?>"><input type="hidden" name="removed_blood_result_files" id="removedBloodResultFilesInput" value="<?php echo $esc(old('removed_blood_result_files', '')); ?>"><input type="hidden" name="save_mode" id="saveModeInput" value="draft"><div class="stack">
<section class="card is-active" data-section-index="0" id="section-patient"><?php $done = !empty($sectionStatuses['patient']); ?><div class="card-head"><div><h3>Patient Information</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="section-stack"><div class="subpanel"><div class="section-title">Medical History</div><table class="medical-history-table"><tbody><?php foreach ($medicalHistoryRows as $medicalHistoryRow): ?><?php $medicalHistoryValue = trim((string) $patientFormValue($medicalHistoryRow['name'])); $medicalHistoryChoice = old($medicalHistoryRow['name'] . '_status', $patientFormValue($medicalHistoryRow['name'] . '_status', $medicalHistoryValue !== '' ? 'Yes' : '')); ?><tr data-medical-history-row><th><?php echo $esc($medicalHistoryRow['label']); ?></th><td class="medical-radio-cell"><div class="radio-inline"><label><input type="radio" name="<?php echo $esc($medicalHistoryRow['name']); ?>_status" value="Yes" data-medical-history-toggle="Yes"<?php echo $medicalHistoryChoice === 'Yes' ? ' checked' : ''; ?><?php echo $isReadOnly ? ' disabled' : ''; ?>> Yes</label><label><input type="radio" name="<?php echo $esc($medicalHistoryRow['name']); ?>_status" value="No" data-medical-history-toggle="No"<?php echo $medicalHistoryChoice === 'No' ? ' checked' : ''; ?><?php echo $isReadOnly ? ' disabled' : ''; ?>> No</label></div></td><td class="medical-comment-cell" data-medical-history-detail><textarea name="<?php echo $esc($medicalHistoryRow['name']); ?>" placeholder="<?php echo $esc($medicalHistoryRow['placeholder']); ?>" data-medical-history-textarea<?php echo $isReadOnly ? ' readonly' : ''; ?>><?php echo $esc($patientFormValue($medicalHistoryRow['name'])); ?></textarea></td></tr><?php endforeach; ?></tbody></table></div><div class="subpanel"><div class="section-title">Occupational &amp; Company History</div><div class="repeat-card"><div class="repeat-card-head"><div class="repeat-card-title">Current Company Record</div></div><div class="grid"><label class="field">Job Title<input type="text" name="current_job_title" value="<?php echo $esc($patientFormValue('current_job_title')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label><label class="field">Company Name<input type="text" name="current_company_name" value="<?php echo $esc($patientFormValue('current_company_name', $selectedCompany->company_name ?? '')); ?>" readonly></label><div class="field full"><div class="current-company-grid"><label class="field">Date of Starting Employment<input type="date" name="current_start_employment_date" value="<?php echo $esc($patientFormValue('current_start_employment_date')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label><label class="field">Employment Duration<input type="text" name="current_employment_duration" value="<?php echo $esc($patientFormValue('current_employment_duration')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label><label class="field">Chemical Exposure Duration<input type="text" name="current_chemical_exposure_duration" value="<?php echo $esc($patientFormValue('current_chemical_exposure_duration')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label></div></div><label class="field full">Chemical Exposure Incidents<textarea name="current_chemical_exposure_incidents"<?php echo $isReadOnly ? ' readonly' : ''; ?>><?php echo $esc($patientFormValue('current_chemical_exposure_incidents')); ?></textarea></label></div></div><div class="repeat-list" id="occupationalHistoryList"><?php for ($index = 0; $index < $occupationalPastRowCount; $index++): ?><div class="repeat-card" data-occup-row><div class="repeat-card-head"><div class="repeat-card-title">Past Company Record <?php echo $index + 1; ?></div><?php if (! $isReadOnly): ?><button class="btn danger small" type="button" data-remove-occup-row>Delete</button><?php endif; ?></div><div class="grid"><label class="field">Job Title<input type="text" name="occup_job_title[]" value="<?php echo $esc((string) ($occupJobTitles[$index] ?? '')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label><label class="field">Company Name<input type="text" name="occup_company_name[]" value="<?php echo $esc((string) ($occupCompanyNames[$index] ?? '')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label><div class="field full"><div class="current-company-grid"><label class="field">Date of Starting Employment<input type="date" name="start_employment_date[]" value="<?php echo $esc((string) ($startEmploymentDates[$index] ?? '')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label><label class="field">Employment Duration<input type="text" name="employment_duration[]" value="<?php echo $esc((string) ($employmentDurations[$index] ?? '')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label><label class="field">Chemical Exposure Duration<input type="text" name="chemical_exposure_duration[]" value="<?php echo $esc((string) ($chemicalExposureDurations[$index] ?? '')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label></div></div><label class="field full">Chemical Exposure Incidents<textarea name="chemical_exposure_incidents[]"<?php echo $isReadOnly ? ' readonly' : ''; ?>><?php echo $esc((string) ($chemicalExposureIncidents[$index] ?? '')); ?></textarea></label></div></div><?php endfor; ?></div><?php if (! $isReadOnly): ?><div class="repeat-actions"><button class="small-btn" type="button" id="addOccupationalRow">+ Add Past Company</button></div><?php endif; ?></div><div class="subpanel"><div class="section-title">Personal &amp; Social History</div><div class="history-stack"><div class="history-row"><div class="history-choice"><div class="history-choice-label">Smoking History</div><div class="history-radio-group"><?php foreach(['Current','Ex-smoker','Non-smoker'] as $option): ?><label class="history-radio"><input type="radio" name="smoking_history" value="<?php echo $esc($option); ?>"<?php echo $patientFormValue('smoking_history') === $option ? ' checked' : ''; ?><?php echo $isReadOnly ? ' disabled' : ''; ?>><?php echo $esc($option); ?></label><?php endforeach; ?></div></div><label class="field">Years of Smoking<input type="number" min="0" name="years_of_smoking" value="<?php echo $esc($patientFormValue('years_of_smoking')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label><label class="field">No. of Cigarettes<input type="number" min="0" name="no_of_cigarettes" value="<?php echo $esc($patientFormValue('no_of_cigarettes')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label></div><div class="history-row two-inputs"><div class="history-choice"><div class="history-choice-label">Vaping History</div><div class="history-radio-group"><?php foreach(['Yes','No'] as $option): ?><label class="history-radio"><input type="radio" name="vaping_history" value="<?php echo $esc($option); ?>"<?php echo $patientFormValue('vaping_history') === $option ? ' checked' : ''; ?><?php echo $isReadOnly ? ' disabled' : ''; ?>><?php echo $esc($option); ?></label><?php endforeach; ?></div></div><label class="field">Years of Vaping<input type="number" min="0" name="years_of_vaping" value="<?php echo $esc($patientFormValue('years_of_vaping')); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>></label></div><label class="field full">Hobby<textarea name="hobby"<?php echo $isReadOnly ? ' readonly' : ''; ?>><?php echo $esc($patientFormValue('hobby')); ?></textarea></label></div></div><div class="subpanel"><div class="section-title">Training History</div><table class="training-table"><tbody><?php foreach ([['Handling of Chemical','handling_of_chemical','chemical_comments','Insert handling comments'],['Sign & Symptoms Knowledge','sign_symptoms','sign_comments','Insert sign and symptoms comments'],['Chemical Poisoning Knowledge','chemical_poisoning','poisoning_comments','Insert poisoning comments'],['Proper PPE Knowledge','proper_PPE','proper_comments','Insert proper PPE comments'],['PPE Usage','PPE_usage','usage_comments','Insert PPE usage comments']] as $trainingRow): ?><tr><th><?php echo $esc($trainingRow[0]); ?></th><td class="training-radio-cell"><div class="radio-inline"><?php foreach(['Yes','No'] as $option): ?><label><input type="radio" name="<?php echo $esc($trainingRow[1]); ?>" value="<?php echo $esc($option); ?>"<?php echo $patientFormValue($trainingRow[1]) === $option ? ' checked' : ''; ?><?php echo $isReadOnly ? ' disabled' : ''; ?>> <?php echo $esc($option); ?></label><?php endforeach; ?></div></td><td class="comment-field"><textarea name="<?php echo $esc($trainingRow[2]); ?>" placeholder="<?php echo $esc($trainingRow[3]); ?>"<?php echo $isReadOnly ? ' readonly' : ''; ?>><?php echo $esc($patientFormValue($trainingRow[2])); ?></textarea></td></tr><?php endforeach; ?></tbody></table></div></div></section>
<section class="card" data-section-index="1" id="section-chemical"><?php $done = !empty($sectionStatuses['chemical']); $chemicalValue = old('chemicals', $chemicalInfo->chemicals ?? ''); ?><div class="card-head"><div><h3>Chemical Information</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="grid"><div class="field"><label>Workplace / Company</label><input type="text" name="company_name" value="<?php echo $esc(old('company_name', $chemicalInfo->company_name ?? $selectedCompany->company_name ?? '')); ?>"></div><div class="field chemical-field-group"><label for="toggleChemicalDropdown">Chemicals</label><?php if ($isReadOnly): ?><input type="text" name="chemicals" value="<?php echo $esc($chemicalValue); ?>" readonly><?php else: ?><input type="hidden" name="chemicals" id="chemicalValueInput" value="<?php echo $esc($chemicalValue); ?>"><button class="chemical-trigger<?php echo trim((string) $chemicalValue) === '' ? ' is-placeholder' : ''; ?>" type="button" id="toggleChemicalDropdown" aria-haspopup="listbox" aria-expanded="false"><span id="chemicalTriggerLabel"><?php echo $esc($chemicalValue ?: 'Select chemical'); ?></span><span class="chemical-trigger-icon">&#9662;</span></button><div class="chemical-dropdown" id="chemicalDropdown"><div class="chemical-search-wrap"><input type="text" id="chemicalSearchInput" placeholder="Search chemical" autocomplete="off"></div><div class="chemical-options" id="chemicalOptionsList"><?php foreach(($chemicalOptions ?? []) as $chemicalOption): ?><div class="chemical-option" data-chemical-option="<?php echo $esc($chemicalOption); ?>" role="option"><?php echo $esc($chemicalOption); ?></div><?php endforeach; ?><div class="chemical-empty" id="chemicalEmptyState" style="display:none;">No matching chemical found.</div></div><button class="chemical-add" type="button" id="addChemicalOption">+ Add new</button><div class="chemical-add-form" id="chemicalAddForm" style="display:none;"><input type="text" id="chemicalNewInput" placeholder="Enter new chemical name" autocomplete="off"><div class="chemical-add-actions"><button class="btn" type="button" id="chemicalAddCancel">Cancel</button><button class="next" type="button" id="chemicalAddSave">Save</button></div></div></div><?php endif; ?></div><div class="field"><label>Types of Medical Examination</label><select name="examination_type"><option value="">Select examination type</option><?php foreach(['Pre-Placement','Periodic','Return to Work','Exit'] as $type): ?><option value="<?php echo $esc($type); ?>" <?php echo old('examination_type', $chemicalInfo->examination_type ?? '') === $type ? 'selected' : ''; ?>><?php echo $esc($type); ?></option><?php endforeach; ?></select></div><div class="field"><label>Examination Date</label><input type="date" name="examination_date" value="<?php echo $esc(old('examination_date', $chemicalInfo->examination_date ?? date('Y-m-d'))); ?>"></div></div></section>
<section class="card" data-section-index="2" id="section-history"><?php $done = !empty($sectionStatuses['history']); ?><div class="card-head"><div><h3>History of Health Effect</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="table-grid"><?php foreach($historyGroups as $groupTitle => $group): ?><?php $groupKey = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string) $groupTitle) ?: 'history-group'); ?><div class="mini-table" data-history-group="<?php echo $esc($groupKey); ?>"><div class="mini-table-head"><h4><?php echo $esc($groupTitle); ?></h4><?php if (! $isReadOnly): ?><label class="group-no-toggle"><input type="checkbox" data-history-all-no="<?php echo $esc($groupKey); ?>"> All No</label><?php endif; ?></div><?php foreach($group as $name => $label): ?><?php $historyColumn = $historyFieldColumnMap[$name] ?? $name; $historyValue = old($name, $historyOfHealth->{$historyColumn} ?? ''); ?><div class="symptom-row"><span><?php echo $esc($label); ?></span><div class="radio-inline"><label><input type="radio" name="<?php echo $esc($name); ?>" value="Yes" data-history-group-radio="<?php echo $esc($groupKey); ?>" <?php echo $historyValue === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="<?php echo $esc($name); ?>" value="No" data-history-group-radio="<?php echo $esc($groupKey); ?>" <?php echo $historyValue === 'No' ? 'checked' : ''; ?>> No</label></div></div><?php endforeach; ?></div><?php endforeach; ?></div><div class="field full" style="margin-top:14px;"><label>Others (Elaborate frequency / severity)</label><textarea name="others_effect"><?php echo $esc(old('others_effect', $historyOfHealth->others_symptoms ?? '')); ?></textarea></div></section>
<section class="card" data-section-index="3" id="section-clinical"><?php $done = !empty($sectionStatuses['clinical']); ?><div class="card-head"><div><h3>Clinical Findings</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="grid"><div class="field"><label>Clinical Findings Present</label><div class="radio-inline"><?php $clinicalResult = old('result_clinical_findings', $clinicalFindings->result_clinical_findings ?? ''); ?><label><input type="radio" name="result_clinical_findings" value="Yes" <?php echo $clinicalResult === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="result_clinical_findings" value="No" <?php echo $clinicalResult === 'No' ? 'checked' : ''; ?>> No</label></div></div><div class="field full"><label>Describe current health effects</label><textarea name="elaboration"><?php echo $esc(old('elaboration', $clinicalFindings->elaboration ?? '')); ?></textarea></div></div></section>
<section class="card" data-section-index="4" id="section-physical"><?php $done = !empty($sectionStatuses['physical']); ?><div class="card-head"><div><h3>Physical Examination</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="exam-table-wrap"><table class="exam-table"><thead><tr><th style="width:30%;">Area / System</th><th style="width:22%;">Finding</th><th style="width:48%;">Result</th></tr></thead><tbody><tr><th rowspan="3">Anthropometry</th><th>Weight (kg)</th><td class="text-cell"><input type="number" step="0.01" name="weight" value="<?php echo $esc(old('weight', $physicalExam->weight ?? '')); ?>"></td></tr><tr><th>Height (cm)</th><td class="text-cell"><input type="number" step="0.01" name="height" value="<?php echo $esc(old('height', $physicalExam->height ?? '')); ?>"></td></tr><tr><th>BMI</th><td class="text-cell"><input type="number" step="0.01" name="BMI" value="<?php echo $esc(old('BMI', $physicalExam->BMI ?? '')); ?>" readonly><div class="reading-note" id="bmiReading">BMI category will appear here.</div></td></tr><tr><th rowspan="3">Vital Signs</th><th>Blood Pressure (mm/Hg)</th><td class="text-cell"><div class="grid-2"><div class="field"><label>Systolic</label><input type="number" name="bp_systolic" value="<?php echo $esc(old('bp_systolic', $physicalExam->bp_systolic ?? '')); ?>"></div><div class="field"><label>Diastolic</label><input type="number" name="bp_distolic" value="<?php echo $esc(old('bp_distolic', $physicalExam->bp_distolic ?? '')); ?>"><div class="reading-note" id="bpReading">Blood pressure category will appear here.</div></div></div></td></tr><tr><th>Pulse Rate (bpm)</th><td class="text-cell"><input type="number" name="pulse_rate" value="<?php echo $esc(old('pulse_rate', $physicalExam->pulse_rate ?? '')); ?>"></td></tr><tr><th>Respiratory Rate</th><td class="text-cell"><input type="number" name="respiratory_rate" value="<?php echo $esc(old('respiratory_rate', $physicalExam->respiratory_rate ?? '')); ?>"></td></tr><tr><th>General Appearances</th><th>Finding</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="general_appearances" value="Normal" <?php echo old('general_appearances', $physicalExam->general_appearances ?? '') === 'Normal' ? 'checked' : ''; ?>> Normal</label><label><input type="radio" name="general_appearances" value="Abnormal" <?php echo old('general_appearances', $physicalExam->general_appearances ?? '') === 'Abnormal' ? 'checked' : ''; ?>> Abnormal</label></div></td></tr><tr><th rowspan="2">Cardiovascular System</th><th>S1 &amp; S2</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="s1_s2" value="Yes" <?php echo old('s1_s2', $physicalExam->s1_s2 ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="s1_s2" value="No" <?php echo old('s1_s2', $physicalExam->s1_s2 ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th>Murmur</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="murmur" value="Yes" <?php echo old('murmur', $physicalExam->murmur ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="murmur" value="No" <?php echo old('murmur', $physicalExam->murmur ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th>Ear, Nose and Throat</th><th>ENT Findings</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="ear_nose_throat" value="Normal" <?php echo old('ear_nose_throat', $physicalExam->ear_nose_throat ?? '') === 'Normal' ? 'checked' : ''; ?>> Normal</label><label><input type="radio" name="ear_nose_throat" value="Abnormal" <?php echo old('ear_nose_throat', $physicalExam->ear_nose_throat ?? '') === 'Abnormal' ? 'checked' : ''; ?>> Abnormal</label></div></td></tr><tr><th rowspan="2">Eyes</th><th>Visual Acuity</th><td class="text-cell"><div class="visual-inline"><label>R <input type="text" name="visual_acuity_right" value="<?php echo $esc(old('visual_acuity_right', $physicalExam->visual_acuity_right ?? '')); ?>"></label><label>L <input type="text" name="visual_acuity_left" value="<?php echo $esc(old('visual_acuity_left', $physicalExam->visual_acuity_left ?? '')); ?>"></label></div></td></tr><tr><th>Colour Blindness</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="colour_blindness" value="Yes" <?php echo old('colour_blindness', $physicalExam->colour_blindness ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="colour_blindness" value="No" <?php echo old('colour_blindness', $physicalExam->colour_blindness ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th rowspan="2">Gastrointestinal</th><th>Tenderness</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="gas_tenderness" value="Yes" <?php echo old('gas_tenderness', $physicalExam->gas_tenderness ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="gas_tenderness" value="No" <?php echo old('gas_tenderness', $physicalExam->gas_tenderness ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th>Abdominal Mass</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="abdominal_mass" value="Yes" <?php echo old('abdominal_mass', $physicalExam->abdominal_mass ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="abdominal_mass" value="No" <?php echo old('abdominal_mass', $physicalExam->abdominal_mass ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th rowspan="2">Haematology</th><th>Lymph Nodes</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="lymph_nodes" value="Palpable" <?php echo old('lymph_nodes', $physicalExam->lymph_nodes ?? '') === 'Palpable' ? 'checked' : ''; ?>> Palpable</label><label><input type="radio" name="lymph_nodes" value="Non-palpable" <?php echo old('lymph_nodes', $physicalExam->lymph_nodes ?? '') === 'Non-palpable' ? 'checked' : ''; ?>> Non-palpable</label></div></td></tr><tr><th>Splenomegaly</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="splenomegaly" value="Yes" <?php echo old('splenomegaly', $physicalExam->splenomegaly ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="splenomegaly" value="No" <?php echo old('splenomegaly', $physicalExam->splenomegaly ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th rowspan="2">Kidney</th><th>Tenderness</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="kidney_tenderness" value="Yes" <?php echo old('kidney_tenderness', $physicalExam->kidney_tenderness ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="kidney_tenderness" value="No" <?php echo old('kidney_tenderness', $physicalExam->kidney_tenderness ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th>Ballotable</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="ballotable" value="Yes" <?php echo old('ballotable', $physicalExam->ballotable ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="ballotable" value="No" <?php echo old('ballotable', $physicalExam->ballotable ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th rowspan="2">Liver</th><th>Jaundice</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="jaundice" value="Yes" <?php echo old('jaundice', $physicalExam->jaundice ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="jaundice" value="No" <?php echo old('jaundice', $physicalExam->jaundice ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th>Hepatomegaly</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="hepatomegaly" value="Yes" <?php echo old('hepatomegaly', $physicalExam->hepatomegaly ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="hepatomegaly" value="No" <?php echo old('hepatomegaly', $physicalExam->hepatomegaly ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th rowspan="2">Musculoskeletal</th><th>Muscle Tone</th><td class="text-cell"><div class="radio-inline"><?php foreach (['1','2','3','4','5'] as $option): ?><label><input type="radio" name="muscle_tone" value="<?php echo $esc($option); ?>" <?php echo old('muscle_tone', $physicalExam->muscle_tone ?? '') === $option ? 'checked' : ''; ?>> <?php echo $esc($option); ?></label><?php endforeach; ?></div></td></tr><tr><th>Muscle Tenderness</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="muscle_tenderness" value="Yes" <?php echo old('muscle_tenderness', $physicalExam->muscle_tenderness ?? '') === 'Yes' ? 'checked' : ''; ?>> Yes</label><label><input type="radio" name="muscle_tenderness" value="No" <?php echo old('muscle_tenderness', $physicalExam->muscle_tenderness ?? '') === 'No' ? 'checked' : ''; ?>> No</label></div></td></tr><tr><th rowspan="2">Nervous System</th><th>Power</th><td class="text-cell"><div class="radio-inline"><?php foreach (['1','2','3','4','5'] as $option): ?><label><input type="radio" name="power" value="<?php echo $esc($option); ?>" <?php echo old('power', $physicalExam->power ?? '') === $option ? 'checked' : ''; ?>> <?php echo $esc($option); ?></label><?php endforeach; ?></div></td></tr><tr><th>Sensation</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="sensation" value="Normal" <?php echo old('sensation', $physicalExam->sensation ?? '') === 'Normal' ? 'checked' : ''; ?>> Normal</label><label><input type="radio" name="sensation" value="Abnormal" <?php echo old('sensation', $physicalExam->sensation ?? '') === 'Abnormal' ? 'checked' : ''; ?>> Abnormal</label></div></td></tr><tr><th rowspan="2">Respiratory</th><th>Sound</th><td class="text-cell"><div class="radio-inline"><?php foreach (['Clear','Rhonchi','Crepitus'] as $option): ?><label><input type="radio" name="sound" value="<?php echo $esc($option); ?>" <?php echo old('sound', $physicalExam->sound ?? '') === $option ? 'checked' : ''; ?>> <?php echo $esc($option); ?></label><?php endforeach; ?></div></td></tr><tr><th>Air Entry</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="air_entry" value="Normal" <?php echo old('air_entry', $physicalExam->air_entry ?? '') === 'Normal' ? 'checked' : ''; ?>> Normal</label><label><input type="radio" name="air_entry" value="Abnormal" <?php echo old('air_entry', $physicalExam->air_entry ?? '') === 'Abnormal' ? 'checked' : ''; ?>> Abnormal</label></div></td></tr><tr><th>Reproductive</th><th>Finding</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="reproductive" value="Normal" <?php echo old('reproductive', $physicalExam->reproductive ?? '') === 'Normal' ? 'checked' : ''; ?>> Normal</label><label><input type="radio" name="reproductive" value="Abnormal" <?php echo old('reproductive', $physicalExam->reproductive ?? '') === 'Abnormal' ? 'checked' : ''; ?>> Abnormal</label></div></td></tr><tr><th>Skin</th><th>Finding</th><td class="text-cell"><div class="radio-inline"><label><input type="radio" name="skin" value="Normal" <?php echo old('skin', $physicalExam->skin ?? '') === 'Normal' ? 'checked' : ''; ?>> Normal</label><label><input type="radio" name="skin" value="Abnormal" <?php echo old('skin', $physicalExam->skin ?? '') === 'Abnormal' ? 'checked' : ''; ?>> Abnormal</label></div></td></tr><tr><th>Others</th><th>Finding</th><td class="text-cell"><textarea name="others"><?php echo $esc(old('others', $physicalExam->others ?? '')); ?></textarea></td></tr></tbody></table></div></section>
<section class="card" data-section-index="5" id="section-target"><?php $done = !empty($sectionStatuses['target']); ?><div class="card-head"><div><h3>Target Organ Test</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="exam-table-wrap"><table class="exam-table" id="targetOrganTable"><thead><tr><th>Test</th><th class="choice-cell">Normal</th><th class="choice-cell">Abnormal</th><th>Comments / Findings</th></tr></thead><tbody><tr><th>Full Blood Count</th><td class="choice-cell"><input type="radio" name="blood_count_result" value="Normal" <?php echo $fixedTargetResultValue('blood_count_result') === 'Normal' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="blood_count_result" value="Abnormal" <?php echo $fixedTargetResultValue('blood_count_result') === 'Abnormal' ? 'checked' : ''; ?>></td><td class="text-cell"><textarea name="blood_comments"><?php echo $esc(old('blood_comments', $targetOrgan->blood_comments ?? '')); ?></textarea></td></tr><tr><th>Renal Function Test</th><td class="choice-cell"><input type="radio" name="renal_function_result" value="Normal" <?php echo $fixedTargetResultValue('renal_function_result') === 'Normal' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="renal_function_result" value="Abnormal" <?php echo $fixedTargetResultValue('renal_function_result') === 'Abnormal' ? 'checked' : ''; ?>></td><td class="text-cell"><textarea name="renal_comments"><?php echo $esc(old('renal_comments', $targetOrgan->renal_comments ?? '')); ?></textarea></td></tr><tr><th>Liver Function Test</th><td class="choice-cell"><input type="radio" name="liver_function_result" value="Normal" <?php echo $fixedTargetResultValue('liver_function_result') === 'Normal' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="liver_function_result" value="Abnormal" <?php echo $fixedTargetResultValue('liver_function_result') === 'Abnormal' ? 'checked' : ''; ?>></td><td class="text-cell"><textarea name="liver_comments"><?php echo $esc(old('liver_comments', $targetOrgan->liver_comments ?? '')); ?></textarea></td></tr><tr><th>Chest X-ray</th><td class="choice-cell"><input type="radio" name="chest_xray_result" value="Normal" <?php echo $fixedTargetResultValue('chest_xray_result') === 'Normal' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="chest_xray_result" value="Abnormal" <?php echo $fixedTargetResultValue('chest_xray_result') === 'Abnormal' ? 'checked' : ''; ?>></td><td class="text-cell"><textarea name="chest_comments"><?php echo $esc(old('chest_comments', $targetOrgan->chest_comments ?? '')); ?></textarea></td></tr><?php for ($targetTestIndex = 0; $targetTestIndex < $otherTargetTestRowCount; $targetTestIndex++): ?><tr data-target-test-row><th><div class="target-test-label"><input type="text" class="target-test-name-input" name="other_target_test_name[]" value="<?php echo $esc((string) ($otherTargetTestNames[$targetTestIndex] ?? '')); ?>" placeholder="Insert test name"<?php echo $isReadOnly ? ' readonly' : ''; ?>><?php if (! $isReadOnly): ?><button type="button" class="target-test-remove" data-remove-target-test-row>&times;</button><?php endif; ?></div></th><td class="choice-cell"><input type="radio" name="other_target_test_result[<?php echo $targetTestIndex; ?>]" value="Normal" <?php echo ((string) ($otherTargetTestResults[$targetTestIndex] ?? '')) === 'Normal' ? 'checked' : ''; ?><?php echo $isReadOnly ? ' disabled' : ''; ?>></td><td class="choice-cell"><input type="radio" name="other_target_test_result[<?php echo $targetTestIndex; ?>]" value="Abnormal" <?php echo ((string) ($otherTargetTestResults[$targetTestIndex] ?? '')) === 'Abnormal' ? 'checked' : ''; ?><?php echo $isReadOnly ? ' disabled' : ''; ?>></td><td class="text-cell"><textarea name="other_target_test_comments[]" placeholder="Comments / findings"<?php echo $isReadOnly ? ' readonly' : ''; ?>><?php echo $esc((string) ($otherTargetTestComments[$targetTestIndex] ?? '')); ?></textarea></td></tr><?php endfor; ?><tr><th>Spirometry</th><td colspan="3" class="text-cell"><div class="grid-3"><div class="field"><label>FEV 1</label><input type="number" step="0.01" name="spirometry_FEV1" value="<?php echo $esc(old('spirometry_FEV1', $targetOrgan->spirometry_FEV1 ?? '')); ?>"></div><div class="field"><label>FVC</label><input type="number" step="0.01" name="spirometry_FVC" value="<?php echo $esc(old('spirometry_FVC', $targetOrgan->spirometry_FVC ?? '')); ?>"></div><div class="field"><label>FEV / FVC</label><input type="number" step="0.01" name="spirometry_FEV_FVC" value="<?php echo $esc(old('spirometry_FEV_FVC', $targetOrgan->spirometry_FEV_FVC ?? '')); ?>"></div></div><div class="field" style="margin-top:12px;"><label>Comments</label><textarea name="spirometry_comments"><?php echo $esc(old('spirometry_comments', $targetOrgan->spirometry_comments ?? '')); ?></textarea></div></td></tr></tbody></table></div><?php if (! $isReadOnly): ?><div class="table-actions"><button type="button" class="small-btn" id="addTargetTestRow">+ Add Other Test</button></div><?php endif; ?></section>
<section class="card" data-section-index="6" id="section-biological"><?php $done = !empty($sectionStatuses['biological']); ?><div class="card-head"><div><h3>Biological Monitoring</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><label class="recommendation-option" style="margin-bottom:16px;"><input type="checkbox" name="biological_monitoring_manual_complete" id="biologicalManualCompleteInput" value="1" <?php echo $biologicalManualComplete ? 'checked' : ''; ?><?php echo $isReadOnly ? ' disabled' : ''; ?>><span>Mark this section as completed manually when biological monitoring is not required for this patient.</span></label><div class="exam-table-wrap"><table class="exam-table" id="bioMonitoringTable"><thead><tr><th>Biological Exposure Indices / Determinants</th><th>Baseline</th><th>Annual</th><th class="choice-cell">Action</th></tr></thead><tbody><?php foreach ($bioRows as $bioRow): ?><tr class="bio-row"><td class="text-cell"><input type="text" class="bio-determinant" value="<?php echo $esc($bioRow['determinant']); ?>" placeholder="Determinant / test name"></td><td class="text-cell"><input type="text" class="bio-baseline" value="<?php echo $esc($bioRow['baseline']); ?>" placeholder="Baseline result"></td><td class="text-cell"><input type="text" class="bio-annual" value="<?php echo $esc($bioRow['annual']); ?>" placeholder="Annual result"></td><td class="choice-cell"><button type="button" class="small-btn bio-remove" title="Delete row" aria-label="Delete row"><svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M10 11v6"></path><path d="M14 11v6"></path><path d="M6 7l1 13h10l1-13"></path><path d="M9 7V4h6v3"></path></svg></button></td></tr><?php endforeach; ?></tbody></table></div><div class="table-actions"><button type="button" class="small-btn" id="addBioRow">+ Add Biological Result</button></div><div class="file-upload-panel"><div><h4>Blood Result Files</h4></div><div class="file-upload-list" id="bloodResultFileList"><?php foreach ($uploadedBloodResultFiles as $bloodResultFile): ?><div class="file-upload-row" data-existing-file="<?php echo $esc($bloodResultFile); ?>"><div class="file-link-shell"><a href="<?php echo $esc(asset('storage/' . ltrim($bloodResultFile, '/'))); ?>" target="_blank" rel="noopener"><?php echo $esc(basename($bloodResultFile)); ?></a></div><?php if (! $isReadOnly): ?><button type="button" class="file-row-delete" data-remove-existing-file="<?php echo $esc($bloodResultFile); ?>">Delete</button><?php endif; ?></div><?php endforeach; ?></div><?php if (! $isReadOnly): ?><div class="file-upload-actions"><button type="button" class="file-upload-add" id="addBloodResultFile">+ Add File</button></div><?php endif; ?></div></section><section class="card" data-section-index="7" id="section-respirator"><?php $done = !empty($sectionStatuses['respirator']); ?><div class="card-head"><div><h3>Fitness to Wear Respirator</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="exam-table-wrap"><table class="exam-table"><thead><tr><th style="width:35%;">Conclusion on fitness to wear respirator</th><th class="choice-cell">Fit</th><th class="choice-cell">Not fit</th><th>Please justify</th></tr></thead><tbody><tr><th>Respirator fitness</th><td class="choice-cell"><input type="radio" name="fitness_result" value="Fit" <?php echo old('fitness_result', $fitnessRespirator->fitness_result ?? '') === 'Fit' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="fitness_result" value="Not fit" <?php echo old('fitness_result', $fitnessRespirator->fitness_result ?? '') === 'Not fit' ? 'checked' : ''; ?>></td><td class="text-cell"><textarea name="fitness_justification"><?php echo $esc(old('fitness_justification', $fitnessRespirator->fitness_justification ?? '')); ?></textarea></td></tr></tbody></table></div></section>
<section class="card" data-section-index="8" id="section-findings"><?php $done = !empty($sectionStatuses['findings']); ?><div class="card-head"><div><h3>MS Findings</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="exam-table-wrap"><table class="exam-table"><thead><tr><th>MS Finding</th><th class="choice-cell">Yes</th><th class="choice-cell">No</th><th class="choice-cell">Work Related Yes</th><th class="choice-cell">Work Related No</th></tr></thead><tbody><tr><th>History of health effects due to chemical exposure</th><td class="choice-cell"><input type="radio" name="history_of_health" value="Yes" <?php echo old('history_of_health', $msFindings->history_of_health ?? '') === 'Yes' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="history_of_health" value="No" <?php echo old('history_of_health', $msFindings->history_of_health ?? '') === 'No' ? 'checked' : ''; ?>></td><td class="na-cell" colspan="2">Not applicable</td></tr><tr><th>Clinical findings</th><td class="choice-cell"><input type="radio" name="clinical_findings" value="Yes" <?php echo old('clinical_findings', $msFindings->clinical_findings ?? '') === 'Yes' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="clinical_findings" value="No" <?php echo old('clinical_findings', $msFindings->clinical_findings ?? '') === 'No' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="CF_work_related" value="Yes" <?php echo old('CF_work_related', $msFindings->CF_work_related ?? '') === 'Yes' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="CF_work_related" value="No" <?php echo old('CF_work_related', $msFindings->CF_work_related ?? '') === 'No' ? 'checked' : ''; ?>></td></tr><tr><th>Target organ function test results</th><td class="choice-cell"><input type="radio" name="target_organ" value="Yes" <?php echo old('target_organ', $msFindings->target_organ ?? '') === 'Yes' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="target_organ" value="No" <?php echo old('target_organ', $msFindings->target_organ ?? '') === 'No' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="TO_work_related" value="Yes" <?php echo old('TO_work_related', $msFindings->TO_work_related ?? '') === 'Yes' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="TO_work_related" value="No" <?php echo old('TO_work_related', $msFindings->TO_work_related ?? '') === 'No' ? 'checked' : ''; ?>></td></tr><tr><th>BEI determinant (BM/BEM)</th><td class="choice-cell"><input type="radio" name="biological_monitoring" value="Yes" <?php echo old('biological_monitoring', $msFindings->biological_monitoring ?? '') === 'Yes' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="biological_monitoring" value="No" <?php echo old('biological_monitoring', $msFindings->biological_monitoring ?? '') === 'No' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="BM_work_related" value="Yes" <?php echo old('BM_work_related', $msFindings->BM_work_related ?? '') === 'Yes' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="BM_work_related" value="No" <?php echo old('BM_work_related', $msFindings->BM_work_related ?? '') === 'No' ? 'checked' : ''; ?>></td></tr><tr><th>Pregnancy / Breastfeeding</th><td class="choice-cell"><input type="radio" name="pregnancy_breastFeding" value="Yes" <?php echo old('pregnancy_breastFeding', $msFindings->pregnancy_breastFeding ?? '') === 'Yes' ? 'checked' : ''; ?>></td><td class="choice-cell"><input type="radio" name="pregnancy_breastFeding" value="No" <?php echo old('pregnancy_breastFeding', $msFindings->pregnancy_breastFeding ?? '') === 'No' ? 'checked' : ''; ?>></td><td class="na-cell" colspan="2">Not applicable</td></tr></tbody></table></div><div class="fit-row"><strong>Conclusion of fitness to work</strong><label><input type="radio" name="conclusion_fitness" value="Fit" <?php echo old('conclusion_fitness', $msFindings->conclusion_fitness ?? '') === 'Fit' ? 'checked' : ''; ?>> Fit</label><label><input type="radio" name="conclusion_fitness" value="Not Fit" <?php echo old('conclusion_fitness', $msFindings->conclusion_fitness ?? '') === 'Not Fit' ? 'checked' : ''; ?>> Not Fit</label></div></section>
  <section class="card" data-section-index="9" id="section-recommendation"><?php $done = !empty($sectionStatuses['recommendation']); ?><div class="card-head"><div><h3>Recommendation</h3></div><span class="badge <?php echo $done ? 'done' : 'missing'; ?>"><?php echo $done ? 'Completed' : 'Incomplete'; ?></span></div><div class="grid"><div class="field full"><label>Recommendation Type</label><div class="recommendation-grid"><?php foreach ($recommendationOptions as $recommendationOption): ?><label class="recommendation-option"><input type="checkbox" name="recommendation_types[]" value="<?php echo $esc($recommendationOption); ?>" <?php echo in_array($recommendationOption, $selectedRecommendationTypes, true) ? 'checked' : ''; ?>><span><?php echo $esc($recommendationOption); ?></span></label><?php endforeach; ?><label class="recommendation-option"><input type="checkbox" name="recommendation_type_other_enabled" value="1" id="recommendationTypeOtherToggle" <?php echo $hasRecommendationOther ? 'checked' : ''; ?>><span>Other</span></label></div></div><div class="field full" id="recommendationTypeOtherWrap" style="<?php echo $hasRecommendationOther ? '' : 'display:none;'; ?>"><label>Other Recommendation</label><input type="text" name="recommendation_type_other" id="recommendationTypeOtherInput" value="<?php echo $esc($recommendationOtherValue); ?>" placeholder="Enter other recommendation"></div><div class="field" id="recommendationMrpStartWrap"><label>MRP Start Date</label><input type="date" name="MRPdate_start" id="recommendationMrpStartInput" value="<?php echo $esc(old('MRPdate_start', $recommendationData->MRPdate_start ?? '')); ?>"></div><div class="field" id="recommendationMrpEndWrap"><label>MRP End Date</label><input type="date" name="MRPdate_end" id="recommendationMrpEndInput" value="<?php echo $esc(old('MRPdate_end', $recommendationData->MRPdate_end ?? '')); ?>"></div><div class="field" id="recommendationNextReviewWrap"><label>Next Review Date</label><input type="date" name="nextReview_date" id="recommendationNextReviewInput" value="<?php echo $esc(old('nextReview_date', $recommendationData->nextReview_date ?? '')); ?>"></div><div class="field full"><label>Notes</label><textarea name="notes"><?php echo $esc(old('notes', $recommendationData->notes ?? '')); ?></textarea></div><div class="field full"><input type="hidden" name="recommendation_employee_signature" id="recommendation_employee_signature" value="<?php echo $esc($recommendationEmployeeSignature); ?>"><input type="hidden" name="recommendation_ack_date" id="recommendation_ack_date" value="<?php echo $esc($recommendationAckDate); ?>"><div class="acknowledgement-block"><div class="acknowledgement-text">The implication of the above results has been explained to me by the OHD.</div><div class="acknowledgement-top"><div class="signature-panel"><div>Signature of the patient:</div><div class="signature-pad-inline"><canvas id="recommendationSignaturePad"></canvas></div><div class="signature-inline-actions"><button class="btn small signature-clear-btn" type="button" id="recommendationSignatureClear">Clear</button></div><div>Date: <span class="ack-date-text" id="recommendationAckDateText"><?php echo $esc($formatDateDisplay($recommendationAckDate) !== '' ? $formatDateDisplay($recommendationAckDate) : 'NA'); ?></span></div></div></div><div class="ack-line"><div class="ack-label">Name of Occupational Health Doctor</div><div class="ack-value"><?php echo $esc($doctorNameDisplay !== '' ? $doctorNameDisplay : 'NA'); ?></div></div><div class="ack-line"><div class="ack-label">Name of Clinic</div><div class="ack-value"><?php echo $esc($clinicDisplayName !== '' ? $clinicDisplayName : 'NA'); ?></div></div><div class="ack-grid"><div class="ack-line"><div class="ack-label">MMC / OHD Registration No.</div><div class="ack-value"><?php echo $esc($doctorRegistrationDisplay !== '' ? $doctorRegistrationDisplay : 'NA'); ?></div></div><div class="ack-line"><div class="ack-label">Clinic Tel. No.</div><div class="ack-value"><?php echo $esc($clinicTelephoneDisplay !== '' ? $clinicTelephoneDisplay : 'NA'); ?></div></div><div class="ack-line"><div class="ack-label">Fax No.</div><div class="ack-value"><?php echo $esc($clinicFaxDisplay !== '' ? $clinicFaxDisplay : 'NA'); ?></div></div><div class="ack-line"><div class="ack-label">Email</div><div class="ack-value"><?php echo $esc($clinicEmailDisplay !== '' ? $clinicEmailDisplay : 'NA'); ?></div></div></div></div></div></div></section>
</div><div class="actions"><div id="examWizardText">Section 1 of 10</div><div><a class="btn" id="examPrevLink" href="<?php echo $esc($stepBack); ?>">Back</a> <button class="btn" id="examPrevBtn" type="button" style="display:none;">Previous Section</button> <button class="btn" id="examDraftBtn" name="save_mode" value="draft" type="submit" <?php echo $isReadOnly ? 'style="display:none;"' : ''; ?>>Save Draft</button> <button class="next" id="examNextBtn" type="button" <?php echo $isReadOnly ? 'style="display:none;"' : ''; ?>>Next Section</button> <button class="next" id="examSaveBtn" name="save_mode" value="final" type="submit" style="display:none;">Save</button></div></div></form><?php if (! $isReadOnly): ?><template id="occupationalRowTemplate"><div class="repeat-card" data-occup-row><div class="repeat-card-head"><div class="repeat-card-title">Past Company Record</div><button class="btn danger small" type="button" data-remove-occup-row>Delete</button></div><div class="grid"><label class="field">Job Title<input type="text" name="occup_job_title[]"></label><label class="field">Company Name<input type="text" name="occup_company_name[]"></label><div class="field full"><div class="current-company-grid"><label class="field">Date of Starting Employment<input type="date" name="start_employment_date[]"></label><label class="field">Employment Duration<input type="text" name="employment_duration[]"></label><label class="field">Chemical Exposure Duration<input type="text" name="chemical_exposure_duration[]"></label></div></div><label class="field full">Chemical Exposure Incidents<textarea name="chemical_exposure_incidents[]"></textarea></label></div></div></template><template id="targetTestRowTemplate"><tr data-target-test-row><th><div class="target-test-label"><input type="text" class="target-test-name-input" name="other_target_test_name[]" placeholder="Insert test name"><button type="button" class="target-test-remove" data-remove-target-test-row>&times;</button></div></th><td class="choice-cell"><input type="radio" name="other_target_test_result[__INDEX__]" value="Normal"></td><td class="choice-cell"><input type="radio" name="other_target_test_result[__INDEX__]" value="Abnormal"></td><td class="text-cell"><textarea name="other_target_test_comments[]" placeholder="Comments / findings"></textarea></td></tr></template><?php endif; ?></section></div></div><script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script><script>(function(){
var cards=Array.prototype.slice.call(document.querySelectorAll('.card[data-section-index]'));
var navItems=Array.prototype.slice.call(document.querySelectorAll('.status-item'));
var statusMenu=document.getElementById('examStatusMenu');
var statusToggle=document.getElementById('examStatusToggle');
var statusToggleLabel=document.getElementById('examStatusToggleLabel');
var statusToggleMeta=document.getElementById('examStatusToggleMeta');
var statusToggleIcon=document.getElementById('examStatusToggleIcon');
var prevBtn=document.getElementById('examPrevBtn');
var draftBtn=document.getElementById('examDraftBtn');
var nextBtn=document.getElementById('examNextBtn');
var saveBtn=document.getElementById('examSaveBtn');
var prevLink=document.getElementById('examPrevLink');
var text=document.getElementById('examWizardText');
var form=document.getElementById('surveillanceExamForm');
var pageTop=document.getElementById('examPageTop');
var pageTitle=document.getElementById('examPageTitle');
var appPage=document.querySelector('.app-page');
var addBioRowBtn=document.getElementById('addBioRow');
var bioTableBody=document.querySelector('#bioMonitoringTable tbody');
var baselineStore=document.getElementById('baselineResultsStore');
var annualStore=document.getElementById('baselineAnnualStore');
var historyAllNoToggles=Array.prototype.slice.call(document.querySelectorAll('[data-history-all-no]'));
var bloodResultFileList=document.getElementById('bloodResultFileList');
var addBloodResultFileBtn=document.getElementById('addBloodResultFile');
var occupationalList=document.getElementById('occupationalHistoryList');
var occupationalTemplate=document.getElementById('occupationalRowTemplate');
var addOccupationalRowBtn=document.getElementById('addOccupationalRow');
var targetOrganTableBody=document.querySelector('#targetOrganTable tbody');
var targetTestRowTemplate=document.getElementById('targetTestRowTemplate');
var addTargetTestRowBtn=document.getElementById('addTargetTestRow');
var removedBloodResultFilesInput=document.getElementById('removedBloodResultFilesInput');
var surveillanceIdInput=document.getElementById('surveillanceIdInput');
var declarationIdInput=document.getElementById('declarationIdInput');
var chemicalStoreUrl='<?php echo $esc(route('surveillance.chemical-option.store')); ?>';
var csrfTokenInput=form?form.querySelector('input[name="_token"]'):null;
var chemicalInput=document.getElementById('chemicalSearchInput');
var chemicalValueInput=document.getElementById('chemicalValueInput');
var chemicalTriggerLabel=document.getElementById('chemicalTriggerLabel');
var chemicalDropdown=document.getElementById('chemicalDropdown');
var chemicalOptionsList=document.getElementById('chemicalOptionsList');
var chemicalEmptyState=document.getElementById('chemicalEmptyState');
var chemicalToggleBtn=document.getElementById('toggleChemicalDropdown');
var addChemicalOptionBtn=document.getElementById('addChemicalOption');
var chemicalAddForm=document.getElementById('chemicalAddForm');
var chemicalNewInput=document.getElementById('chemicalNewInput');
var chemicalAddCancel=document.getElementById('chemicalAddCancel');
var chemicalAddSave=document.getElementById('chemicalAddSave');
var recommendationOtherToggle=document.getElementById('recommendationTypeOtherToggle');
var biologicalManualCompleteInput=document.getElementById('biologicalManualCompleteInput');
var recommendationOtherWrap=document.getElementById('recommendationTypeOtherWrap');
var recommendationOtherInput=document.getElementById('recommendationTypeOtherInput');
var recommendationMrpStartWrap=document.getElementById('recommendationMrpStartWrap');
var recommendationMrpEndWrap=document.getElementById('recommendationMrpEndWrap');
var recommendationNextReviewWrap=document.getElementById('recommendationNextReviewWrap');
var recommendationMrpStartInput=document.getElementById('recommendationMrpStartInput');
var recommendationMrpEndInput=document.getElementById('recommendationMrpEndInput');
var recommendationNextReviewInput=document.getElementById('recommendationNextReviewInput');
var recommendationSignatureInput=document.getElementById('recommendation_employee_signature');
var recommendationAckDateInput=document.getElementById('recommendation_ack_date');
var recommendationAckDateText=document.getElementById('recommendationAckDateText');
var recommendationSignatureClear=document.getElementById('recommendationSignatureClear');
var saveModeInput=document.getElementById('saveModeInput');
var sectionKeys=['patient','chemical','history','clinical','physical','target','biological','respirator','findings','recommendation'];
var current=<?php echo (int) $initialSectionIndex; ?>;
var isSaving=false;
var isReadOnly=form.getAttribute('data-readonly')==='1';
if(!cards.length||!form){return;}
if(isReadOnly){
    Array.prototype.slice.call(form.querySelectorAll('input, select, textarea, button')).forEach(function(element){
        if(!element.name && element.id !== 'examPrevBtn' && element.id !== 'examPrevLink'){ }
        if(element.type === 'hidden'){return;}
        if(element.type === 'file'){element.disabled=true; return;}
        if(element.id === 'examPrevBtn' || element.id === 'examPrevLink'){return;}
        if(element.id === 'examNextBtn' || element.id === 'examSaveBtn' || element.id === 'examDraftBtn' || element.id === 'addBioRow' || element.id === 'toggleChemicalDropdown' || element.id === 'addChemicalOption' || element.id === 'chemicalAddCancel' || element.id === 'chemicalAddSave' || element.id === 'recommendationSignatureClear'){element.style.display='none'; return;}
        if(element.classList && element.classList.contains('bio-remove')){element.style.display='none'; return;}
        if(element.tagName === 'SELECT' || element.type === 'radio' || element.type === 'checkbox'){element.disabled=true; return;}
        element.readOnly=true;
    });
}
function formatDisplayDate(value){if(!value){return '';}var parts=String(value).split('-');if(parts.length===3){return parts[2]+'/'+parts[1]+'/'+parts[0];}return String(value);}
function todayIsoDate(){var date=new Date();var month=String(date.getMonth()+1).padStart(2,'0');var day=String(date.getDate()).padStart(2,'0');return date.getFullYear()+'-'+month+'-'+day;}
function updateRecommendationDateDisplay(value){if(recommendationAckDateText){recommendationAckDateText.textContent=value?formatDisplayDate(value):'-';}}
function setupSignaturePad(canvasId,inputId){var canvas=document.getElementById(canvasId);var input=document.getElementById(inputId);if(!canvas||!input||typeof SignaturePad==='undefined'){return null;}var pad=new SignaturePad(canvas,{minWidth:1.5,maxWidth:2.5,penColor:'#111827'});function restoreFromInput(){if(input.value&&input.value.indexOf('data:image')===0){try{pad.fromDataURL(input.value);}catch(error){}}}function resize(){var rect=canvas.getBoundingClientRect();if(!rect.width||!rect.height){return;}var previousValue=input.value||'';var ratio=Math.max(window.devicePixelRatio||1,1);canvas.width=rect.width*ratio;canvas.height=rect.height*ratio;canvas.getContext('2d').scale(ratio,ratio);pad.clear();if(previousValue.indexOf('data:image')===0){try{pad.fromDataURL(previousValue);}catch(error){}}}window.addEventListener('resize',resize);if(isReadOnly){pad.off();canvas.style.pointerEvents='none';}return{clear:function(){pad.clear();input.value='';},save:function(){if(!pad.isEmpty()){input.value=pad.toDataURL('image/png');}},isEmpty:function(){return pad.isEmpty();},hasValue:function(){return (input.value||'').trim()!=='';},resize:resize,restore:restoreFromInput};}
function sync(){cards.forEach(function(card,index){card.classList.toggle('is-active',index===current);});navItems.forEach(function(item,index){item.classList.toggle('is-current',index===current);});if(statusToggleLabel&&navItems[current]){var navStrong=navItems[current].querySelector('.status-text strong');var navMeta=navItems[current].querySelector('.status-text span');var navIcon=navItems[current].querySelector('.status-icon');statusToggleLabel.textContent=navStrong?navStrong.textContent:'Sections';statusToggleMeta.textContent=navMeta?navMeta.textContent:'Choose a section';if(statusToggleIcon&&navIcon){statusToggleIcon.className=navIcon.className;statusToggleIcon.innerHTML=navIcon.innerHTML;}}if(pageTop){pageTop.style.display=current===0?'block':'none';}if(pageTitle&&current===0){pageTitle.textContent='<?php echo $esc($isReadOnly ? 'View Surveillance Examination' : ($pageMode === 'edit' ? 'Edit Surveillance Examination' : 'Surveillance Examination')); ?>';}if(prevBtn){prevBtn.style.display=current>0?'inline-flex':'none';}if(prevLink){prevLink.style.display=current===0?'inline-flex':'none';}if(draftBtn){draftBtn.style.display=!isReadOnly?'inline-flex':'none';}if(nextBtn){nextBtn.style.display=current<cards.length-1?'inline-flex':'none';}if(saveBtn){saveBtn.style.display=current===cards.length-1&&!isReadOnly?'inline-flex':'none';}if(text){text.textContent='Section '+(current+1)+' of '+cards.length;}window.requestAnimationFrame(function(){if(cards[current]&&cards[current].id==='section-recommendation'&&recommendationSigner){recommendationSigner.resize();}if(appPage){appPage.scrollTo({top:0,behavior:'smooth'});return;}window.scrollTo({top:0,behavior:'smooth'});});} 
function setSectionStatus(index, done){var item=navItems[index];var card=cards[index];if(item){var icon=item.querySelector('.status-icon');var label=item.querySelector('.status-text span');if(icon){icon.classList.remove('ok','bad');icon.classList.add(done?'ok':'bad');icon.innerHTML=done?'&#10003;':'!';}if(label){label.textContent=done?'Completed':'Incomplete';}}if(card){var badge=card.querySelector('.badge');if(badge){badge.classList.remove('done','missing');badge.classList.add(done?'done':'missing');badge.textContent=done?'Completed':'Incomplete';}}}
function updateSectionStatuses(sectionStatuses){if(!sectionStatuses){return;}sectionKeys.forEach(function(key,index){setSectionStatus(index,!!sectionStatuses[key]);});}
function fieldValue(selector){var element=form.querySelector(selector);if(!element){return '';}return (element.value||'').trim();}
function radioValue(name){var checked=form.querySelector('input[name="'+name+'"]:checked');return checked?(checked.value||'').trim():'';}
function everyFilled(names, resolver){return names.every(function(name){var value=resolver(name);return value!=='';});}
function recommendationSelected(){return Array.prototype.slice.call(form.querySelectorAll('input[name="recommendation_types[]"]:checked')).map(function(input){return (input.value||'').trim();}).filter(function(value){return value!=='';});}
function hasPermanentMedicalRemovalProtection(){return recommendationSelected().indexOf('Permanent Medical Removal Protection')!==-1;}
function syncRecommendationOther(){if(!recommendationOtherToggle||!recommendationOtherWrap||!recommendationOtherInput){return;}var enabled=!!recommendationOtherToggle.checked;recommendationOtherWrap.style.display=enabled?'':'none';if(!enabled){recommendationOtherInput.value='';}}
function syncRecommendationDates(){var needsMrpDates=hasPermanentMedicalRemovalProtection();if(recommendationMrpStartWrap){recommendationMrpStartWrap.style.display=needsMrpDates?'':'none';}if(recommendationMrpEndWrap){recommendationMrpEndWrap.style.display=needsMrpDates?'':'none';}if(recommendationNextReviewWrap){recommendationNextReviewWrap.style.display='';}if(recommendationMrpStartInput){recommendationMrpStartInput.required=needsMrpDates;if(!needsMrpDates){recommendationMrpStartInput.value='';}}if(recommendationMrpEndInput){recommendationMrpEndInput.required=needsMrpDates;if(!needsMrpDates){recommendationMrpEndInput.value='';}}if(recommendationNextReviewInput){recommendationNextReviewInput.required=true;}}
function updateBloodPressureReading(){var systolic=parseFloat(fieldValue('input[name="bp_systolic"]'));var diastolic=parseFloat(fieldValue('input[name="bp_distolic"]'));var note=document.getElementById('bpReading');if(!note){return;}note.className='reading-note';if(isNaN(systolic)||isNaN(diastolic)){note.textContent='Blood pressure category will appear here.';return;}var state='is-normal';var text='Normal Blood Pressure';if(systolic>=180||diastolic>=120){state='is-crisis';text='Hypertensive Crisis';}else if(systolic>=140||diastolic>=90){state='is-stage2';text='Stage 2 Hypertension';}else if((systolic>=130&&systolic<=139)||(diastolic>=80&&diastolic<=89)){state='is-stage1';text='Stage 1 Hypertension';}else if(systolic>=120&&systolic<=129&&diastolic<80){state='is-elevated';text='Elevated Blood Pressure';}note.classList.add(state);note.textContent=text;}
function updateBmiReading(){var weight=parseFloat(fieldValue('input[name="weight"]'));var height=parseFloat(fieldValue('input[name="height"]'));var bmiInput=form.querySelector('input[name="BMI"]');var note=document.getElementById('bmiReading');if(!bmiInput||!note){return;}note.className='reading-note';var bmiValue=parseFloat((bmiInput.value||'').trim());if(!isNaN(weight)&&!isNaN(height)&&height>0){var calculated=weight/Math.pow(height/100,2);if(isFinite(calculated)){bmiValue=calculated;bmiInput.value=calculated.toFixed(1);}}else if(isNaN(weight)||isNaN(height)||height<=0){bmiInput.value='';}if(isNaN(bmiValue)){note.textContent='BMI category will appear here.';return;}var result='Normal';var state='is-normal';if(bmiValue<18.5){result='Underweight';state='is-underweight';}else if(bmiValue<23){result='Normal';state='is-normal';}else if(bmiValue<25){result='Overweight';state='is-overweight';}else if(bmiValue<30){result='Overweight';state='is-elevated';}else{result='Obese';state='is-obese';}note.classList.add(state);note.textContent=result;}
function historyGroupInputs(groupKey){return Array.prototype.slice.call(document.querySelectorAll('input[data-history-group-radio="'+groupKey+'"]'));}
function syncHistoryAllNoToggle(groupKey){var toggle=document.querySelector('[data-history-all-no="'+groupKey+'"]');if(!toggle){return;}var radios=historyGroupInputs(groupKey);var names={};var allNo=true;radios.forEach(function(radio){if(!radio.name||names[radio.name]){return;}names[radio.name]=true;var checked=document.querySelector('input[name="'+radio.name+'"]:checked');if(!checked||checked.value!=='No'){allNo=false;}});toggle.checked=allNo&&Object.keys(names).length>0;}
function applyHistoryAllNo(groupKey){var names={};historyGroupInputs(groupKey).forEach(function(radio){if(radio.name){names[radio.name]=true;}});Object.keys(names).forEach(function(name){var noInput=document.querySelector('input[name="'+name+'"][value="No"]');if(noInput){noInput.checked=true;}});syncHistoryAllNoToggle(groupKey);refreshVisibleStatuses();}
function syncOccupationalTitles(){if(!occupationalList){return;}var rows=occupationalList.querySelectorAll('[data-occup-row]');rows.forEach(function(row,index){var title=row.querySelector('.repeat-card-title');if(title){title.textContent='Past Company Record '+(index+1);}var remove=row.querySelector('[data-remove-occup-row]');if(remove){remove.disabled=rows.length===1;remove.style.opacity=remove.disabled?'0.5':'1';}});}
function bindOccupationalRow(row){if(!row){return;}var remove=row.querySelector('[data-remove-occup-row]');if(remove){remove.addEventListener('click',function(){if(!occupationalList||occupationalList.querySelectorAll('[data-occup-row]').length<=1){return;}row.remove();syncOccupationalTitles();refreshVisibleStatuses();});}}
function reindexTargetTestRows(){if(!targetOrganTableBody){return;}Array.prototype.slice.call(targetOrganTableBody.querySelectorAll('[data-target-test-row]')).forEach(function(row,index){Array.prototype.slice.call(row.querySelectorAll('input[type="radio"]')).forEach(function(input){if(input.value==='Normal'||input.value==='Abnormal'){input.name='other_target_test_result['+index+']';}});});}
function bindTargetTestRow(row){if(!row){return;}var remove=row.querySelector('[data-remove-target-test-row]');if(remove){remove.addEventListener('click',function(){row.remove();reindexTargetTestRows();refreshVisibleStatuses();});}}
function removedBloodResultFiles(){if(!removedBloodResultFilesInput||!removedBloodResultFilesInput.value){return [];}return removedBloodResultFilesInput.value.split('\n').map(function(value){return value.trim();}).filter(function(value){return value!=='';});}
function syncRemovedBloodResultFiles(values){if(!removedBloodResultFilesInput){return;}removedBloodResultFilesInput.value=values.join('\n');}
function activeExistingBloodResultFiles(){if(!bloodResultFileList){return 0;}return bloodResultFileList.querySelectorAll('[data-existing-file]:not(.is-removed)').length;}
function activeNewBloodResultFiles(){if(!bloodResultFileList){return 0;}return Array.prototype.slice.call(bloodResultFileList.querySelectorAll('input[name="blood_result_files[]"]')).filter(function(input){return input.closest('.file-upload-row')&&!input.closest('.file-upload-row').classList.contains('is-removed')&&input.files&&input.files.length>0;}).length;}
function totalVisibleBloodResultRows(){if(!bloodResultFileList){return 0;}return bloodResultFileList.querySelectorAll('.file-upload-row:not(.is-removed)').length;}
function markBloodResultFileRemoved(path){if(!path||!bloodResultFileList){return;}var values=removedBloodResultFiles();if(values.indexOf(path)===-1){values.push(path);syncRemovedBloodResultFiles(values);}Array.prototype.slice.call(bloodResultFileList.querySelectorAll('[data-existing-file]')).forEach(function(row){if((row.getAttribute('data-existing-file')||'')!==path){return;}row.classList.add('is-removed');});}
function buildBloodResultFileRow(){var row=document.createElement('div');row.className='file-upload-row';row.innerHTML='<div class="file-input-shell"><input type="file" name="blood_result_files[]"></div><button type="button" class="file-row-delete" data-remove-new-file=\"1\">Delete</button>';return row;}
function ensureBloodResultFileRow(){if(isReadOnly||!bloodResultFileList){return;}if(totalVisibleBloodResultRows()>0){return;}bloodResultFileList.appendChild(buildBloodResultFileRow());}
function initializeMedicalHistoryRows(){Array.prototype.slice.call(document.querySelectorAll('[data-medical-history-row]')).forEach(function(row){Array.prototype.slice.call(row.querySelectorAll('[data-medical-history-toggle]')).forEach(function(input){input.addEventListener('change',function(){refreshVisibleStatuses();});});});}
function patientSectionComplete(){var requiredRadioFields=['diagnosed_history_status','medication_history_status','admitted_history_status','family_history_status','others_history_status','smoking_history','vaping_history','handling_of_chemical','sign_symptoms','chemical_poisoning','proper_PPE','PPE_usage'];if(!everyFilled(requiredRadioFields,radioValue)){return false;}if(fieldValue('input[name="current_job_title"]')===''||fieldValue('input[name="current_employment_duration"]')===''||fieldValue('input[name="current_chemical_exposure_duration"]')===''){return false;}var smokingHistory=radioValue('smoking_history');if((smokingHistory==='Current'||smokingHistory==='Ex-smoker')&&(fieldValue('input[name="years_of_smoking"]')===''||fieldValue('input[name="no_of_cigarettes"]')==='')){return false;}if(radioValue('vaping_history')==='Yes'&&fieldValue('input[name="years_of_vaping"]')===''){return false;}return true;}
function computeSectionCompletion(index){switch(index){case 0:return patientSectionComplete();case 1:return fieldValue('input[name="company_name"]')!==''&&fieldValue('input[name="chemicals"]')!==''&&fieldValue('select[name="examination_type"]')!==''&&fieldValue('input[name="examination_date"]')!=='';case 2:return radioValue('breathing_difficulty')!=='';case 3:return radioValue('result_clinical_findings')!=='';case 4:return fieldValue('input[name="weight"]')!==''&&fieldValue('input[name="height"]')!==''&&fieldValue('input[name="BMI"]')!=='';case 5:var hasRequiredBuiltIns=everyFilled(['blood_count_result','renal_function_result','liver_function_result'],radioValue);return hasRequiredBuiltIns&&fieldValue('input[name="spirometry_FEV1"]')!==''&&fieldValue('input[name="spirometry_FVC"]')!==''&&fieldValue('input[name="spirometry_FEV_FVC"]')!=='';case 6:if(biologicalManualCompleteInput&&biologicalManualCompleteInput.checked){return true;}buildBioPayload();var baselineLines=(baselineStore&&baselineStore.value?baselineStore.value.split(/\r\n|\r|\n/):[]).filter(function(line){return line.trim()!=='';});var annualLines=(annualStore&&annualStore.value?annualStore.value.split(/\r\n|\r|\n/):[]).filter(function(line){return line.trim()!=='';});var existingFiles=activeExistingBloodResultFiles()>0;var newFiles=activeNewBloodResultFiles()>0;if((existingFiles||newFiles)&&(!baselineLines.length||baselineLines.length===annualLines.length)){return true;}if(!baselineLines.length||baselineLines.length!==annualLines.length){return false;}return baselineLines.every(function(line,idx){var parts=line.split('::');return (parts[0]||'').trim()!==''&&(parts[1]||'').trim()!==''&&(annualLines[idx]||'').trim()!=='';});case 7:return radioValue('fitness_result')!=='';case 8:return radioValue('history_of_health')!==''&&radioValue('conclusion_fitness')!=='';case 9:var selectedRecommendations=recommendationSelected();var hasOtherRecommendation=recommendationOtherToggle&&recommendationOtherToggle.checked&&fieldValue('input[name="recommendation_type_other"]')!=='';var needsMrpDates=hasPermanentMedicalRemovalProtection();var hasRequiredDateValues=fieldValue('input[name="nextReview_date"]')!==''&&(!needsMrpDates||(fieldValue('input[name="MRPdate_start"]')!==''&&fieldValue('input[name="MRPdate_end"]')!==''));return (selectedRecommendations.length>0||hasOtherRecommendation)&&hasRequiredDateValues;default:return false;}}
function refreshVisibleStatuses(){sectionKeys.forEach(function(key,index){setSectionStatus(index,computeSectionCompletion(index));});}
function refreshBioRemoveButtons(){if(!bioTableBody){return;}var rows=bioTableBody.querySelectorAll('.bio-row');rows.forEach(function(row){var btn=row.querySelector('.bio-remove');if(btn){btn.style.visibility=rows.length>1?'visible':'hidden';}});} 
function buildBioPayload(){if(!bioTableBody||!baselineStore||!annualStore){return;}var baselineLines=[];var annualLines=[];bioTableBody.querySelectorAll('.bio-row').forEach(function(row){var determinant=(row.querySelector('.bio-determinant')||{}).value||'';var baseline=(row.querySelector('.bio-baseline')||{}).value||'';var annual=(row.querySelector('.bio-annual')||{}).value||'';if(determinant.trim()||baseline.trim()||annual.trim()){baselineLines.push(determinant.trim()+'::'+baseline.trim());annualLines.push(annual.trim());}});baselineStore.value=baselineLines.join('\n');annualStore.value=annualLines.join('\n');}
function persistExamData(){buildBioPayload();var payload=new FormData(form);payload.set('autosave','1');payload.set('save_mode','draft');isSaving=true;if(nextBtn){nextBtn.disabled=true;}return fetch(form.getAttribute('action'),{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN':csrfTokenInput?csrfTokenInput.value:''},body:payload}).then(function(response){if(!response.ok){throw new Error('Unable to save examination');}return response.json();}).then(function(data){if(surveillanceIdInput&&data.surveillance_id){surveillanceIdInput.value=data.surveillance_id;}if(declarationIdInput&&data.declaration_id){declarationIdInput.value=data.declaration_id;}updateSectionStatuses(data.sectionStatuses||{});return data;}).finally(function(){isSaving=false;if(nextBtn){nextBtn.disabled=false;}});}
if(addBioRowBtn&&bioTableBody){addBioRowBtn.addEventListener('click',function(){var row=document.createElement('tr');row.className='bio-row';row.innerHTML='<td class="text-cell"><input type="text" class="bio-determinant" placeholder="Determinant / test name"></td><td class="text-cell"><input type="text" class="bio-baseline" placeholder="Baseline result"></td><td class="text-cell"><input type="text" class="bio-annual" placeholder="Annual result"></td><td class="choice-cell"><button type="button" class="small-btn bio-remove" title="Delete row" aria-label="Delete row"><svg viewBox=\"0 0 24 24\"><path d=\"M4 7h16\"></path><path d=\"M10 11v6\"></path><path d=\"M14 11v6\"></path><path d=\"M6 7l1 13h10l1-13\"></path><path d=\"M9 7V4h6v3\"></path></svg></button></td>';bioTableBody.appendChild(row);refreshBioRemoveButtons();});bioTableBody.addEventListener('click',function(event){var btn=event.target.closest('.bio-remove');if(!btn){return;}var rows=bioTableBody.querySelectorAll('.bio-row');if(rows.length<=1){return;}btn.closest('.bio-row').remove();refreshBioRemoveButtons();});refreshBioRemoveButtons();}
if(occupationalList){occupationalList.querySelectorAll('[data-occup-row]').forEach(bindOccupationalRow);syncOccupationalTitles();}
if(addOccupationalRowBtn&&occupationalList&&occupationalTemplate){addOccupationalRowBtn.addEventListener('click',function(){var fragment=occupationalTemplate.content.cloneNode(true);occupationalList.appendChild(fragment);bindOccupationalRow(occupationalList.lastElementChild);syncOccupationalTitles();refreshVisibleStatuses();});}
if(targetOrganTableBody){targetOrganTableBody.querySelectorAll('[data-target-test-row]').forEach(bindTargetTestRow);reindexTargetTestRows();}
if(addTargetTestRowBtn&&targetOrganTableBody&&targetTestRowTemplate){addTargetTestRowBtn.addEventListener('click',function(){var nextIndex=targetOrganTableBody.querySelectorAll('[data-target-test-row]').length;var templateHtml=targetTestRowTemplate.innerHTML.replace(/__INDEX__/g,String(nextIndex));var tempContainer=document.createElement('tbody');tempContainer.innerHTML=templateHtml;var row=tempContainer.firstElementChild;var spirometryRow=targetOrganTableBody.lastElementChild;targetOrganTableBody.insertBefore(row,spirometryRow);bindTargetTestRow(row);reindexTargetTestRows();refreshVisibleStatuses();});}
if(addBloodResultFileBtn){addBloodResultFileBtn.addEventListener('click',function(){if(!bloodResultFileList){return;}bloodResultFileList.appendChild(buildBloodResultFileRow());refreshVisibleStatuses();});}
if(bloodResultFileList){bloodResultFileList.addEventListener('click',function(event){var existingButton=event.target.closest('[data-remove-existing-file]');if(existingButton){event.preventDefault();markBloodResultFileRemoved(existingButton.getAttribute('data-remove-existing-file')||'');ensureBloodResultFileRow();refreshVisibleStatuses();return;}var newButton=event.target.closest('[data-remove-new-file]');if(!newButton){return;}event.preventDefault();var row=newButton.closest('.file-upload-row');if(row){row.remove();}ensureBloodResultFileRow();refreshVisibleStatuses();});bloodResultFileList.addEventListener('change',function(event){if(event.target&&event.target.matches('input[name="blood_result_files[]"]')){refreshVisibleStatuses();}});}
initializeMedicalHistoryRows();
historyAllNoToggles.forEach(function(toggle){toggle.addEventListener('change',function(){if(toggle.checked){applyHistoryAllNo(toggle.getAttribute('data-history-all-no')||'');return;}syncHistoryAllNoToggle(toggle.getAttribute('data-history-all-no')||'');});syncHistoryAllNoToggle(toggle.getAttribute('data-history-all-no')||'');});
if(form){form.addEventListener('change',function(event){var groupKey=event.target&&event.target.getAttribute&&event.target.getAttribute('data-history-group-radio');if(groupKey){syncHistoryAllNoToggle(groupKey);}});}
function chemicalOptions(){return chemicalOptionsList?Array.prototype.slice.call(chemicalOptionsList.querySelectorAll('[data-chemical-option]')):[];}
function updateChemicalTrigger(value){if(!chemicalTriggerLabel||!chemicalToggleBtn){return;}var hasValue=(value||'').trim()!=='';chemicalTriggerLabel.textContent=hasValue?value:'Select chemical';chemicalToggleBtn.classList.toggle('is-placeholder',!hasValue);chemicalToggleBtn.setAttribute('aria-expanded',chemicalDropdown&&chemicalDropdown.classList.contains('is-open')?'true':'false');}
function filterChemicalOptions(){if(!chemicalInput){return;}var query=(chemicalInput.value||'').trim().toLowerCase();var visibleCount=0;chemicalOptions().forEach(function(option){var optionText=(option.getAttribute('data-chemical-option')||'').toLowerCase();var match=query===''||optionText.indexOf(query)!==-1;option.style.display=match?'':'none';if(match){visibleCount+=1;}});if(chemicalEmptyState){chemicalEmptyState.style.display=visibleCount===0?'':'none';}}
function openChemicalDropdown(){if(!chemicalDropdown){return;}chemicalDropdown.classList.add('is-open');filterChemicalOptions();updateChemicalTrigger(chemicalValueInput?chemicalValueInput.value:'');if(chemicalInput){chemicalInput.value=chemicalValueInput?chemicalValueInput.value:'';chemicalInput.focus();}}
function closeChemicalDropdown(){if(!chemicalDropdown){return;}chemicalDropdown.classList.remove('is-open');hideChemicalAddForm();updateChemicalTrigger(chemicalValueInput?chemicalValueInput.value:'');}
function selectChemicalOption(value){if(chemicalValueInput){chemicalValueInput.value=value;}if(chemicalInput){chemicalInput.value=value;}updateChemicalTrigger(value);closeChemicalDropdown();}
function appendChemicalOption(value){if(!chemicalOptionsList){return;}var existing=chemicalOptions().find(function(option){return (option.getAttribute('data-chemical-option')||'').toLowerCase()===value.toLowerCase();});if(existing){return existing;}var option=document.createElement('div');option.className='chemical-option';option.setAttribute('data-chemical-option',value);option.setAttribute('role','option');option.textContent=value;chemicalOptionsList.insertBefore(option, chemicalEmptyState || null);return option;}
function showChemicalAddForm(){if(!chemicalAddForm){return;}chemicalAddForm.style.display='grid';if(chemicalNewInput){chemicalNewInput.value=chemicalInput?chemicalInput.value:'';chemicalNewInput.focus();}}
function hideChemicalAddForm(){if(!chemicalAddForm){return;}chemicalAddForm.style.display='none';if(chemicalNewInput){chemicalNewInput.value='';}}
function addChemicalOption(valueOverride){if(!chemicalOptionsList){return Promise.resolve();}var value=(valueOverride!==undefined?valueOverride:(chemicalNewInput?chemicalNewInput.value:chemicalInput?chemicalInput.value:'')).trim();if(!value){return Promise.resolve();}appendChemicalOption(value);if(!chemicalStoreUrl||!window.fetch){selectChemicalOption(value);hideChemicalAddForm();return Promise.resolve();}return fetch(chemicalStoreUrl,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrfTokenInput?csrfTokenInput.value:'','Accept':'application/json'},body:JSON.stringify({chemical_name:value})}).then(function(response){if(!response.ok){throw new Error('Unable to save chemical');}return response.json();}).then(function(payload){var savedValue=(payload&&payload.chemical_name)?payload.chemical_name:value;appendChemicalOption(savedValue);selectChemicalOption(savedValue);hideChemicalAddForm();}).catch(function(){selectChemicalOption(value);hideChemicalAddForm();});}
updateChemicalTrigger(chemicalValueInput?chemicalValueInput.value:'');
if(addChemicalOptionBtn){addChemicalOptionBtn.addEventListener('click',function(){showChemicalAddForm();});}
if(chemicalAddCancel){chemicalAddCancel.addEventListener('click',function(){hideChemicalAddForm();});}
if(chemicalAddSave){chemicalAddSave.addEventListener('click',function(){addChemicalOption();});}
if(recommendationOtherToggle){recommendationOtherToggle.addEventListener('change',function(){syncRecommendationOther();refreshVisibleStatuses();});}
Array.prototype.slice.call(form.querySelectorAll('input[name="recommendation_types[]"]')).forEach(function(input){input.addEventListener('change',function(){syncRecommendationDates();refreshVisibleStatuses();});});
if(chemicalToggleBtn){chemicalToggleBtn.addEventListener('click',function(){if(!chemicalDropdown){return;}if(chemicalDropdown.classList.contains('is-open')){closeChemicalDropdown();return;}openChemicalDropdown();});}
if(chemicalInput){chemicalInput.addEventListener('input',filterChemicalOptions);chemicalInput.addEventListener('keydown',function(event){if(event.key==='Escape'){closeChemicalDropdown();}});}
if(chemicalNewInput){chemicalNewInput.addEventListener('keydown',function(event){if(event.key==='Enter'){event.preventDefault();addChemicalOption();}if(event.key==='Escape'){hideChemicalAddForm();}});}
if(chemicalDropdown){chemicalDropdown.addEventListener('click',function(event){var option=event.target.closest('[data-chemical-option]');if(!option){return;}selectChemicalOption(option.getAttribute('data-chemical-option')||option.textContent||'');});document.addEventListener('click',function(event){if(!chemicalDropdown.contains(event.target)&&event.target!==chemicalToggleBtn){closeChemicalDropdown();}});} 
var recommendationSigner=setupSignaturePad('recommendationSignaturePad','recommendation_employee_signature');
if(recommendationSignatureClear){recommendationSignatureClear.addEventListener('click',function(){if(recommendationSigner){recommendationSigner.clear();}if(recommendationAckDateInput){recommendationAckDateInput.value='';}updateRecommendationDateDisplay('');refreshVisibleStatuses();});}
if(form){form.addEventListener('submit',function(){buildBioPayload();});}
ensureBloodResultFileRow();
if(prevBtn){prevBtn.addEventListener('click',function(){if(current<=0){return;}if(isReadOnly){current-=1;sync();return;}if(isSaving){return;}persistExamData().then(function(){current-=1;sync();}).catch(function(){current-=1;sync();});});}
navItems.forEach(function(item,index){item.addEventListener('click',function(){if(statusMenu&&statusToggle){statusMenu.classList.remove('is-open');statusToggle.setAttribute('aria-expanded','false');}if(index===current){sync();return;}if(isReadOnly){current=index;sync();return;}if(isSaving){return;}persistExamData().then(function(){current=index;sync();}).catch(function(){current=index;sync();});});});
if(statusToggle&&statusMenu){statusToggle.addEventListener('click',function(event){event.stopPropagation();var isOpen=statusMenu.classList.toggle('is-open');statusToggle.setAttribute('aria-expanded',isOpen?'true':'false');});document.addEventListener('click',function(event){if(!statusMenu.contains(event.target)){statusMenu.classList.remove('is-open');statusToggle.setAttribute('aria-expanded','false');}});}
if(nextBtn){nextBtn.addEventListener('click',function(){if(current>=cards.length-1){return;}if(isReadOnly){current+=1;sync();return;}if(isSaving){return;}persistExamData().then(function(){current+=1;sync();}).catch(function(){current+=1;sync();});});}
refreshVisibleStatuses();
updateBloodPressureReading();
updateBmiReading();
syncRecommendationOther();
syncRecommendationDates();
updateRecommendationDateDisplay(recommendationAckDateInput?recommendationAckDateInput.value:'');
if(form){form.addEventListener('input',function(event){refreshVisibleStatuses();var name=(event.target&&event.target.name)||'';if(name==='bp_systolic'||name==='bp_distolic'){updateBloodPressureReading();}if(name==='weight'||name==='height'||name==='BMI'){updateBmiReading();}if(name==='recommendation_employee_signature'||name==='recommendation_ack_date'){updateRecommendationDateDisplay(recommendationAckDateInput?recommendationAckDateInput.value:'');}});form.addEventListener('change',function(event){refreshVisibleStatuses();var name=(event.target&&event.target.name)||'';if(name==='bp_systolic'||name==='bp_distolic'){updateBloodPressureReading();}if(name==='weight'||name==='height'||name==='BMI'){updateBmiReading();}if(name==='recommendation_employee_signature'||name==='recommendation_ack_date'){updateRecommendationDateDisplay(recommendationAckDateInput?recommendationAckDateInput.value:'');}});form.addEventListener('submit',function(event){var submitter=event.submitter||document.activeElement;var submitMode=(submitter&&submitter.value==='final')?'final':'draft';if(saveModeInput){saveModeInput.value=submitMode;}buildBioPayload();if(recommendationSigner&&!isReadOnly){if(!recommendationSigner.isEmpty()){recommendationSigner.save();if(recommendationAckDateInput&&!recommendationAckDateInput.value){recommendationAckDateInput.value=todayIsoDate();}}else if(recommendationSignatureInput&&recommendationSignatureInput.value.trim()===''&&recommendationAckDateInput){recommendationAckDateInput.value='';}updateRecommendationDateDisplay(recommendationAckDateInput?recommendationAckDateInput.value:'');}});}
if(isReadOnly&&form){
Array.prototype.slice.call(form.querySelectorAll('input, select, textarea, button')).forEach(function(element){
if(!element){return;}
var tagName=(element.tagName||'').toLowerCase();
var inputType=((element.getAttribute('type')||'').toLowerCase());
if(inputType==='hidden'){return;}
if(tagName==='textarea'||tagName==='input'){
if(['radio','checkbox','file','button','submit'].indexOf(inputType)!==-1){
element.disabled=true;
return;
}
element.readOnly=true;
return;
}
if(tagName==='select'||tagName==='button'){
element.disabled=true;
}
});
}
sync();
})();</script><?php medis_render_navigation_end(); ?>
</body></html>
