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

$candidateRows = collect();
if ($companyId > 0 && $folderDate !== '' && DB::getSchemaBuilder()->hasTable('declaration') && DB::getSchemaBuilder()->hasTable('chemical_information')) {
    $candidateRows = DB::table('declaration')
        ->join('employee', 'employee.employee_id', '=', 'declaration.employee_id')
        ->join('chemical_information', 'chemical_information.surveillance_id', '=', 'declaration.surveillance_id')
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
        ])
        ->orderBy('employee.employee_firstName')
        ->orderBy('employee.employee_lastName')
        ->get();
}

$chemicalOptions = $candidateRows
    ->groupBy(static fn ($row) => trim((string) ($row->chemicals ?? '')))
    ->map(static fn ($group) => $group->first())
    ->values();

$selectedCandidate = $candidateRows->first(function ($row) use ($declarationId, $employeeId, $surveillanceId) {
    return ((int) ($row->declaration_id ?? 0) === $declarationId && $declarationId > 0)
        || ((int) ($row->employee_id ?? 0) === $employeeId && $employeeId > 0)
        || ((int) ($row->surveillance_id ?? 0) === $surveillanceId && $surveillanceId > 0);
});

$shouldAutoSelectFirstCandidate = ! $selectedCandidate
    && ! request()->session()->hasOldInput()
    && ($createMode || $declarationId <= 0)
    && $candidateRows->isNotEmpty();

if ($shouldAutoSelectFirstCandidate) {
    $selectedCandidate = $candidateRows->first();
    $declarationId = (int) ($selectedCandidate->declaration_id ?? 0);
    $employeeId = (int) ($selectedCandidate->employee_id ?? 0);
    $companyId = (int) ($selectedCandidate->company_id ?? $companyId);
    $surveillanceId = (int) ($selectedCandidate->surveillance_id ?? 0);
}

$isFreshCreate = $createMode && ! $selectedCandidate && ! request()->session()->hasOldInput();

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

if (! $declaration && $selectedCandidate) {
    $declarationId = (int) ($selectedCandidate->declaration_id ?? 0);
    $employeeId = (int) ($selectedCandidate->employee_id ?? 0);
    $companyId = (int) ($selectedCandidate->company_id ?? 0);
    $surveillanceId = (int) ($selectedCandidate->surveillance_id ?? 0);
    $declaration = DB::table('declaration')->where('declaration_id', $declarationId)->first();
}

$surveillanceId = (int) ($declaration->surveillance_id ?? $surveillanceId);
$employeeId = (int) ($declaration->employee_id ?? $employeeId);
$companyId = (int) ($declaration->company_id ?? $companyId);

$employee = $employeeId > 0 ? DB::table('employee')->where('employee_id', $employeeId)->first() : null;
$company = $companyId > 0 ? DB::table('company')->where('company_id', $companyId)->first() : null;
$chemical = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('chemical_information')
    ? DB::table('chemical_information')->where('surveillance_id', $surveillanceId)->first()
    : null;
$summary = $surveillanceId > 0 && DB::getSchemaBuilder()->hasTable('summary_report')
    ? DB::table('summary_report')->where('surveillance_id', $surveillanceId)->first()
    : null;
$doctor = ! empty($declaration->doctor_id) && DB::getSchemaBuilder()->hasTable('doctor')
    ? DB::table('doctor')->where('doctor_id', $declaration->doctor_id)->first()
    : null;
$activeClinicId = (int) request()->session()->get('active_clinic_id', 0);
$clinicRecord = $activeClinicId > 0 && DB::getSchemaBuilder()->hasTable('clinic')
    ? DB::table('clinic')->where('clinic_id', $activeClinicId)->first()
    : null;
$statusMessage = (string) session('status', '');

$parseDate = static function ($value): ?Carbon {
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
$formatDate = static function ($value) use ($parseDate): string {
    $parsed = $parseDate($value);
    return $parsed ? $parsed->format('d M Y') : (trim((string) ($value ?? '')) !== '' ? (string) $value : '-');
};
$formatInputDate = static function ($value) use ($parseDate): string {
    $parsed = $parseDate($value);
    return $parsed ? $parsed->format('Y-m-d') : '';
};
$showValue = static function ($value, string $fallback = '-'): string {
    $text = trim((string) ($value ?? ''));
    return $text !== '' ? $text : $fallback;
};

$companyAddressLines = array_values(array_filter([
    trim((string) ($company->company_address ?? '')),
    trim(collect([
        trim((string) ($company->company_postcode ?? '')),
        trim((string) ($company->company_district ?? '')),
    ])->filter(static fn ($value) => $value !== '')->implode(', ')),
    trim(collect([
        trim((string) ($company->company_state ?? '')),
        'Malaysia',
    ])->filter(static fn ($value) => $value !== '')->implode(', ')),
], static fn ($value) => $value !== ''));
$companyAddressText = implode("\n", $companyAddressLines);
$companyAddressSingleLine = trim(preg_replace('/\s+/', ' ', implode(', ', $companyAddressLines)));

$doctorName = trim((string) (($doctor->doctor_firstName ?? '') . ' ' . ($doctor->doctor_lastName ?? '')));
$doctorRegistration = trim((string) ($doctor->OHD_registrationNo ?? $doctor->MMC_no ?? ''));
$clinicAddress = collect([
    trim((string) ($clinicRecord->clinic_address ?? '')),
    trim(collect([
        trim((string) ($clinicRecord->clinic_postcode ?? '')),
        trim((string) ($clinicRecord->clinic_district ?? '')),
    ])->filter(static fn ($value) => $value !== '')->implode(', ')),
    trim(collect([
        trim((string) ($clinicRecord->clinic_state ?? '')),
        'Malaysia',
    ])->filter(static fn ($value) => $value !== '')->implode(', ')),
])->filter(static fn ($value) => $value !== '')->implode("\n");

$indicationOptions = [
    'significant_personal_exposure' => 'Significant personal exposure (>= 50% PEL)',
    'reported_health_effects' => 'Reported health effects',
    'skin_absorption' => 'Skin absorption',
    'others' => 'Others',
];
$storedIndicationText = trim((string) ($summary->indication_CHRAreport ?? ''));
$storedIndicationFlags = [];
foreach ($indicationOptions as $key => $label) {
    if ($key !== 'others' && stripos($storedIndicationText, $label) !== false) {
        $storedIndicationFlags[] = $key;
    }
}
$storedIndicationOther = '';
if (preg_match('/Others:\s*(.+)$/im', $storedIndicationText, $matches) === 1) {
    $storedIndicationFlags[] = 'others';
    $storedIndicationOther = trim((string) ($matches[1] ?? ''));
} elseif (stripos($storedIndicationText, 'Others') !== false) {
    $storedIndicationFlags[] = 'others';
}

$screenSelectedIndications = array_values(array_filter((array) old('indication_flags', $storedIndicationFlags)));
$screenIndicationOther = old('indication_other', $storedIndicationOther);
$editableClinicAddress = old('clinic_practice_address', $clinicAddress);

$metrics = [
    [
        'title' => 'History of health effects due to chemical exposure',
        'prefix' => 'H',
        'normal_key' => 'no_ofWorkersNormal_H',
        'occ_key' => 'no_ofWorkersAbormal_OccupationalH',
        'non_occ_key' => null,
        'mrp_key' => null,
        'specify_key' => null,
    ],
    [
        'title' => 'Clinical findings',
        'prefix' => 'I',
        'normal_key' => 'no_ofWorkersNormal_I',
        'occ_key' => 'no_ofWorkersAbormal_OccupationalI',
        'non_occ_key' => 'no_ofWorkersAbormal_nonOccupationalI',
        'mrp_key' => 'no_ofWorkersRecommended_I',
        'specify_key' => null,
    ],
    [
        'title' => 'Target organ function test(s)',
        'prefix' => 'J',
        'normal_key' => 'no_ofWorkersNormal_J',
        'occ_key' => 'no_ofWorkersAbormal_OccupationalJ',
        'non_occ_key' => 'no_ofWorkersAbormal_nonOccupationalJ',
        'mrp_key' => 'no_ofWorkersRecommended_J',
        'specify_key' => 'specify_J',
    ],
    [
        'title' => 'BEI determinant (BM/BEM)',
        'prefix' => 'K',
        'normal_key' => 'no_ofWorkersNormal_K',
        'occ_key' => 'no_ofWorkersAbormal_OccupationalK',
        'non_occ_key' => 'no_ofWorkersAbormal_nonOccupationalK',
        'mrp_key' => 'no_ofWorkersRecommended_K',
        'specify_key' => 'specify_K',
    ],
];

$screen = [
    'chemical_name' => old('chemical_name', (string) ($summary->chemical_name ?? $chemical->chemicals ?? '')),
    'totalNo_workplace' => old('totalNo_workplace', (string) ($summary->totalNo_workplace ?? $company->total_workers ?? '')),
    'name_of_workUnit' => old('name_of_workUnit', (string) ($summary->name_of_workUnit ?? '')),
    'no_exposedWorkers' => old('no_exposedWorkers', (string) ($summary->no_exposedWorkers ?? '')),
    'totalNo_examined' => old('totalNo_examined', (string) ($summary->totalNo_examined ?? '')),
    'CHRA_reportNo' => old('CHRA_reportNo', (string) ($summary->CHRA_reportNo ?? '')),
    'name_of_laboratoy' => old('name_of_laboratoy', (string) ($summary->name_of_laboratoy ?? '')),
    'recommendation' => old('recommendation', (string) ($summary->recommendation ?? '')),
    'decision' => old('decision', (string) ($summary->decision ?? '')),
    'justification_decision' => old('justification_decision', (string) ($summary->justification_decision ?? '')),
    'date_of_implementation' => old('date_of_implementation', $formatInputDate((string) ($summary->date_of_implementation ?? ''))),
    'totalNo_MRP' => old('totalNo_MRP', (string) ($summary->totalNo_MRP ?? '')),
];

$specifyRows = [];
foreach (['specify_J', 'specify_K'] as $specifyKey) {
    $rawSpec = old($specifyKey, (string) ($summary->{$specifyKey} ?? ''));
    $lines = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), preg_split('/\r\n|\r|\n/', (string) $rawSpec) ?: []), static fn ($value) => $value !== ''));
    if ($lines === []) {
        $lines = [''];
    }
    $specifyRows[$specifyKey] = $lines;
}

$metricValues = [];
foreach ($metrics as $metric) {
    $metricValues[$metric['normal_key']] = old($metric['normal_key'], (string) ($summary->{$metric['normal_key']} ?? 0));
    $metricValues[$metric['occ_key']] = old($metric['occ_key'], (string) ($summary->{$metric['occ_key']} ?? 0));
    if ($metric['non_occ_key']) {
        $metricValues[$metric['non_occ_key']] = old($metric['non_occ_key'], (string) ($summary->{$metric['non_occ_key']} ?? 0));
    }
    if ($metric['mrp_key']) {
        $metricValues[$metric['mrp_key']] = old($metric['mrp_key'], (string) ($summary->{$metric['mrp_key']} ?? 0));
    }
    if ($metric['specify_key']) {
        $metricValues[$metric['specify_key']] = old($metric['specify_key'], (string) ($summary->{$metric['specify_key']} ?? ''));
    }
}

if ($isFreshCreate) {
    $screen = [
        'chemical_name' => '',
        'totalNo_workplace' => '',
        'name_of_workUnit' => '',
        'no_exposedWorkers' => '',
        'totalNo_examined' => '',
        'CHRA_reportNo' => '',
        'name_of_laboratoy' => '',
        'recommendation' => '',
        'decision' => '',
        'justification_decision' => '',
        'date_of_implementation' => '',
        'totalNo_MRP' => '',
    ];
    $screenSelectedIndications = [];
    $screenIndicationOther = '';
    $editableClinicAddress = '';
    foreach ($metrics as $metric) {
        $metricValues[$metric['normal_key']] = '';
        $metricValues[$metric['occ_key']] = '';
        if ($metric['non_occ_key']) {
            $metricValues[$metric['non_occ_key']] = '';
        }
        if ($metric['mrp_key']) {
            $metricValues[$metric['mrp_key']] = '';
        }
        if ($metric['specify_key']) {
            $metricValues[$metric['specify_key']] = '';
        }
    }
}

$selectedIndicationLabels = [];
foreach ($indicationOptions as $key => $label) {
    if (in_array($key, $screenSelectedIndications, true) && $key !== 'others') {
        $selectedIndicationLabels[] = $label;
    }
}
if (in_array('others', $screenSelectedIndications, true)) {
    $otherIndicationLabel = trim((string) $screenIndicationOther);
    $selectedIndicationLabels[] = $otherIndicationLabel !== '' ? 'Others - ' . $otherIndicationLabel : 'Others';
}
$indicationSummary = $selectedIndicationLabels !== [] ? implode(', ', $selectedIndicationLabels) : '-';
$decisionRows = [
    [
        'label' => 'Continue MS',
        'checked' => $screen['decision'] === 'Continue MS',
        'justification' => $screen['decision'] === 'Continue MS' ? $screen['justification_decision'] : '-',
        'date' => $screen['decision'] === 'Continue MS' ? $showValue($formatDate($screen['date_of_implementation'])) : '-',
    ],
    [
        'label' => 'Stop MS',
        'checked' => $screen['decision'] === 'Stop MS',
        'justification' => $screen['decision'] === 'Stop MS' ? $screen['justification_decision'] : '-',
        'date' => $screen['decision'] === 'Stop MS' ? $showValue($formatDate($screen['date_of_implementation'])) : '-',
    ],
];
$reportRecommendationText = trim((string) $screen['recommendation']) !== ''
    ? trim((string) $screen['recommendation'])
    : '-';
$reportDecisionText = trim((string) $screen['decision']) !== ''
    ? trim((string) $screen['decision'])
    : '-';
$reportDecisionJustificationText = trim((string) $screen['justification_decision']) !== ''
    ? trim((string) $screen['justification_decision'])
    : '-';
$reportDecisionDateText = trim((string) $screen['date_of_implementation']) !== ''
    ? $showValue($formatDate($screen['date_of_implementation']))
    : '-';
$downloadDateText = Carbon::now()->format('d/m/Y');

$readonly = $viewMode ? ' readonly' : '';
$disabled = $viewMode ? ' disabled' : '';
$printDate = $isFreshCreate ? '' : $showValue($formatDate($chemical->examination_date ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>USECHH 4 Summary Report</title>
</head>
<body>
<style>
body{margin:0;padding:0;background:#fff;color:#111827;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.4;overflow-x:hidden}
.page-shell{width:100%;max-width:none;margin:0;padding:0 32px 24px;box-sizing:border-box}
.card{background:transparent;border:0;border-radius:0;overflow:visible}
.report-header{padding:10px 0 24px;border-bottom:0}
.report-act{font-size:14px;font-weight:700;line-height:1.35;color:#0f172a}
.report-regulation{margin-top:4px;font-size:15px;font-weight:700;line-height:1.35;color:#0f172a}
.report-code{position:absolute;right:0;top:0;font-size:14px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.report-title{margin:14px 0 0;text-align:center;font-size:20px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#0f172a}
.section{padding:14px 0;border-top:1px solid #edf2ef}
.section:first-of-type{border-top:0}
.digital-form{padding:0;display:grid;gap:22px;min-width:0;width:100%;max-width:100%}
.digital-form-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:0}
.digital-form-title{margin:0;font-size:22px;font-weight:700;letter-spacing:.02em;color:#0f172a}
.digital-panel{padding:0;min-width:0;width:100%;max-width:100%}
.digital-panel-title{margin:0 0 12px;font-size:1rem;font-weight:700;color:#0f172a}
.section-divider{padding-top:8px}
.flash{margin:0 0 18px;padding:12px 14px;border:1px solid #cfe7d4;border-radius:14px;background:#f3fbf4;color:#1f5f35}
.selector-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:16px;align-items:end}
.selector-label,.field-label{display:block;margin-bottom:8px;font-size:.94rem;font-weight:700;color:#0f172a}
.selector-select,.field-input,.field-textarea{width:100%;padding:13px 15px;border:1px solid #cbd5e1;border-radius:16px;background:#fff;font:inherit;color:#0f172a;box-sizing:border-box;min-width:0;max-width:100%;overflow:hidden}
.field-input[readonly],.field-textarea[readonly]{background:#f8fafc;color:#334155}
.field-textarea{min-height:112px;resize:vertical}
.field-static{min-height:54px;padding:14px 16px;border:1px solid #dbe4ee;border-radius:16px;background:#f8fafc;white-space:pre-line;box-sizing:border-box}
.grid-two{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 22px}
.grid-three-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px 22px}
.grid-three{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px 22px}
.full{grid-column:1 / -1}
.summary-table-shell{overflow-x:auto;border:1px solid #dfe8e2;border-radius:22px;background:#fff}
.summary-table{width:100%;border-collapse:separate;border-spacing:0;min-width:0;table-layout:fixed}
.summary-table th,.summary-table td{padding:14px 14px;border-right:1px solid #dfe8e2;border-bottom:1px solid #dfe8e2;vertical-align:top}
.summary-table th:last-child,.summary-table td:last-child{border-right:0}
.summary-table tr:last-child td{border-bottom:0}
.summary-table thead th{background:#e8f3eb;color:#123524;font-size:.88rem;font-weight:700;text-align:left;word-break:break-word}
.inline-options{display:flex;flex-wrap:wrap;gap:18px 26px}
.inline-options.tight{gap:10px 24px}
.check-option,.radio-option{display:flex;align-items:center;gap:10px;font-weight:500}
.check-option input,.radio-option input{width:18px;height:18px;margin:0;accent-color:#389B5B}
.radio-stack{display:grid;gap:12px}
.result-table-shell{overflow-x:hidden;border:1px solid #dfe8e2;border-radius:22px;background:#fff}
.result-table{width:100%;border-collapse:separate;border-spacing:0;min-width:0;table-layout:fixed}
.result-table th,.result-table td{padding:14px 12px;border-right:1px solid #dfe8e2;border-bottom:1px solid #dfe8e2;vertical-align:top}
.result-table th:last-child,.result-table td:last-child{border-right:0}
.result-table thead th{background:#e8f3eb;color:#123524;font-size:.88rem;font-weight:700;text-align:center}
.result-table tbody td:first-child{width:28%;font-weight:700;background:#fbfdfb}
.result-table .table-input{width:100%;padding:11px 12px;border:1px solid #cbd5e1;border-radius:14px;background:#fff;font:inherit;color:#0f172a;box-sizing:border-box}
.result-table .table-input[readonly]{background:#f8fafc;color:#334155}
.result-table .table-textarea{width:100%;min-height:48px;padding:11px 12px;border:1px solid #cbd5e1;border-radius:14px;background:#fff;font:inherit;color:#0f172a;box-sizing:border-box;resize:vertical}
.result-table .table-textarea[readonly]{background:#f8fafc;color:#334155}
.result-table .na-cell{font-size:.92rem;color:#64748b;text-align:center;background:#f8fafc}
.decision-grid{display:grid;grid-template-columns:1fr;gap:18px 22px}
.decision-inline-grid{display:grid;grid-template-columns:240px minmax(0,1fr) 320px;gap:18px 22px;align-items:start}
.save-actions{display:flex;justify-content:flex-end;padding:0 0 32px}
.save-btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 22px;border:1px solid #2f9e44;border-radius:999px;background:#2f9e44;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.save-btn[disabled]{opacity:.6;cursor:not-allowed}
.screen-only{display:block}
.print-only{display:none}
.hidden{display:none !important}
.clinic-report-header{padding:0 0 18px;width:100%}
.clinic-report-header img{display:block;width:100%;max-width:none;max-height:none;height:auto;object-fit:fill}
.clinic-report-header__fallback{padding:20px 0;text-align:center;font-size:20px;font-weight:700;color:#0f172a}
.usechh4-print-page{font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.45;color:#111827}
.usechh4-print-wrap{padding:0 8mm 0}
.usechh4-print-heading{position:relative;text-align:center;padding-bottom:6px}
.usechh4-print-code{position:absolute;right:0;top:0;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#0f172a}
.usechh4-print-act{font-size:14px;font-weight:700}
.usechh4-print-regulation{margin-top:2px;font-size:14px;font-weight:700}
.usechh4-print-title{margin-top:12px;font-size:20px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:#123524}
.usechh4-print-section-gap{height:14px}
.usechh4-print-divider{margin:16px 0 14px;border-top:1px solid #cbd5e1}
.usechh4-print-page-break{break-before:page;page-break-before:always;margin-top:0;padding-top:0}
.usechh4-print-table{width:100%;border-collapse:collapse;font-size:11px;table-layout:fixed}
.usechh4-print-table td,.usechh4-print-table th{padding:5px 6px;vertical-align:top}
.usechh4-print-table .label{width:31%;font-weight:700}
.usechh4-print-table .sep{width:2%}
.usechh4-print-table .value{width:67%}
.usechh4-print-table .label-wide{width:31%;font-weight:700}
.usechh4-print-table .value-wide{width:67%}
.usechh4-print-table .single-line-label{white-space:nowrap}
.usechh4-print-table .single-line-value{white-space:nowrap}
.usechh4-print-table .multiline-value{white-space:pre-line}
.usechh4-print-box-table{width:100%;border-collapse:collapse;margin-top:12px;font-size:11px}
.usechh4-print-box-table td{border:1px solid #123524;padding:9px 10px}
.usechh4-print-checks{margin-top:12px}
.usechh4-print-check-row{display:grid;grid-template-columns:1fr 1fr;gap:10px 32px}
.usechh4-print-check-item{display:flex;align-items:flex-start;gap:10px}
.usechh4-print-check-box{width:16px;height:16px;border:1px solid #123524;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;line-height:1;flex:0 0 16px}
.usechh4-print-results{width:100%;border-collapse:collapse;margin-top:16px;font-size:10.8px}
.usechh4-print-results th,.usechh4-print-results td{border:1px solid #123524;padding:7px 8px;vertical-align:top}
.usechh4-print-results thead th{background:#fff;color:#111827;font-weight:700;text-align:center}
.usechh4-print-results .subhead{background:#fff;color:#111827}
.usechh4-print-results .row-label{font-weight:700}
.usechh4-print-text-table{width:100%;border-collapse:collapse;margin-top:12px;font-size:11px}
.usechh4-print-text-table td{padding:4px 4px 4px 0;vertical-align:top}
.usechh4-print-decision{width:100%;border-collapse:collapse;margin-top:12px;font-size:10.6px}
.usechh4-print-decision th,.usechh4-print-decision td{border:1px solid #123524;padding:7px 8px;vertical-align:top}
.usechh4-print-decision thead th{background:#fff;color:#111827;font-weight:700;text-align:center}
.usechh4-print-decision .tick-col{width:6%;text-align:center}
.usechh4-print-decision .decision-col{width:18%}
.usechh4-print-note-small{margin-top:4px;font-size:10px}
.usechh4-print-declare{margin-top:12px}
.usechh4-print-doctor{width:100%;border-collapse:collapse;margin-top:8px;font-size:11px}
.usechh4-print-doctor td{padding:4px 6px 4px 0;vertical-align:top}
.usechh4-print-doctor .label{width:22%;font-weight:700}
.usechh4-print-doctor .sep{width:2%;padding-right:2px}
.usechh4-print-doctor .value{padding-left:0}
.usechh4-print-sign-block{margin-top:18px;font-size:11px;color:#111827}
.usechh4-print-sign-line{display:flex;align-items:center;gap:8px}
.usechh4-print-sign-line .label{font-weight:700;min-width:72px}
.usechh4-print-sign-line .value{text-align:left}
.usechh4-print-sign-image{margin:10px 0;padding-left:72px}
.usechh4-print-sign-image img{max-width:120px;max-height:44px;object-fit:contain}
.usechh4-print-footer-note{margin-top:18px;padding-top:10px;border-top:1px solid #123524;font-size:10px;line-height:1.45;text-align:center}

@media (max-width: 960px){
    .page-shell{padding:0 16px 24px}
    .section,.report-header,.save-actions{padding-left:0;padding-right:0}
    .grid-two,.grid-three,.grid-three-fields,.selector-row,.decision-grid,.decision-inline-grid{grid-template-columns:1fr}
    .report-code{position:static;display:block;margin-top:10px;text-align:center}
}

@media print{
    @page{size:A4 portrait;margin:12mm}
    body{background:#fff}
    .screen-only{display:none !important}
    .print-only{display:block !important}
    .page-shell{max-width:none;margin:0;padding:0}
}
</style>

<?php if ($printMode || $pdfDownloadMode): ?>
    <div class="<?php echo $pdfDownloadMode ? 'usechh4-print-page' : 'print-only usechh4-print-page'; ?>" style="padding:0;">
        <?php require __DIR__ . '/partials/clinic_header.php'; ?>
        <div class="usechh4-print-wrap">
            <div class="usechh4-print-heading">
                <div class="usechh4-print-code">USECHH 4</div>
                <div class="usechh4-print-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="usechh4-print-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <div class="usechh4-print-title">Summary Report for Medical Surveillance</div>
            </div>

            <table class="usechh4-print-table" style="margin-top:16px;">
                <tr>
                    <td class="label single-line-label">Name of Workplace</td>
                    <td class="sep">:</td>
                    <td class="value single-line-value" colspan="4"><?php echo $esc($showValue($company->company_name ?? '')); ?></td>
                </tr>
                <tr>
                    <td class="label single-line-label">MyKKP Registration No.</td>
                    <td class="sep">:</td>
                    <td class="value single-line-value" colspan="4"><?php echo $esc($showValue($company->mykpp_registration_no ?? '')); ?></td>
                </tr>
                <tr>
                    <td class="label single-line-label">Address of Workplace</td>
                    <td class="sep">:</td>
                    <td class="value-wide multiline-value" colspan="4"><?php echo $esc($showValue($companyAddressText)); ?></td>
                </tr>
            </table>

            <div class="usechh4-print-section-gap"></div>

            <table class="usechh4-print-table">
                <tr>
                    <td class="label-wide">Individual Chemical (Use ONE USECHH 4 form for ONE chemical only)</td>
                    <td class="sep">:</td>
                    <td class="value-wide" colspan="4"><?php echo $esc($showValue($screen['chemical_name'])); ?></td>
                </tr>
                <tr>
                    <td class="label single-line-label">Date of CHRA conducted</td>
                    <td class="sep">:</td>
                    <td class="value single-line-value" colspan="4"><?php echo $esc($printDate); ?></td>
                </tr>
                <tr>
                    <td class="label single-line-label">CHRA report no.</td>
                    <td class="sep">:</td>
                    <td class="value single-line-value" colspan="4"><?php echo $esc($showValue($screen['CHRA_reportNo'])); ?></td>
                </tr>
                <tr>
                    <td class="label-wide">Indication for medical surveillance based on CHRA report</td>
                    <td class="sep">:</td>
                    <td class="value-wide" colspan="4"><?php echo $esc($indicationSummary); ?></td>
                </tr>
            </table>

            <table class="usechh4-print-box-table">
                <tr>
                    <td style="width:48%;font-weight:700;">Total number of workers in the workplace</td>
                    <td><?php echo $esc($showValue($screen['totalNo_workplace'])); ?></td>
                </tr>
                <tr>
                    <td style="font-weight:700;">Name of the work unit where workers are in</td>
                    <td><?php echo $esc($showValue($screen['name_of_workUnit'])); ?></td>
                </tr>
                <tr>
                    <td style="font-weight:700;">Total number of exposed workers in the work unit</td>
                    <td><?php echo $esc($showValue($screen['no_exposedWorkers'])); ?></td>
                </tr>
                <tr>
                    <td style="font-weight:700;">Total number of workers examined</td>
                    <td><?php echo $esc($showValue($screen['totalNo_examined'])); ?></td>
                </tr>
            </table>

            <table class="usechh4-print-results avoid-break">
                <thead>
                    <tr>
                        <th style="width:26%;"></th>
                        <th rowspan="2" style="width:13%;">No. of workers with normal findings</th>
                        <th colspan="2" style="width:32%;">No. of workers with abnormal findings</th>
                        <th rowspan="2" style="width:17%;">No. of workers recommended for medical removal protection</th>
                    </tr>
                    <tr>
                        <th class="subhead"></th>
                        <th class="subhead">Occupational</th>
                        <th class="subhead">Non-occupational</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($metrics as $metric): ?>
                        <tr>
                            <td class="row-label">
                                <?php echo $esc($metric['title']); ?>
                                <?php if ($metric['specify_key'] && trim((string) ($metricValues[$metric['specify_key']] ?? '')) !== ''): ?>
                                    <div style="margin-top:4px;font-weight:400;">Please specify: <?php echo $esc(trim((string) ($metricValues[$metric['specify_key']] ?? ''))); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;"><?php echo $esc($showValue($metricValues[$metric['normal_key']] ?? '0')); ?></td>
                            <td style="text-align:center;"><?php echo $esc($showValue($metricValues[$metric['occ_key']] ?? '0')); ?></td>
                            <td style="text-align:center;"><?php echo $esc($metric['non_occ_key'] ? $showValue($metricValues[$metric['non_occ_key']] ?? '0') : 'Not applicable'); ?></td>
                            <td style="text-align:center;"><?php echo $esc($metric['mrp_key'] ? $showValue($metricValues[$metric['mrp_key']] ?? '0') : 'Not applicable'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="usechh4-print-page-break">
                <?php require __DIR__ . '/partials/clinic_header.php'; ?>
                <div class="usechh4-print-wrap">
                    <div class="usechh4-print-heading">
                        <div class="usechh4-print-code">USECHH 4</div>
                        <div class="usechh4-print-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                        <div class="usechh4-print-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                        <div class="usechh4-print-title">Summary Report for Medical Surveillance</div>
                    </div>

                    <table class="usechh4-print-text-table" style="margin-top:18px;">
                        <tr>
                            <td style="width:38%;font-weight:700;">Total no. of employees recommended for MRP</td>
                            <td style="width:2%;">:</td>
                            <td><?php echo $esc($showValue($screen['totalNo_MRP'])); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;">Name of Laboratory</td>
                            <td>:</td>
                            <td><?php echo $esc($showValue($screen['name_of_laboratoy'])); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;">Recommendation</td>
                            <td>:</td>
                            <td><?php echo $esc($showValue($reportRecommendationText)); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:700;">Decision / Justification of Decision / Date of Implementation</td>
                            <td>:</td>
                            <td>
                                <?php echo $esc($showValue($reportDecisionText)); ?>
                                <span style="display:inline-block;width:20px;"></span>
                                <?php echo $esc($showValue($reportDecisionJustificationText)); ?>
                                <span style="display:inline-block;width:20px;"></span>
                                <?php echo $esc($showValue($reportDecisionDateText)); ?>
                            </td>
                        </tr>
                    </table>

                    <div class="usechh4-print-divider"></div>

                    <div class="usechh4-print-declare">
                        I hereby declare that all particulars given in this report are accurate to the best of my knowledge.
                    </div>

                    <table class="usechh4-print-doctor avoid-break">
                        <tr>
                            <td class="label">Name of Occupational Health Doctor</td>
                            <td class="sep">:</td>
                            <td class="value"><?php echo $esc($showValue($doctorName)); ?></td>
                        </tr>
                        <tr>
                            <td class="label">OHD Registration No.</td>
                            <td class="sep">:</td>
                            <td class="value"><?php echo $esc($showValue($doctorRegistration)); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Name of Practice &amp; Address</td>
                            <td class="sep">:</td>
                            <td class="value" style="white-space:pre-line;"><?php echo $esc($showValue($editableClinicAddress)); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Tel No.</td>
                            <td class="sep">:</td>
                            <td class="value"><?php echo $esc($showValue($clinicRecord->clinic_tel ?? '')); ?></td>
                        </tr>
                        <tr>
                            <td class="label">Email Address</td>
                            <td class="sep">:</td>
                            <td class="value"><?php echo $esc($showValue($clinicRecord->clinic_email ?? '')); ?></td>
                        </tr>
                    </table>

                    <div class="usechh4-print-sign-block avoid-break">
                        <div class="usechh4-print-sign-image">
                            <?php if (trim((string) ($doctor->doctor_sign ?? '')) !== ''): ?>
                                <img src="<?php echo $esc(trim((string) ($doctor->doctor_sign ?? ''))); ?>" alt="Doctor signature">
                            <?php endif; ?>
                        </div>
                        <div class="usechh4-print-sign-line">
                            <span class="label">Signature:</span>
                            <span class="value"></span>
                        </div>
                        <div class="usechh4-print-sign-line">
                            <span class="label">Date:</span>
                            <span class="value"><?php echo $esc($downloadDateText); ?></span>
                        </div>
                    </div>

                    <div class="usechh4-print-footer-note">
                        Submit this form within 30 days of completion of medical surveillance to the Director General, Department of Occupational Safety and Health, Putrajaya. Please ensure all items in the form are completed. Incomplete forms will not be accepted.
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="page-shell">
        <section class="card">
            <div class="report-header"><div class="report-head-top" style="position:relative;display:block;text-align:center;">
                <div class="report-act">Occupational Safety and Health Act 1994 (Act 514)</div>
                <div class="report-regulation">Use and Standard of Exposure of Chemical Hazardous to Health Regulations 2000</div>
                <div class="report-code">USECHH 4</div>
                <div class="report-title">Summary Report for Medical Surveillance</div>
            </div>

            <?php if ($statusMessage !== ''): ?>
                <div class="flash"><?php echo $esc($statusMessage); ?></div>
            <?php endif; ?>

            <?php if (($createMode || $candidateRows->isNotEmpty()) && ! $viewMode): ?>
                <form method="get" id="usechh4PatientSelectForm" class="hidden">
                    <input type="hidden" name="create_mode" value="1">
                    <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
                    <input type="hidden" name="folder_date" value="<?php echo $esc($folderDate); ?>">
                    <input type="hidden" name="declaration_id" id="usechh4DeclarationInput" value="<?php echo $esc((string) ($isFreshCreate ? 0 : ($declaration->declaration_id ?? 0))); ?>">
                </form>
            <?php endif; ?>

            <form method="post" action="<?php echo $esc(route('surveillance.report.summary.save')); ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="declaration_id" value="<?php echo $esc((string) ($isFreshCreate ? 0 : ($declaration->declaration_id ?? $declarationId))); ?>">
                <input type="hidden" name="employee_id" value="<?php echo $esc((string) ($isFreshCreate ? 0 : $employeeId)); ?>">
                <input type="hidden" name="company_id" value="<?php echo $esc((string) $companyId); ?>">
                <input type="hidden" name="surveillance_id" value="<?php echo $esc((string) ($isFreshCreate ? 0 : $surveillanceId)); ?>">
                <input type="hidden" name="chemical_name" value="<?php echo $esc($screen['chemical_name']); ?>">
                <input type="hidden" name="folder_date" value="<?php echo $esc($folderDate); ?>">

                <div class="section">
                    <div class="digital-form">

                        <div class="digital-panel">
                            <h3 class="digital-panel-title">Workplace Summary</h3>
                            <div class="grid-three-fields">
                        <div>
                            <label class="field-label" for="usechh4ChemicalSelect">Chemical</label>
                            <?php if (($createMode || $chemicalOptions->isNotEmpty()) && ! $viewMode): ?>
                                <select class="selector-select" id="usechh4ChemicalSelect">
                                    <option value="">Select chemical</option>
                                    <?php foreach ($chemicalOptions as $candidate): ?>
                                        <?php $optionLabel = trim((string) ($candidate->chemicals ?? '')); ?>
                                        <option value="<?php echo $esc((string) ($candidate->declaration_id ?? 0)); ?>" <?php echo (! $isFreshCreate && (int) ($candidate->declaration_id ?? 0) === (int) ($declaration->declaration_id ?? 0)) ? 'selected' : ''; ?>>
                                            <?php echo $esc($optionLabel); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <div class="field-static"><?php echo $esc($showValue($screen['chemical_name'])); ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="field-label">CHRA Report No.</label>
                            <input class="field-input" type="text" name="CHRA_reportNo" value="<?php echo $esc($screen['CHRA_reportNo']); ?>"<?php echo $readonly; ?>>
                        </div>
                        <div>
                            <div class="field-label">Date of CHRA Conducted</div>
                            <div class="field-static"><?php echo $esc($printDate); ?></div>
                        </div>
                        <div class="full">
                            <div class="summary-table-shell">
                                <table class="summary-table">
                                    <thead>
                                        <tr>
                                            <th>Total Number of Workers in the Workplace</th>
                                            <th>Name of the Work Unit where Workers are in</th>
                                            <th>Total Number of Exposed Workers in the Work Unit</th>
                                            <th>Total Number of Workers Examined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><input class="field-input" type="number" min="0" name="totalNo_workplace" value="<?php echo $esc($screen['totalNo_workplace']); ?>"<?php echo $readonly; ?>></td>
                                            <td><input class="field-input" type="text" name="name_of_workUnit" value="<?php echo $esc($screen['name_of_workUnit']); ?>"<?php echo $readonly; ?>></td>
                                            <td><input class="field-input" type="number" min="0" name="no_exposedWorkers" value="<?php echo $esc($screen['no_exposedWorkers']); ?>"<?php echo $readonly; ?>></td>
                                            <td><input class="field-input" type="number" min="0" name="totalNo_examined" value="<?php echo $esc($screen['totalNo_examined']); ?>"<?php echo $readonly; ?>></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                            </div>
                        </div>

                        <div class="digital-panel section-divider">
                            <h3 class="digital-panel-title">Indication for Medical Surveillance Based on CHRA Report</h3>
                            <div class="grid-two">
                        <div class="full">
                            <div class="inline-options">
                                <?php foreach ($indicationOptions as $key => $label): ?>
                                    <label class="check-option">
                                        <input class="indication-flag" type="checkbox" name="indication_flags[]" value="<?php echo $esc($key); ?>"<?php echo in_array($key, $screenSelectedIndications, true) ? ' checked' : ''; ?><?php echo $disabled; ?>>
                                        <span><?php echo $esc($label); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="full<?php echo in_array('others', $screenSelectedIndications, true) ? '' : ' hidden'; ?>" id="indicationOtherDetailsRow">
                            <label class="field-label">Others Details</label>
                            <textarea class="field-textarea" name="indication_other"<?php echo $readonly; ?>><?php echo $esc($screenIndicationOther); ?></textarea>
                        </div>
                            </div>
                        </div>

                        <div class="digital-panel section-divider">
                            <h3 class="digital-panel-title">Medical Surveillance Results</h3>
                            <div class="result-table-shell">
                                <table class="result-table">
                                    <thead>
                                        <tr>
                                            <th rowspan="2" style="text-align:left;">Medical Surveillance Result</th>
                                            <th rowspan="2">No. of Workers with Normal Findings</th>
                                            <th colspan="2">No. of Workers with Abnormal Findings</th>
                                            <th rowspan="2">No. of Workers Recommended for Medical Removal Protection</th>
                                        </tr>
                                        <tr>
                                            <th>Occupational</th>
                                            <th>Non-occupational</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($metrics as $metric): ?>
                                            <tr>
                                                <td>
                                                    <div><?php echo $esc($metric['title']); ?></div>
                                                    <?php if ($metric['specify_key']): ?>
                                                        <div style="margin-top:10px;">
                                                            <div class="field-label" style="margin-bottom:6px;">Please Specify</div>
                                                            <input class="table-input" type="text" name="<?php echo $esc($metric['specify_key']); ?>" value="<?php echo $esc($metricValues[$metric['specify_key']] ?? ''); ?>"<?php echo $readonly; ?>>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <input class="table-input" type="number" min="0" name="<?php echo $esc($metric['normal_key']); ?>" value="<?php echo $esc($metricValues[$metric['normal_key']] ?? '0'); ?>"<?php echo $readonly; ?>>
                                                </td>
                                                <td>
                                                    <input class="table-input" type="number" min="0" name="<?php echo $esc($metric['occ_key']); ?>" value="<?php echo $esc($metricValues[$metric['occ_key']] ?? '0'); ?>"<?php echo $readonly; ?>>
                                                </td>
                                                <td>
                                                    <?php if ($metric['non_occ_key']): ?>
                                                        <input class="table-input" type="number" min="0" name="<?php echo $esc($metric['non_occ_key']); ?>" value="<?php echo $esc($metricValues[$metric['non_occ_key']] ?? '0'); ?>"<?php echo $readonly; ?>>
                                                    <?php else: ?>
                                                        <div class="na-cell">Not applicable</div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($metric['mrp_key']): ?>
                                                        <input class="table-input" type="number" min="0" name="<?php echo $esc($metric['mrp_key']); ?>" value="<?php echo $esc($metricValues[$metric['mrp_key']] ?? '0'); ?>"<?php echo $readonly; ?>>
                                                    <?php else: ?>
                                                        <div class="na-cell">Not applicable</div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="digital-panel section-divider">
                            <h3 class="digital-panel-title">Recommendation and Implementation</h3>
                            <div class="decision-grid">
                                <div>
                                    <label class="field-label">Total No. of Employees Recommended for MRP</label>
                                    <input class="field-input" type="number" min="0" name="totalNo_MRP" value="<?php echo $esc($screen['totalNo_MRP']); ?>"<?php echo $readonly; ?>>
                                </div>
                                <div>
                                    <label class="field-label">Name of Laboratory</label>
                                    <input class="field-input" type="text" name="name_of_laboratoy" value="<?php echo $esc($screen['name_of_laboratoy']); ?>"<?php echo $readonly; ?>>
                                </div>
                                <div class="full">
                                    <label class="field-label">Recommendation</label>
                                    <textarea class="field-textarea" name="recommendation"<?php echo $readonly; ?>><?php echo $esc($screen['recommendation']); ?></textarea>
                                </div>
                                <div class="decision-inline-grid full">
                                    <div>
                                        <div class="field-label">Decision</div>
                                        <div class="radio-stack">
                                            <label class="radio-option">
                                                <input type="radio" name="decision" value="Continue MS"<?php echo $screen['decision'] === 'Continue MS' ? ' checked' : ''; ?><?php echo $disabled; ?>>
                                                <span>Continue MS</span>
                                            </label>
                                            <label class="radio-option">
                                                <input type="radio" name="decision" value="Stop MS"<?php echo $screen['decision'] === 'Stop MS' ? ' checked' : ''; ?><?php echo $disabled; ?>>
                                                <span>Stop MS</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="field-label">Justification of Decision</label>
                                        <textarea class="field-textarea" name="justification_decision"<?php echo $readonly; ?>><?php echo $esc($screen['justification_decision']); ?></textarea>
                                    </div>
                                    <div>
                                        <label class="field-label">Date of Implementation</label>
                                        <input class="field-input" type="date" name="date_of_implementation" value="<?php echo $esc($screen['date_of_implementation']); ?>"<?php echo $readonly; ?>>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="digital-panel section-divider">
                            <h3 class="digital-panel-title">Occupational Health Doctor</h3>
                            <div class="grid-two">
                        <div>
                            <div class="field-label">Name of Occupational Health Doctor</div>
                            <div class="field-static"><?php echo $esc($showValue($doctorName)); ?></div>
                        </div>
                        <div>
                            <div class="field-label">OHD Registration No.</div>
                            <div class="field-static"><?php echo $esc($showValue($doctorRegistration)); ?></div>
                        </div>
                        <div class="full">
                            <div class="field-label">Name of Practice &amp; Address</div>
                            <textarea class="field-textarea" name="clinic_practice_address"<?php echo $readonly; ?>><?php echo $esc($editableClinicAddress); ?></textarea>
                        </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (! $viewMode): ?>
                    <div class="save-actions">
                        <button class="save-btn" type="submit" <?php echo $surveillanceId > 0 ? '' : 'disabled'; ?>>Save USECHH4</button>
                    </div>
                <?php endif; ?>
            </form>
        </section>
    </div>

    <?php if (($createMode || $candidateRows->isNotEmpty()) && ! $viewMode): ?>
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            var patientSelect = document.getElementById('usechh4ChemicalSelect');
            var declarationInput = document.getElementById('usechh4DeclarationInput');
            var form = document.getElementById('usechh4PatientSelectForm');
            var otherCheckbox = document.querySelector('.indication-flag[value="others"]');
            var otherRow = document.getElementById('indicationOtherDetailsRow');
            if (!patientSelect || !declarationInput || !form) {
                if (otherCheckbox && otherRow) {
                    var toggleOtherDetailsFallback = function () {
                        otherRow.classList.toggle('hidden', !otherCheckbox.checked);
                    };
                    otherCheckbox.addEventListener('change', toggleOtherDetailsFallback);
                    toggleOtherDetailsFallback();
                }
            } else {
                patientSelect.addEventListener('change', function () {
                    declarationInput.value = patientSelect.value || '';
                    form.submit();
                });
            }
            if (otherCheckbox && otherRow) {
                var toggleOtherDetails = function () {
                    otherRow.classList.toggle('hidden', !otherCheckbox.checked);
                };
                otherCheckbox.addEventListener('change', toggleOtherDetails);
                toggleOtherDetails();
            }
        });
        </script>
    <?php endif; ?>
<?php endif; ?>
</body>
</html>


